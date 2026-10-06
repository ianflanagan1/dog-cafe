<?php

declare(strict_types=1);

namespace App\Services;

use App\Environment\Server;
use App\Repositories\AppUserRepository;
use App\Session\Auth;
use App\Session\FavouriteSessionStore;
use App\Session\Session;
use App\Types\StandardTypes;
use App\Utils\Curl;
use App\Utils\Log;
use Google\Client;
use Google\Service\Oauth2;

/**
 * @phpstan-import-type PosInt from StandardTypes
 */
class LoginService
{
    protected const string DISCORD_TOKEN_URL = 'https://discordapp.com/api/oauth2/token';
    protected const string DISCORD_USERS_URL = 'https://discordapp.com/api/users/@me';
    protected const string DISCORD_AVATAR_URL_BASE = 'https://cdn.discordapp.com/avatars/';
    protected const string SESSION_KEY_PRE_LOGIN_LOCATION = 'loginLocation';

    /**
     * @param array{
     *      googleClientId: non-empty-string,
     *      googleClientSecret: non-empty-string,
     *      discordClientId: non-empty-string,
     *      discordClientSecret: non-empty-string,
     * } $config,
     */
    public function __construct(
        protected array $config,
        protected AppUserRepository $appUserRepository,
        protected Session $session,
        protected FavouriteSessionStore $favouriteSessionStore,
        protected AppUserPictureService $appUserPictureService,
        protected Auth $auth,
    ) {
    }

    /**
     * Generates the full Google OAuth2 login URL for use on the login page.
     *
     * @param non-empty-string $route Relative path for the OAuth2 redirect_uri.
     * @return non-empty-string
     */
    public function getLoginUrlGoogle(string $route): string
    {
        $redirectUrl = Server::getSchemeAndHost() . $route;

        return 'https://accounts.google.com/o/oauth2/v2/auth?response_type=code&access_type=online&client_id=' . $this->config['googleClientId'] . '&redirect_uri=' . urlencode($redirectUrl) . '&state&scope=email%20profile&approval_prompt=auto';

        // https://console.cloud.google.com
    }

    /**
     * Generates the full Discord OAuth2 login URL for use on the login page.
     *
     * @param non-empty-string $route Relative path for the OAuth2 redirect_uri.
     * @return non-empty-string
     */
    public function getLoginUrlDiscord(string $route): string
    {
        $redirectUrl = Server::getSchemeAndHost() . $route;

        return 'https://discord.com/oauth2/authorize?client_id=' . $this->config['discordClientId'] . '&response_type=code&redirect_uri=' . urlencode($redirectUrl) . '&scope=identify+email';

        // https://discord.com/developers/applications
    }

    /**
     * @param string $code
     * @param non-empty-string $route
     * @return bool
     */
    public function processLoginGoogle(mixed $code, string $route): bool
    {
        if (!self::validateCode($code)) {
            return false;
        }

        /** @phpstan-var string $code */

        $redirectUrl = Server::getSchemeAndHost() . $route;

        $clientGoogle = new Client();
        $clientGoogle->setClientId($this->config['googleClientId']);
        $clientGoogle->setClientSecret($this->config['googleClientSecret']);
        $clientGoogle->setRedirectUri($redirectUrl);

        $token = $clientGoogle->fetchAccessTokenWithAuthCode($code);
        $clientGoogle->setAccessToken($token['access_token']);

        $oauth2 = new Oauth2($clientGoogle);

        $userinfo = $oauth2->userinfo->get();

        // https://github.com/googleapis/google-api-php-client-services/blob/main/src/Oauth2/Userinfo.php

        $email = $userinfo->email;
        $name = $userinfo->name;
        $pictureUrl = $userinfo->picture;

        if (!is_string($email) || empty($email)) {
            Log::error('`email` invalid', $email);
            return false;
        }

        if (!is_string($name)) {
            Log::error('`name` invalid', $name);
            return false;
        }

        if (!is_string($pictureUrl)) {
            Log::error('`picture` invalid', $pictureUrl);
            return false;
        }

        if (empty($name)) {
            $name = null;
        }

        if (empty($pictureUrl)) {
            $pictureUrl = null;
        }

        $this->handleLogin(
            $email,
            $name,
            $pictureUrl,
        );

        return true;
    }

    /**
     * @param string $code
     * @param non-empty-string $route
     * @return bool
     */
    public function processLoginDiscord(mixed $code, string $route): bool
    {
        if (!self::validateCode($code)) {
            return false;
        }

        /** @phpstan-var string $code */

        $redirectUrl = Server::getSchemeAndHost() . $route;

        $result = Curl::arrayResponse(
            self::DISCORD_TOKEN_URL,
            [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            [
                'code'          => $code,
                'client_id'     => $this->config['discordClientId'],
                'client_secret' => $this->config['discordClientSecret'],
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => $redirectUrl,
                'score'         => 'identity%20email',
            ],
        );

        // Example response
        // '{"token_type": "Bearer", "access_token": "9NMkr6e5xNe1xqX4dMIBr74YK2aApt", "expires_in": 604800, "refresh_token": "DSobFDZggcq9ZsWbOJbeAUthfPSkBj", "scope": "email identify"}'

        /**
         * array{
         *      token_type: string,
         *      access_token: string,
         *      expires_in: int,
         *      refresh_token: string,
         *      scope: string,
         * } $result
         */

        if ($result === false) {
            Log::error('Failed getting token', [
                'code'          => $code,
                'redirect_uri'  => $redirectUrl,
            ]);
            return false;
        }

        if (!isset($result['access_token'])) {
            Log::error('`access_token` not set', $result);
            return false;
        }

        $user = Curl::arrayResponse(
            self::DISCORD_USERS_URL,
            [
                'Authorization: Bearer ' . $result['access_token'],
                'Content-Type: application/x-www-form-urlencoded',
            ],
        );

        // https://discord.com/developers/docs/resources/user#user-object

        // Example response
        // '{"id":"953560390363676693","username":"ianflanagan1","avatar":"57272cb844c4a89f4923614084e36559","discriminator":"0","public_flags":0,"flags":0,"banner":null,"accent_color":2237480,"global_name":"Ian Flanagan","avatar_decoration_data":null,"banner_color":"#222428","clan":null,"mfa_enabled":true,"locale":"en-US","premium_type":0,"email":"ianflanagan1@gmail.com","verified":true}'

        /**
         * array{
         *      id: string,
         *      username: string,
         *      discriminator: string,
         *      global_name: ?string,
         *      avatar: ?string,
         *      bot?: bool,
         *      system?: bool,
         *      mfa_enabled?: bool,
         *      banner?: ?string,
         *      accent_color?: ?int,
         *      locale?: string,
         *      verified?: bool,
         *      email?: ?string,
         *      flags?: int,
         *      premium_type?: int,
         *      public_flags?: int,
         *      avatar_decoration_data?: mixed,
         *      collectibles?: mixed,
         *      primary_guild?: mixed,
         *      banner_color?: ?string,
         *      clan?: mixed,
         * } $user
         */

        if ($user === false) {
            return false;
        }

        if (!isset($user['email']) || !is_string($user['email']) || empty($user['email'])) {
            Log::error('`email` invalid', $user);
            return false;
        }

        $email = $user['email'];

        $name = isset($user['global_name']) && is_string($user['global_name']) && !empty($user['global_name'])
            ? $user['global_name']
            : null;

        $pictureUrl = isset($user['avatar']) && is_string($user['avatar']) && !empty($user['avatar'])
            ? self::DISCORD_AVATAR_URL_BASE . $user['id'] . '/' . $user['avatar'] . '.jpg'
            : null;

        $this->handleLogin(
            $email,
            $name,
            $pictureUrl
        );

        return true;
    }

    /**
     * @param ?string $location
     * @return void
     */
    public function storePreLoginLocation(?string $location): void
    {
        if ($location === null || empty($location)) {
            return;
        }

        $this->session->put(
            self::SESSION_KEY_PRE_LOGIN_LOCATION,
            urldecode($location),
        );
    }

    /**
     * @return non-empty-string
     */
    public function getPreLoginLocation(): string
    {
        $res = $this->session->get(self::SESSION_KEY_PRE_LOGIN_LOCATION);

        assert(
            $res === null
            || (is_string($res) && !empty($res)),
            'Pre-Login Location must be null or non-empty-string',
        );

        return $res !== null
            ? $res
            : '/';
    }

    /**
     * @param non-empty-string $email
     * @param ?non-empty-string $name
     * @param ?non-empty-string $pictureUrl
     * @return PosInt
     */
    protected function handleLogin(string $email, ?string $name = null, ?string $pictureUrl = null): int
    {
        $appUser = $this->appUserRepository->findByEmail($email);

        // Existing user
        if ($appUser !== null) {
            $id = $appUser->id;

            $pictureFilename = $this->appUserPictureService->updateIfChanged(
                $id,
                $appUser->picture,
                $pictureUrl,
                $email
            );

            // New user
        } else {
            $pictureFilename = $pictureUrl !== null
                ? AppUserPictureService::saveFile($pictureUrl, $email)
                : null;

            $id = $this->appUserRepository->save($email, $name, $pictureFilename);
        }

        $this->auth->login($id, $pictureFilename);
        $this->favouriteSessionStore->refresh($id);

        return $id;
    }

    protected static function validateCode(mixed $code): bool
    {
        if (!is_string($code) || empty($code)) {
            Log::error('`code` invalid', $code);
            return false;
        }

        return true;
    }
}


// Other Oauth providers

// Microsoft
// https://portal.azure.com
/*
$parameters = [
    'client_id'     => $this->config['microsoftAppId'],
    'redirect_uri'  => 'http://localhost:8000/redirect-microsoft',
    'response_type' => 'token',
    'scope'         => 'https://graph.microsoft.com/User.Read',
    'state'         => session_id(),
];
$urlMicrosoft = 'https://login.microsoftonline.com/' . $this->config['microsoftTenantId'] . '/oauth2/v2.0/authorize?' . http_build_query($parameters);
// https://www.youtube.com/watch?v=GLV8XtUWVjk
*/

// Facebook: requires domain and business verification
// https://developers.facebook.com/

// Twitter: requires domain
// https://developer.x.com/

// Amazon: needs privacy policy url
// https://developer.amazon.com/

// LinkedIn: needs company linkedin profile
// https://www.linkedin.com/developers/apps/new

// Apple: error creating account
// https://developer.apple.com/

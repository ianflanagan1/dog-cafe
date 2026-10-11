<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Framework\Session\Auth;
use App\Framework\Session\Session;
use App\Repositories\AppUserRepository;
use App\Services\AppUserPictureService;
use App\Services\LoginService;
use App\Session\FavouriteSessionStore;
use Closure;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Uri\Rfc3986\Uri;

final class LoginServiceTest extends TestCase
{
    private LoginService $loginService;

    #[Override]
    protected function setUp(): void
    {
        $this->loginService = new LoginService(
            [
                'googleClientId'        => 'google-client',
                'googleClientSecret'    => 'google-secret',
                'discordClientId'       => 'discord-client',
                'discordClientSecret'   => 'discord-secret',
            ],
            self::createStub(AppUserRepository::class),
            self::createStub(Session::class),
            self::createStub(FavouriteSessionStore::class),
            self::createStub(AppUserPictureService::class),
            self::createStub(Auth::class),
            'https://example.com',
        );
    }

    /**
     * @return array<non-empty-string, array{Closure(LoginService): string, non-empty-string, non-empty-string}>
     */
    public static function providers(): array
    {
        return [
            'Google'    => [static fn (LoginService $service): string => $service->getLoginUrlGoogle('/redirect-login', 'a-state-token'), 'accounts.google.com', 'google-client'],
            'Discord'   => [static fn (LoginService $service): string => $service->getLoginUrlDiscord('/redirect-login', 'a-state-token'), 'discord.com', 'discord-client'],
        ];
    }

    #[DataProvider('providers')]
    public function test_a_login_url_carries_the_given_state(Closure $loginUrl, string $host, string $clientId): void
    {
        $query = $this->loginUrlQuery($loginUrl, $host);

        $this->assertSame('a-state-token', $query['state'] ?? null);
    }

    #[DataProvider('providers')]
    public function test_a_login_url_carries_the_client_id_and_a_redirect_uri_under_the_app_url(Closure $loginUrl, string $host, string $clientId): void
    {
        $query = $this->loginUrlQuery($loginUrl, $host);

        $this->assertSame($clientId, $query['client_id'] ?? null);
        $this->assertSame('https://example.com/redirect-login', $query['redirect_uri'] ?? null);
        $this->assertSame('code', $query['response_type'] ?? null);
    }

    /**
     * @param Closure(LoginService): string $loginUrl
     * @return array<mixed>
     */
    private function loginUrlQuery(Closure $loginUrl, string $host): array
    {
        $url = $loginUrl($this->loginService);

        $uri = new Uri($url);

        $this->assertSame('https', $uri->getScheme());
        $this->assertSame($host, $uri->getHost());

        parse_str($uri->getQuery() ?? '', $query);

        return $query;
    }
}

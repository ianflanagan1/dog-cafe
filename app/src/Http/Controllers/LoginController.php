<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Attributes\Get;
use App\Http\DTOs\ViewParameters;
use App\Http\FormRequests\LoginRedirectRequest;
use App\Http\FormRequests\LoginRequest;
use App\Http\Response\Response;
use App\Services\LoginService;

/**
 * @phpstan-import-type LoginValidated from LoginRequest
 * @phpstan-import-type LoginRedirectValidated from LoginRedirectRequest
 */
readonly class LoginController extends Controller
{
    protected const string REDIRECT_ROUTE_GOOGLE  = '/redirect-google-login';
    protected const string REDIRECT_ROUTE_DISCORD = '/redirect-discord-login';

    #[Get('/login')]
    /**
     * Render login page.
     */
    public function login(LoginService $loginService): ViewParameters
    {
        /** @var LoginValidated $validated */
        $validated = LoginRequest::validate();

        // Store the route to return to after successful login
        $loginService->storePreLoginLocation($validated['location']);

        return new ViewParameters(
            'main',
            'login',
            'login',
            [
                'search'    => false,
                'canonical' => '/login',
            ],
            [
                'canonical' => '/login',
            ],
            [
                'urlGoogle'     => $loginService->getLoginUrlGoogle(self::REDIRECT_ROUTE_GOOGLE),
                'urlDiscord'    => $loginService->getLoginUrlDiscord(self::REDIRECT_ROUTE_DISCORD),
            ],
            200,
        );
    }

    #[Get(self::REDIRECT_ROUTE_GOOGLE)]
    /**
     * Handle to callback from Google OAuth.
     */
    public function redirectGoogleLogin(LoginService $loginService): void
    {
        /** @var LoginRedirectValidated $validated */
        $validated = LoginRedirectRequest::validate();

        $loginService->processLoginGoogle($validated['code'], self::REDIRECT_ROUTE_GOOGLE);
        Response::redirect($loginService->getPreLoginLocation(), 303);
    }

    #[Get(self::REDIRECT_ROUTE_DISCORD)]
    /**
     * Handle to callback from Discord OAuth.
     */
    public function redirectDiscordLogin(LoginService $loginService): void
    {
        /** @var LoginRedirectValidated $validated */
        $validated = LoginRedirectRequest::validate();

        $loginService->processLoginDiscord($validated['code'], self::REDIRECT_ROUTE_DISCORD);
        Response::redirect($loginService->getPreLoginLocation(), 303);
    }
}

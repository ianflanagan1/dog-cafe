<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Framework\Http\Controller;
use App\Framework\Http\Response;
use App\Framework\Routing\Attributes\Get;
use App\Framework\View\ViewRenderer;
use App\Http\FormRequests\LoginRedirectRequest;
use App\Http\FormRequests\LoginRequest;
use App\Services\LoginService;

/**
 * @phpstan-import-type LoginValidated from LoginRequest
 * @phpstan-import-type LoginRedirectValidated from LoginRedirectRequest
 */
final readonly class LoginController extends Controller
{
    // COUPLED:oauth-redirect-paths. = google-console and discord-portal redirect URI paths (docs/external/); ⊆ nginx:PHP location routes
    protected const string REDIRECT_ROUTE_GOOGLE  = '/redirect-google-login';
    protected const string REDIRECT_ROUTE_DISCORD = '/redirect-discord-login';

    #[Get('/login')]
    /**
     * Render login page.
     */
    public function login(LoginRequest $request, ViewRenderer $viewRenderer, LoginService $loginService): Response
    {
        /** @var LoginValidated $validated */
        $validated = $request->validate();

        // Store the route to return to after successful login
        $loginService->storePreLoginLocation($validated['location']);

        return $viewRenderer->render(
            status: 200,
            page: 'login',
            layoutParameters: [
                'search'    => false,
                'canonical' => '/login',
            ],
            headParameters: [
                'canonical' => '/login',
            ],
            bodyParameters: [
                'urlGoogle'     => $loginService->getLoginUrlGoogle(self::REDIRECT_ROUTE_GOOGLE),
                'urlDiscord'    => $loginService->getLoginUrlDiscord(self::REDIRECT_ROUTE_DISCORD),
            ],
        );
    }

    #[Get(self::REDIRECT_ROUTE_GOOGLE)]
    /**
     * Handle to callback from Google OAuth.
     */
    public function redirectGoogleLogin(LoginRedirectRequest $request, LoginService $loginService): Response
    {
        /** @var LoginRedirectValidated $validated */
        $validated = $request->validate();

        $loginService->processLoginGoogle($validated['code'], self::REDIRECT_ROUTE_GOOGLE);
        return Response::redirect(303, $loginService->getPreLoginLocation());
    }

    #[Get(self::REDIRECT_ROUTE_DISCORD)]
    /**
     * Handle to callback from Discord OAuth.
     */
    public function redirectDiscordLogin(LoginRedirectRequest $request, LoginService $loginService): Response
    {
        /** @var LoginRedirectValidated $validated */
        $validated = $request->validate();

        $loginService->processLoginDiscord($validated['code'], self::REDIRECT_ROUTE_DISCORD);
        return Response::redirect(303, $loginService->getPreLoginLocation());
    }
}

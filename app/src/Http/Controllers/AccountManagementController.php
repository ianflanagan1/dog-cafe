<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Framework\Http\Controller;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Routing\Attributes\Delete;
use App\Framework\Routing\Attributes\Get;
use App\Framework\Routing\Attributes\Post;
use App\Framework\Session\Auth;
use App\Framework\Session\FormTokenHandler;
use App\Framework\View\ViewRenderer;
use App\Services\AppUserDeletionService;

final readonly class AccountManagementController extends Controller
{
    protected const string LOGOUT_KEY = 'accountLogout';
    protected const string DELETE_KEY = 'accountDelete';

    #[Get('/settings')]
    /**
     * Render account management page.
     */
    public function settings(ViewRenderer $viewRenderer, Auth $auth, FormTokenHandler $formTokenHandler): Response
    {
        $auth->guard(true);

        return $viewRenderer->render(
            status: 200,
            page: 'settings',
            layoutParameters: [
                'search'    => false,
                'canonical' => '/settings',
            ],
            headParameters: [
                'canonical' => '/settings',
            ],
            bodyParameters: [
              'logoutFormToken' => $formTokenHandler->createToken(self::LOGOUT_KEY),
              'deleteFormToken' => $formTokenHandler->createToken(self::DELETE_KEY),
            ],
        );
    }

    #[Post('/logout')]
    public function logout(Request $request, ViewRenderer $viewRenderer, Auth $auth, FormTokenHandler $formTokenHandler): Response
    {
        $auth->guard(true);

        if (!$formTokenHandler->isValidToken(self::LOGOUT_KEY, $request->formToken('logout_form_token'))) {
            return $viewRenderer->error(403);
        }

        $auth->logout();

        return Response::redirect(303, '/');
    }

    #[Delete('/account')]
    public function deleteAccount(
        Request $request,
        ViewRenderer $viewRenderer,
        Auth $auth,
        FormTokenHandler $formTokenHandler,
        AppUserDeletionService $appUserDeletionService,
    ): Response {
        $auth->guard(true);

        if (!$formTokenHandler->isValidToken(self::DELETE_KEY, $request->formToken('delete_form_token'))) {
            return $viewRenderer->error(403);
        }

        $appUserDeletionService->handle();

        return Response::noContent();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Framework\Http\Controller;
use App\Framework\Http\Request;
use App\Framework\Http\Response\Response;
use App\Framework\Routing\Attributes\Delete;
use App\Framework\Routing\Attributes\Get;
use App\Framework\Routing\Attributes\Post;
use App\Framework\Session\Auth;
use App\Framework\Session\FormTokenHandler;
use App\Framework\View\ViewParameters;
use App\Services\AppUserDeletionService;

readonly class AccountManagementController extends Controller
{
    protected const string LOGOUT_KEY = 'accountLogout';
    protected const string DELETE_KEY = 'accountDelete';

    #[Get('/settings')]
    /**
     * Render account management page.
     */
    public function settings(Auth $auth, FormTokenHandler $formTokenHandler): ViewParameters
    {
        $auth->guard(true);

        return new ViewParameters(
            'main',
            'settings',
            'settings',
            [
                'search'    => false,
                'canonical' => '/settings',
            ],
            [
                'canonical' => '/settings',
            ],
            [
              'logoutFormToken' => $formTokenHandler->createToken(self::LOGOUT_KEY),
              'deleteFormToken' => $formTokenHandler->createToken(self::LOGOUT_KEY),
            ],
            200,
        );
    }

    #[Post('/logout')]
    public function logout(Request $request, Auth $auth, FormTokenHandler $formTokenHandler): void
    {
        $auth->guard(true);

        if (!$formTokenHandler->isValidToken(self::LOGOUT_KEY, $request->formToken('logout_form_token'))) {
            Response::htmlError(403);
        }

        $auth->logout();

        Response::redirect('/', 303);
    }

    #[Delete('/account')]
    public function deleteAccount(
        Request $request,
        Auth $auth,
        FormTokenHandler $formTokenHandler,
        AppUserDeletionService $appUserDeletionService
    ): string {
        $auth->guard(true);

        if (!$formTokenHandler->isValidToken(self::DELETE_KEY, $request->formToken('delete_form_token'))) {
            Response::htmlError(403);
        }

        $appUserDeletionService->handle();

        return Response::empty();
    }
}

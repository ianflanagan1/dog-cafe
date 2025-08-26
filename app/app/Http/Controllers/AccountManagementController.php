<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Attributes\Delete;
use App\Http\Attributes\Get;
use App\Http\Attributes\Post;
use App\Http\DTOs\ViewParameters;
use App\Http\Request;
use App\Http\Response\Response;
use App\Services\AppUserDeletionService;
use App\Session\Auth;
use App\Session\FormTokenHandler;

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
    public function logout(Auth $auth, FormTokenHandler $formTokenHandler): void
    {
        $auth->guard(true);

        if (!$formTokenHandler->isValidToken(self::LOGOUT_KEY, Request::getFormToken('logout_form_token'))) {
            Response::htmlError(403);
        }

        $auth->logout();

        Response::redirect('/', 303);
    }

    #[Delete('/account')]
    public function deleteAccount(
        Auth $auth,
        FormTokenHandler $formTokenHandler,
        AppUserDeletionService $appUserDeletionService
    ): string {
        $auth->guard(true);

        if (!$formTokenHandler->isValidToken(self::DELETE_KEY, Request::getFormToken('delete_form_token'))) {
            Response::htmlError(403);
        }

        $appUserDeletionService->handle();

        return Response::empty();
    }
}

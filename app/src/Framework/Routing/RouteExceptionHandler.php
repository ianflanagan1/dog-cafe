<?php

declare(strict_types=1);

namespace App\Framework\Routing;

use App\Framework\Errors\ErrorCode;
use App\Framework\Errors\NotFoundException;
use App\Framework\Errors\SystemException;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Session\UnauthenticatedException;
use App\Framework\Support\Log;
use App\Framework\Validation\InputValidationException;
use App\Framework\View\ViewRenderer;
use Throwable;

final readonly class RouteExceptionHandler
{
    public function __construct(private ViewRenderer $viewRenderer)
    {
    }

    public function handle(Throwable $e, Request $request): Response
    {
        if ($e instanceof RouteNotFoundException || $e instanceof NotFoundException) {
            return $request->wantsJson()
                ? Response::jsonError(404, ErrorCode::ItemNotFound)
                : $this->viewRenderer->error(404);
        }

        if ($e instanceof UnauthenticatedException) {
            if ($request->wantsJson()) {
                return Response::jsonError(401, ErrorCode::NotLoggedIn);
            }

            return $e->redirect
                ? Response::redirect(302, '/login')
                : $this->viewRenderer->error(401);
        }

        if ($e instanceof InputValidationException) {
            return $request->wantsJson()
                ? Response::jsonErrors(422, $e->getErrors())
                : $this->viewRenderer->error(422);
        }

        if ($e instanceof SystemException) {
            Log::exception($e);

            return $request->wantsJson()
                ? Response::jsonError(500, ErrorCode::SystemError)
                : $this->viewRenderer->error(500);
        }

        // Fallback: unhandled exception
        Log::exception($e);

        return $request->wantsJson()
            ? Response::jsonError(500, ErrorCode::SystemError)
            : $this->viewRenderer->error(500);
    }
}

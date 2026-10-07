<?php

declare(strict_types=1);

namespace App\Framework\Routing;

use App\Framework\Errors\ErrorCode;
use App\Framework\Errors\NotFoundException;
use App\Framework\Errors\SystemException;
use App\Framework\Http\Request;
use App\Framework\Http\Response\Response;
use App\Framework\Session\UnauthenticatedException;
use App\Framework\Support\Log;
use App\Framework\Validation\InputValidationException;
use App\Framework\View\ViewParameters;
use Throwable;

class RouteExceptionHandler
{
    public static function handle(Throwable $e, Request $request): ViewParameters|string
    {
        if ($e instanceof RouteNotFoundException || $e instanceof NotFoundException) {
            return $request->wantsJson()
                ? Response::jsonError(ErrorCode::ItemNotFound, 404)
                : Response::htmlError(404);
        }

        if ($e instanceof UnauthenticatedException) {
            if ($request->wantsJson()) {
                return Response::jsonError(ErrorCode::NotLoggedIn, 401);
            }

            if ($e->redirect) {
                Response::redirect('/login');
            }

            return Response::htmlError(401);
        }

        if ($e instanceof InputValidationException) {
            return $request->wantsJson()
                ? Response::jsonErrors($e->getErrors(), 422)
                : Response::htmlError(422);
        }

        if ($e instanceof SystemException) {
            Log::exception($e);

            return $request->wantsJson()
                ? Response::jsonError(ErrorCode::SystemError, 500)
                : Response::htmlError(500);
        }

        // Fallback: unhandled exception
        Log::exception($e);

        return $request->wantsJson()
            ? Response::jsonError(ErrorCode::SystemError, 500)
            : Response::htmlError(500);
    }
}

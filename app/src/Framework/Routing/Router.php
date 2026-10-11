<?php

declare(strict_types=1);

namespace App\Framework\Routing;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use Throwable;

final readonly class Router
{
    public function __construct(
        private RouteRegistry $routeRegistry,
        private ActionExecutor $actionExecutor,
        private RouteExceptionHandler $routeExceptionHandler,
    ) {
    }

    public function resolve(Request $request): Response
    {
        try {
            $actionable = $this->routeRegistry->resolve($request);
            return $this->actionExecutor->handle($actionable);

        } catch (Throwable $e) {
            return $this->routeExceptionHandler->handle($e, $request);
        }
    }
}

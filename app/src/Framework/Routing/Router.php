<?php

declare(strict_types=1);

namespace App\Framework\Routing;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use Throwable;

class Router
{
    public function __construct(
        protected RouteRegistry $routeRegistry,
        protected ActionExecutor $actionExecutor,
        protected RouteExceptionHandler $routeExceptionHandler,
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

<?php

declare(strict_types=1);

namespace App\Framework\Routing;

use App\Framework\Container\Container;
use App\Framework\Http\Request;
use App\Framework\Session\Auth;
use App\Framework\View\View;
use Throwable;

class Router
{
    public function __construct(
        protected Container $container,
        protected RouteRegistry $routeRegistry,
        protected ActionExecutor $actionExecutor,
    ) {
    }

    /**
     * @param Request $request
     * @return string
     */
    public function resolve(Request $request): string
    {
        try {
            $actionable = $this->routeRegistry->resolve($request);

            $response = $this->actionExecutor->handle($actionable);

        } catch (Throwable $e) {
            $response = RouteExceptionHandler::handle($e, $request);
        }

        // Json
        if (is_string($response)) {
            return $response;
        }

        // Html

        /** @var Auth $auth */
        $auth = $this->container->get(Auth::class);

        return (string) new View(
            $auth,
            $response,
        );
    }
}

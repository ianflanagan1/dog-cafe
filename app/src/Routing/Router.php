<?php

declare(strict_types=1);

namespace App\Routing;

use App\Container;
use App\Http\Enums\HttpMethod;
use App\Http\View;
use App\Session\Auth;
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
     * @param non-empty-string $requestUri
     * @param HttpMethod $httpMethod
     * @return string
     */
    public function resolve(string $requestUri, HttpMethod $httpMethod): string
    {
        try {
            $actionable = $this->routeRegistry->resolve(
                $requestUri,
                $httpMethod,
            );

            $response = $this->actionExecutor->handle($actionable);

        } catch (Throwable $e) {
            $response = RouteExceptionHandler::handle($e);
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

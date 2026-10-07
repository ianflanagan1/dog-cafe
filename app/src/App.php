<?php

declare(strict_types=1);

namespace App;

use App\Framework\Container\Container;
use App\Framework\Http\Request;
use App\Framework\Routing\Router;

readonly class App
{
    public function run(Container $container): void
    {
        // Registered before anything else resolves, so every class that type-hints Request gets this instance
        $request = Request::fromGlobals();
        $container->set(Request::class, $request);

        /** @var Router $router */
        $router = $container->get(Router::class);

        echo $router->resolve($request);
    }
}

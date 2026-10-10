<?php

declare(strict_types=1);

namespace App;

use App\Framework\Container\Container;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Routing\Router;

readonly class App
{
    public function run(Container $container): void
    {
        $request = Request::fromGlobals();
        $container->set(Request::class, $request);

        /** @var Router $router */
        $router = $container->get(Router::class);

        $this->send($router->resolve($request));
    }

    /**
     * The only place the response status, headers and body are output.
     */
    protected function send(Response $response): void
    {
        http_response_code($response->status);

        foreach ($response->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }

        echo $response->body;
    }
}

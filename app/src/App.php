<?php

declare(strict_types=1);

namespace App;

use App\Framework\Container\Container;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Routing\Router;
use App\Framework\Session\SessionInterface;

final readonly class App
{
    public function run(Container $container): void
    {
        $request = Request::fromGlobals();
        $container->set(Request::class, $request);

        /** @var Router */
        $router = $container->get(Router::class);

        $response = $router->resolve($request);

        // Release the session lock before sending. Closing is a no-op if a controller already closed it, or nothing used it
        /** @var SessionInterface */
        $session = $container->get(SessionInterface::class);
        $session->close();

        $this->send($response);
    }

    /**
     * The only place the response status, headers and body are output.
     */
    private function send(Response $response): void
    {
        http_response_code($response->status);

        foreach ($response->headers as $name => $value) {
            header($name . ': ' . $value, true);
        }

        echo $response->body;
    }
}

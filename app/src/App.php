<?php

declare(strict_types=1);

namespace App;

use App\Http\Request;
use App\Routing\Router;

readonly class App
{
    public function run(Router $router): void
    {
        echo $router->resolve(
            Request::getRequestTarget(),
            Request::getMethod(),
        );
    }
}

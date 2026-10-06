<?php

declare(strict_types=1);

namespace App;

use App\Framework\Http\Request;
use App\Framework\Routing\Router;

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

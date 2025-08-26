<?php

declare(strict_types=1);

namespace App;

use App\Http\Request;
use App\Routing\Router;

readonly class App
{
    public function run(Router $router): void
    {
        date_default_timezone_set('Europe/London');

        echo $router->resolve(
            Request::getRequestTarget(),
            Request::getMethod(),
        );
    }
}

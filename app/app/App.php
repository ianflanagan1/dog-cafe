<?php

declare(strict_types=1);

namespace App;

use Throwable;
use App\Http\Request;
use App\Routing\Router;
use App\Routing\ActionExecutor;
use App\Routing\RouteExceptionHandler;

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

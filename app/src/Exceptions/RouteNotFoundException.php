<?php

declare(strict_types=1);

namespace App\Exceptions;

class RouteNotFoundException extends \Exception
{
    public function __construct()
    {
        parent::__construct('404 Not Found');
    }
}

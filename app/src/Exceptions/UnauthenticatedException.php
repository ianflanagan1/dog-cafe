<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class UnauthenticatedException extends RuntimeException
{
    public function __construct(public bool $redirect)
    {
    }
}

<?php

declare(strict_types=1);

namespace App\Framework\View;

use Exception;

final class ViewNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('View not found');
    }
}

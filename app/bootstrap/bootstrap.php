<?php

declare(strict_types=1);

use App\Container;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/pathConstants.php';

return new Container(require CONFIG_PATH . '/containerBindings.php');

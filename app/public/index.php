<?php

declare(strict_types=1);

use App\App;
use App\Framework\Container\Container;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container(require CONFIG_PATH . '/containerBindings.php');
$container->call(App::class, 'run');

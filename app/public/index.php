<?php

declare(strict_types=1);

use App\App;
use App\Framework\Container\Container;

/** @var Container $container */
$container = require __DIR__ . '/../bootstrap/bootstrap.php';
$container->call(App::class, 'run');

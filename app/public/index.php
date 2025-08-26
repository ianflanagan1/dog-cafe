<?php

declare(strict_types=1);

use App\App;

$container = require __DIR__ . '/../bootstrap/bootstrap.php';
$container->call(App::class, 'run');

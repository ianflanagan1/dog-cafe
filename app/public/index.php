<?php

declare(strict_types=1);

use App\App;
use App\Framework\Container\Container;

require __DIR__ . '/../vendor/autoload.php';

/** @var array<string, (callable(): mixed)|object|string> */
$instructions = require CONFIG_PATH . '/containerBindings.php';

$container = new Container($instructions);
$container->call(App::class, 'run');

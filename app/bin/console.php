#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Framework\Console\CommandInterface;
use App\Framework\Console\Console;
use App\Framework\Console\Output;
use App\Framework\Container\Container;

// No web server serves bin/, only public/. In case one is ever misconfigured to
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    return;
}

require __DIR__ . '/../vendor/autoload.php';

/** @var array<string, (callable(): mixed)|object|string> */
$instructions = require CONFIG_PATH . '/containerBindings.php';

/** @var array<non-empty-string, class-string<CommandInterface>> */
$commands = require CONFIG_PATH . '/consoleCommands.php';

$console = new Console(new Container($instructions), $commands, new Output(STDOUT, STDERR));

// One of the app's two exits (phpstan.neon): the shell sees the command's exit code.
// The CLI always sets $argv, whatever register_argc_argv says; PHPStan can't know this file only runs there
exit($console->run(array_slice($argv ?? [], 1)));

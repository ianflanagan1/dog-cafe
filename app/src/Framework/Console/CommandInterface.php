<?php

declare(strict_types=1);

namespace App\Framework\Console;

/**
 * One command bin/console.php can run, registered by name in config/consoleCommands.php
 */
interface CommandInterface
{
    public const int SUCCESS = 0;
    public const int FAILURE = 1;
    /** The command line was wrong: an unknown command, or arguments the command doesn't take */
    public const int INVALID = 2;

    /**
     * One line for `list`. Static so listing the commands doesn't construct them and their dependencies
     */
    public static function description(): string;

    /**
     * @param list<string> $args The arguments after the command's name
     * @return int The process exit code: SUCCESS, FAILURE or INVALID
     */
    public function run(array $args, Output $output): int;
}

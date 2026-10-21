<?php

declare(strict_types=1);

namespace App\Framework\Console;

use App\Framework\Container\Container;
use Throwable;

/**
 * Runs one command from the command line and returns its exit code. The CLI's counterpart to App, and its top-level
 * exception handler, as RouteExceptionHandler is for requests
 */
final readonly class Console
{
    /**
     * @param array<non-empty-string, class-string<CommandInterface>> $commands By the name they're run with
     */
    public function __construct(
        private Container $container,
        private array $commands,
        private Output $output,
    ) {
    }

    /**
     * @param list<string> $args The command line after the script name: a command's name, then its arguments.
     *                           No name runs `list`
     * @return int The process exit code
     */
    public function run(array $args): int
    {
        $name = $args[0] ?? 'list';

        if ($name === 'list') {
            $this->list();
            return CommandInterface::SUCCESS;
        }

        if (!isset($this->commands[$name])) {
            $this->output->error("Unknown command \"{$name}\". Run `list` to see the commands.");
            return CommandInterface::INVALID;
        }

        try {
            /** @var CommandInterface */
            $command = $this->container->get($this->commands[$name]);

            return $command->run(array_slice($args, 1), $this->output);
        } catch (Throwable $e) {
            $this->output->error($e::class . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString());
            return CommandInterface::FAILURE;
        }
    }

    private function list(): void
    {
        $descriptions = ['list' => 'List the commands'];

        foreach ($this->commands as $name => $class) {
            $descriptions[$name] = $class::description();
        }

        ksort($descriptions);
        $width = max(array_map(strlen(...), array_keys($descriptions)));

        $this->output->line('Usage: php bin/console.php <command> [arguments]');
        $this->output->line();
        $this->output->line('Commands:');

        foreach ($descriptions as $name => $description) {
            $this->output->line('  ' . str_pad($name, $width) . '  ' . $description);
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Framework\Console;

use App\Framework\Console\CommandInterface;
use App\Framework\Console\Console;
use App\Framework\Console\Output;
use App\Framework\Container\Container;
use Override;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ConsoleTest extends TestCase
{
    /** @var resource */
    private mixed $stdout;

    /** @var resource */
    private mixed $stderr;

    #[Override]
    protected function setUp(): void
    {
        $this->stdout = $this->memoryStream();
        $this->stderr = $this->memoryStream();
    }

    public function test_no_command_lists_every_command_with_its_description(): void
    {
        $exitCode = $this->console()->run([]);

        $this->assertSame(CommandInterface::SUCCESS, $exitCode);
        $this->assertStringContainsString('  echo     Write its arguments', $this->read($this->stdout));
        $this->assertStringContainsString('  explode  Throw', $this->read($this->stdout));
        $this->assertStringContainsString('  list     List the commands', $this->read($this->stdout));
        $this->assertSame('', $this->read($this->stderr));
    }

    public function test_list_lists_the_commands(): void
    {
        $exitCode = $this->console()->run(['list']);

        $this->assertSame(CommandInterface::SUCCESS, $exitCode);
        $this->assertStringContainsString('  echo     Write its arguments', $this->read($this->stdout));
    }

    public function test_listing_does_not_construct_the_commands(): void
    {
        $console = $this->console(['unconstructable' => UnconstructableCommand::class]);

        $this->assertSame(CommandInterface::SUCCESS, $console->run(['list']));
    }

    public function test_a_command_gets_the_arguments_after_its_name_and_its_exit_code_is_returned(): void
    {
        $exitCode = $this->console()->run(['echo', 'a', 'b']);

        $this->assertSame(CommandInterface::FAILURE, $exitCode);
        $this->assertSame('a b' . PHP_EOL, $this->read($this->stdout));
    }

    public function test_an_unknown_command_is_reported_on_stderr_as_invalid(): void
    {
        $exitCode = $this->console()->run(['missing']);

        $this->assertSame(CommandInterface::INVALID, $exitCode);
        $this->assertSame('', $this->read($this->stdout));
        $this->assertStringContainsString('Unknown command "missing"', $this->read($this->stderr));
    }

    public function test_an_exception_from_a_command_is_reported_on_stderr_as_a_failure(): void
    {
        $exitCode = $this->console()->run(['explode']);

        $this->assertSame(CommandInterface::FAILURE, $exitCode);
        $this->assertStringStartsWith(RuntimeException::class . ': Boom' . PHP_EOL, $this->read($this->stderr));
    }

    /**
     * @param array<non-empty-string, class-string<CommandInterface>> $commands
     */
    private function console(array $commands = ['echo' => EchoCommand::class, 'explode' => ExplodingCommand::class]): Console
    {
        return new Console(new Container(), $commands, new Output($this->stdout, $this->stderr));
    }

    /**
     * @return resource
     */
    private function memoryStream(): mixed
    {
        $stream = fopen('php://memory', 'w+');
        $this->assertIsResource($stream);

        return $stream;
    }

    /**
     * @param resource $stream
     */
    private function read(mixed $stream): string
    {
        rewind($stream);
        $contents = stream_get_contents($stream);
        $this->assertIsString($contents);

        return $contents;
    }
}

/**
 * Returns FAILURE so a test can tell its exit code apart from Console's own SUCCESS
 */
final readonly class EchoCommand implements CommandInterface
{
    #[Override]
    public static function description(): string
    {
        return 'Write its arguments';
    }

    #[Override]
    public function run(array $args, Output $output): int
    {
        $output->line(implode(' ', $args));
        return self::FAILURE;
    }
}

final readonly class ExplodingCommand implements CommandInterface
{
    #[Override]
    public static function description(): string
    {
        return 'Throw';
    }

    #[Override]
    public function run(array $args, Output $output): int
    {
        throw new RuntimeException('Boom');
    }
}

final readonly class UnconstructableCommand implements CommandInterface
{
    public function __construct()
    {
        throw new RuntimeException('Constructed');
    }

    #[Override]
    public static function description(): string
    {
        return 'Throw when constructed';
    }

    #[Override]
    public function run(array $args, Output $output): int
    {
        return self::SUCCESS;
    }
}

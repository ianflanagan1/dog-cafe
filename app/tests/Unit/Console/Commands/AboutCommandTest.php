<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\AboutCommand;
use App\Framework\Config\Config;
use App\Framework\Console\CommandInterface;
use App\Framework\Console\Output;
use PHPUnit\Framework\TestCase;

final class AboutCommandTest extends TestCase
{
    public function test_it_shows_the_environment_url_and_php_version(): void
    {
        [$output, $stdout] = $this->streamOutput();

        $exitCode = $this->command()->run([], $output);

        $this->assertSame(CommandInterface::SUCCESS, $exitCode);
        $this->assertStringContainsString('Environment:  testing', $this->read($stdout));
        $this->assertStringContainsString('URL:          https://example.test', $this->read($stdout));
        $this->assertStringContainsString('PHP:          ' . PHP_VERSION, $this->read($stdout));
    }

    public function test_it_rejects_arguments_as_invalid(): void
    {
        [$output, $stdout] = $this->streamOutput();

        $exitCode = $this->command()->run(['extra'], $output);

        $this->assertSame(CommandInterface::INVALID, $exitCode);
        $this->assertSame('', $this->read($stdout));
    }

    private function command(): AboutCommand
    {
        return new AboutCommand(new Config(['app' => ['app_env' => 'testing', 'url' => 'https://example.test']]));
    }

    /**
     * @return array{Output, resource}
     */
    private function streamOutput(): array
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');
        $this->assertIsResource($stdout);
        $this->assertIsResource($stderr);

        return [new Output($stdout, $stderr), $stdout];
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

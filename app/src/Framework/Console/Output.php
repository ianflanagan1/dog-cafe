<?php

declare(strict_types=1);

namespace App\Framework\Console;

/**
 * Where a command writes: results to stdout, errors to stderr, so a caller can pipe one without the other
 */
final readonly class Output
{
    /**
     * @param resource $stdout
     * @param resource $stderr
     */
    public function __construct(
        private mixed $stdout,
        private mixed $stderr,
    ) {
    }

    public function line(string $text = ''): void
    {
        fwrite($this->stdout, $text . PHP_EOL);
    }

    public function error(string $text): void
    {
        fwrite($this->stderr, $text . PHP_EOL);
    }
}

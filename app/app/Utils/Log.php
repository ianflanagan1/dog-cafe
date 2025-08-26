<?php

declare(strict_types=1);

namespace App\Utils;

use App\Types\StandardTypes;
use Throwable;

/**
 * @phpstan-import-type NonNegInt from StandardTypes
*/
class Log
{
    protected const int PRETTIFY_SIZE_LIMIT = 5000;
    protected const string UNSET_DUMP = '__LOG_INTERNAL_UNSET_DUMP__';

    public static function info(string $message, mixed $dump = self::UNSET_DUMP): void
    {
        self::writeStdOut('INFO', $message, $dump);
    }

    public static function debug(string $message, mixed $dump = self::UNSET_DUMP): void
    {
        self::writeStdOut('DEBUG', $message, $dump);
    }

    public static function warning(string $message, mixed $dump = self::UNSET_DUMP): void
    {
        self::writeStdErr('WARNING', $message, $dump);
    }

    public static function error(string $message, mixed $dump = self::UNSET_DUMP): void
    {
        self::writeStdErr('ERROR', $message, $dump);
    }

    public static function exception(Throwable $exception): void
    {
        $message = get_class($exception) . ': ' . $exception->getMessage();
        $trace = $exception->getTraceAsString();

        self::writeStdErr('EXCEPTION', $message . PHP_EOL . $trace);
    }

    protected static function writeStdOut(string $level, string $message, mixed $dump = self::UNSET_DUMP): void
    {
        self::write($level, $message, $dump, 'php://stdout');
    }

    protected static function writeStdErr(string $level, string $message, mixed $dump = self::UNSET_DUMP): void
    {
        self::write($level, $message, $dump, 'php://stderr');
    }

    protected static function write(string $level, string $message, mixed $dump, string $stream): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $caller = self::getCaller();

        $prefix = $caller !== null
            ? "[{$caller['class']}::{$caller['function']}({$caller['line']})]"
            : '';

        $suffix = $dump !== self::UNSET_DUMP
            ? PHP_EOL . self::prettify($dump)
            : '';

        $record = "[{$timestamp}] [{$level}] {$prefix} {$message}{$suffix}";

        $handle = fopen($stream, 'a');

        // Fallback if cannot open stream
        if ($handle === false) {
            error_log($record);
            return;
        }

        fwrite($handle, $record . PHP_EOL);
        fclose($handle);
    }

    /**
     * Prepares any type of variable in a readable format and truncates the output string if the length exceeds the limit.
     *
     * @param mixed $dump
     * @return non-empty-string
     */
    protected static function prettify(mixed $dump): string
    {
        $output = self::describeValue($dump);

        return strlen($output) > self::PRETTIFY_SIZE_LIMIT
            ? substr($output, 0, self::PRETTIFY_SIZE_LIMIT) . '... [truncated]'
            : $output;
    }

    /**
     * Returns a human-readable recursive representation of any value, including nested arrays and exceptions.
     *
     * @param mixed $value
     * @param NonNegInt $depth
     * @return non-empty-string
     */
    protected static function describeValue(mixed $value, int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth);

        if (is_array($value)) {
            return self::describeArray($value, $depth);
        }

        if ($value === null) {
            return $indent . 'NULL';
        }

        if (is_scalar($value)) {
            return $indent . gettype($value) . ': ' . var_export($value, true);
        }

        if ($value instanceof Throwable) {
            return $indent . get_class($value) . ': ' . $value->getMessage();
        }

        return $indent . gettype($value) . ': ' . print_r($value, true);
    }

    /**
     * Recursively describes the structure and types of an array.
     *
     * @param array<mixed> $array
     * @param NonNegInt $depth
     * @return non-empty-string
     */
    protected static function describeArray(array $array, int $depth): string
    {
        $output = 'array:' . PHP_EOL;

        $indent = str_repeat('  ', $depth + 1);

        foreach ($array as $key => $val) {
            $formattedKey = is_int($key) ? $key : "'$key'";
            $output .= "$indent [$formattedKey] => ";

            if (is_array($val)) {
                // Don't print "array" here — recursive call prints it
                $output .= self::describeArray($val, $depth + 2);
            } else {
                $output .= self::describeValue($val, 0) . PHP_EOL;
            }
        }

        return $output;
    }

    /**
     * @return array{
     *      class: ?class-string,
     *      function: string,
     *      line: ?int
     * }
     */
    protected static function getCaller(): ?array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($trace as $frame) {
            // Skip frames of this class
            if (isset($frame['class']) && $frame['class'] === self::class) {
                continue;
            }

            return [
                'class'    => $frame['class'] ?? null,
                'function' => $frame['function'],
                'line'     => $frame['line'] ?? null,
            ];
        }

        return null;
    }
}

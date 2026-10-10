<?php

declare(strict_types=1);

namespace App\Framework\Http;

use App\Framework\Errors\Error;
use App\Framework\Errors\ErrorCode;
use App\Framework\Support\StandardTypes;
use JsonException;
use RuntimeException;

/**
 * Immutable HTTP response returned by every controller action.
 *
 * Building one has no side effects; App::run() is the only place that sends status, headers and body. Property names
 * follow PSR-7's ResponseInterface, so an adapter can be added later if PSR-15 middleware is ever needed.
 * HTML responses are built by ViewRenderer, which needs the request's auth state.
 *
 * @phpstan-import-type PosInt from StandardTypes
 */
final readonly class Response
{
    /**
     * @param PosInt $status
     * @param array<string, string> $headers Lowercase, hyphenated names, e.g. `content-type`
     * @param string $body
     */
    public function __construct(
        public int $status,
        public array $headers,
        public string $body,
    ) {
    }

    public function withHeader(string $name, string $value): self
    {
        return new self(
            $this->status,
            [...$this->headers, strtolower($name) => $value],
            $this->body,
        );
    }

    /**
     * @param PosInt $status
     * @param array<string, mixed> $content
     */
    public static function json(int $status, array $content): self
    {
        return self::jsonBody($status, ['data' => $content]);
    }

    /**
     * @param PosInt $status
     * @param non-empty-list<Error> $errors
     */
    public static function jsonErrors(int $status, array $errors): self
    {
        return self::jsonBody($status, ['data' => ['errors' => $errors]]);
    }

    /**
     * @param PosInt $status
     */
    public static function jsonError(int $status, ErrorCode $code): self
    {
        return self::jsonErrors($status, [new Error($code)]);
    }

    /**
     * @param PosInt $status
     */
    public static function redirect(int $status, string $url): self
    {
        return new self($status, ['location' => $url], '');
    }

    public static function noContent(): self
    {
        return new self(204, [], '');
    }

    /**
     * @param PosInt $status
     * @param array<string, mixed> $content
     */
    private static function jsonBody(int $status, array $content): self
    {
        try {
            $body = json_encode($content, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Failed to encode JSON: ' . $e->getMessage(), 0, $e);
        }

        return new self($status, ['content-type' => 'application/json; charset=utf-8'], $body);
    }
}

<?php

declare(strict_types=1);

namespace App\Framework\Http;

use App\Framework\Support\Log;
use App\Framework\Support\StandardTypes;
use App\Framework\Support\Str;
use RequestParseBodyException;

/**
 * Immutable snapshot of the current HTTP request.
 *
 * Built once per request by fromGlobals(), the only place superglobals are read. Property names follow PSR-7's
 * ServerRequestInterface, so an adapter can be added later if PSR-15 middleware is ever needed.
 *
 * @phpstan-import-type Inputs from StandardTypes
 */
final readonly class Request
{
    /**
     * @param HttpMethod $method
     * @param non-empty-string $target Path and query string, e.g. `/list/london?page=2`
     * @param Inputs $query
     * @param Inputs $parsedBody
     * @param array<string, string> $headers Lowercase, hyphenated names, e.g. `x-forwarded-proto`
     * @param array<string, mixed> $serverParams
     */
    public function __construct(
        public HttpMethod $method,
        public string $target,
        public array $query = [],
        public array $parsedBody = [],
        public array $headers = [],
        public array $serverParams = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (is_string($value) && str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
            }
        }

        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = $_SERVER['REQUEST_URI'];

        assert(is_string($requestMethod), 'REQUEST_METHOD must be a string');
        assert(is_string($requestUri), 'REQUEST_URI must be a string');

        $method = HttpMethod::from(strtolower($requestMethod));

        return new self(
            $method,
            $requestUri ?: '/',
            $_GET,
            self::parseBodyFromGlobals($method),
            $headers,
            $_SERVER,
        );
    }

    /**
     * Request target without the query string, e.g. `/list/london`
     */
    public function path(): string
    {
        return explode('?', $this->target, 2)[0];
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * @return ?non-empty-string
     */
    public function ip(): ?string
    {
        $ip = $this->serverParams['REMOTE_ADDR'] ?? null;

        if (!is_string($ip)) {
            Log::warning('`REMOTE_ADDR` not set');
            return null;
        }

        return Str::nullIfEmpty($ip);
    }

    public function isXmlHttpRequest(): bool
    {
        return $this->header('x-requested-with') === 'XMLHttpRequest';
    }

    public function wantsJson(): bool
    {
        return str_contains($this->header('accept') ?? '', 'application/json');
    }

    /**
     * Body inputs for methods that carry a body, otherwise query string inputs.
     *
     * @return Inputs
     */
    public function inputs(): array
    {
        return $this->hasBody() ? $this->parsedBody : $this->query;
    }

    /**
     * @param non-empty-string $key
     * @return ?non-empty-string
     */
    public function formToken(string $key): ?string
    {
        $token = $this->inputs()[$key] ?? null;

        if (!is_string($token) || $token === '') {
            return null;
        }

        return $token;
    }

    /**
     * PHP only populates $_POST for POST, so form bodies of other methods are parsed on demand.
     *
     * @return Inputs
     */
    private static function parseBodyFromGlobals(HttpMethod $method): array
    {
        if ($method === HttpMethod::POST) {
            return $_POST;
        }

        if ($method !== HttpMethod::PUT && $method !== HttpMethod::PATCH && $method !== HttpMethod::DELETE) {
            return [];
        }

        try {
            [$parsedBody] = request_parse_body();
        } catch (RequestParseBodyException) {
            // Body missing, malformed, or not form-encoded
            return [];
        }

        /** @var Inputs */
        return $parsedBody;
    }

    private function hasBody(): bool
    {
        return $this->method == HttpMethod::POST
            || $this->method == HttpMethod::PUT
            || $this->method == HttpMethod::PATCH
            || $this->method == HttpMethod::DELETE;
    }
}

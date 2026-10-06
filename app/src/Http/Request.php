<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Enums\HttpMethod;
use App\Types\StandardTypes;
use App\Utils\Log;
use App\Utils\Str;

/**
 * @phpstan-import-type Inputs from StandardTypes
 */
class Request
{
    /**
     * @return ?non-empty-string
     */
    public static function ip(): ?string
    {
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            Log::warning('`REMOTE_ADDR` not set');
            return null;
        }

        return Str::nullIfEmpty($_SERVER['REMOTE_ADDR']);
    }

    public static function isXmlHttpRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
    }

    /**
     * @return non-empty-string
     */
    public static function getRequestTarget(): string
    {
        return $_SERVER['REQUEST_URI'] ?: '/';
    }

    public static function getMethod(): HttpMethod
    {
        return HttpMethod::from(strtolower($_SERVER['REQUEST_METHOD']));
    }

    public static function determineInputsType(): int
    {
        return self::shouldCheckPost() ? INPUT_POST : INPUT_GET;
    }

    /**
     * @return Inputs
     */
    public static function getInputs(): array
    {
        return self::shouldCheckPost() ? $_POST : $_GET;
    }

    /**
     * @param non-empty-string $key
     * @return ?non-empty-string
     */
    public static function getFormToken(string $key): ?string
    {
        $token = filter_input(
            self::determineInputsType(),
            $key,
            FILTER_DEFAULT
        );

        if (!is_string($token) || empty($token)) {
            return null;
        }

        return $token;
    }

    public static function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    protected static function shouldCheckPost(): bool
    {
        $method = self::getMethod();

        return $method == HttpMethod::POST
            || $method == HttpMethod::PUT
            || $method == HttpMethod::PATCH
            || $method == HttpMethod::DELETE;
    }
}

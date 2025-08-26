<?php

declare(strict_types=1);

namespace App\Http\Response;

use App\DTOs\Error;
use App\Enums\ErrorCode;
use App\Types\StandardTypes;
use RuntimeException;

/**
 * @phpstan-import-type PosInt from StandardTypes
 */
class JsonResponseBuilder
{
    /**
     * @param array<string, mixed> $content
     * @param PosInt $status
     * @return non-empty-string
     */
    public static function json(array $content, int $status = 200): string
    {
        $response = self::jsonResponse(
            [
                'data' => $content
            ],
            $status
        );

        /** @phpstan-var non-empty-string $response */
        return $response;
    }

    /**
     * @param non-empty-list<Error> $content
     * @param PosInt $status
     * @return non-empty-string
     */
    public static function errors(array $content, int $status = 400): string
    {
        $response = self::jsonResponse(
            [
                'data' => [
                    'errors' => $content,
                ]
            ],
            $status
        );

        /** @phpstan-var non-empty-string $response */
        return $response;
    }

    /**
     * @param ErrorCode $code
     * @param PosInt $status
     * @return non-empty-string
     */
    public static function error(ErrorCode $code, int $status = 400): string
    {
        return self::errors([new Error($code)], $status);
    }

    /**
     * @param array<string, mixed> $content
     * @param PosInt $status
     * @return string
     */
    protected static function jsonResponse(array $content, int $status = 200): string
    {
        $body = json_encode($content);

        if ($body === false) {
            throw new RuntimeException('Failed to encode JSON: ' . json_last_error_msg());
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8', true);

        return $body;
    }
}

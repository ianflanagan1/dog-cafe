<?php

declare(strict_types=1);

namespace App\Http\Response;

use App\DTOs\Error;
use App\Enums\ErrorCode;
use App\Http\DTOs\ViewParameters;
use App\Types\StandardTypes;

/**
 * @phpstan-import-type PosInt from StandardTypes
 */
class Response
{
    /**
     * @param array<string, mixed> $content
     * @param PosInt $status
     * @return non-empty-string
     */
    public static function json(array $content, int $status = 200): string
    {
        return JsonResponseBuilder::json($content, $status);
    }

    /**
     * @param non-empty-list<Error> $content
     * @param PosInt $status
     * @return non-empty-string
     */
    public static function jsonErrors(array $content, int $status = 400): string
    {
        return JsonResponseBuilder::errors($content, $status);
    }

    /**
     * @param ErrorCode $code
     * @param PosInt $status
     * @return non-empty-string
     */
    public static function jsonError(ErrorCode $code, int $status = 400): string
    {
        return JsonResponseBuilder::error($code, $status);
    }

    /**
     * @param PosInt $status
     * @return ViewParameters
     */
    public static function htmlError(int $status = 404): ViewParameters
    {
        return HtmlResponseBuilder::error($status);
    }

    public static function redirect(string $url = '/', int $status = 302): void
    {
        header('Location: ' . $url, true, $status);
        exit();
    }

    public static function empty(): string
    {
        http_response_code(204);
        return '';
    }

}

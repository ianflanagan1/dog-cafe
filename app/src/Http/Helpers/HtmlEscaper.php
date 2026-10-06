<?php

declare(strict_types=1);

namespace App\Http\Helpers;

use App\Utils\Log;

class HtmlEscaper
{
    public static function html(mixed $value): string
    {
        if (is_string($value)) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        Log::error('Couldn\'t escape value', $value);
        return '[invalid]';
    }

    public static function url(string $value): string
    {
        return rawurlencode($value);
    }

    public static function json(mixed $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP);
    }
}

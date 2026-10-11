<?php

declare(strict_types=1);

namespace App\Framework\View;

use App\Framework\Support\Log;

final class HtmlEscaper
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
}

<?php

declare(strict_types=1);

namespace App\Framework\Support;

final class Str
{
    /**
     * @param string $input
     * @return ?non-empty-string
     */
    public static function nullIfEmpty(string $input): ?string
    {
        return $input !== ''
            ? $input
            : null;
    }
}

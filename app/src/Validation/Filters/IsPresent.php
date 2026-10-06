<?php

declare(strict_types=1);

namespace App\Validation\Filters;

/**
 * Determines if a parameter (field) is present in the input.
 *
 * This callback is intended for use with FILTER_CALLBACK filters, returning `true` if the value is not null, indicating
 * the parameter was provided regardless of its actual value (including false, 0, empty string).
 */
class IsPresent
{
    public static function filter(mixed $value): bool
    {
        return $value !== null;
    }
}

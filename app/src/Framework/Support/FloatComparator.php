<?php

declare(strict_types=1);

namespace App\Framework\Support;

final class FloatComparator
{
    private const float EPSILON = 0.0000001;

    public static function lessThan(float $a, float $b, float $epsilon = self::EPSILON): bool
    {
        return $a < $b - $epsilon;
    }

    public static function greaterThan(float $a, float $b, float $epsilon = self::EPSILON): bool
    {
        return $a > $b + $epsilon;
    }
}

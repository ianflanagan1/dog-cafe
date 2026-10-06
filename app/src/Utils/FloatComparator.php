<?php

declare(strict_types=1);

namespace App\Utils;

class FloatComparator
{
    protected const float EPSILON = 0.0000001;

    public static function equalish(float $a, float $b, float $epsilon = self::EPSILON): bool
    {
        return abs($a - $b) <= $epsilon;
    }

    public static function lessThan(float $a, float $b, float $epsilon = self::EPSILON): bool
    {
        return $a < $b - $epsilon;
    }

    public static function greaterThan(float $a, float $b, float $epsilon = self::EPSILON): bool
    {
        return $a > $b + $epsilon;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Traits;

use ReflectionClass;

trait ConstantAccessTrait
{
    /**
     * @param class-string $class
     * @param non-empty-string|list<non-empty-string> $const
     * @return mixed
     */
    protected static function getConstantValue(string $class, string|array $const): mixed
    {
        $reflection = new ReflectionClass($class);

        if (is_string($const)) {
            return $reflection->getConstant($const);
        }

        $array = [];

        foreach($const as $c) {
            $array[] = $reflection->getConstant($c);
        }

        return $array;
    }
}

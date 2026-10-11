<?php

declare(strict_types=1);

namespace App\Framework\Support;

trait QueryStringConversionsTrait
{
    /**
     * @param string $names
     * @param non-empty-string $separator
     * @return list<self>
     */
    public static function fromCommaSeparatedString(string $names, string $separator = ','): array
    {
        if ($names === '') {
            return [];
        }

        $cases = [];
        $validNames = array_map(strtolower(...), array_column(self::cases(), 'name'));

        foreach (explode($separator, strtolower($names)) as $name) {
            $index = array_search($name, $validNames, true);

            if ($index !== false) {
                $cases[] = self::cases()[$index];
            }
        }

        return $cases;
    }
}

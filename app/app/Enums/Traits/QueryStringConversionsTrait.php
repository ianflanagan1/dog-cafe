<?php

declare(strict_types=1);

namespace App\Enums\Traits;

trait QueryStringConversionsTrait
{
    /**
     * @param string $names
     * @param non-empty-string $separator
     * @return list<self>
     */
    public static function fromCommaSeparatedString(string $names, string $separator = ','): array
    {
        if ($names == '') {
            return [];
        }

        $cases = [];
        $names = explode($separator, strtolower($names));

        $validNames = array_column(self::cases(), 'name');

        foreach ($names as $name) {
            $index = array_search($name, array_map('strtolower', $validNames));

            if ($index !== false) {
                $cases[] = self::{$validNames[$index]};
            }
        }

        return $cases;
    }
}

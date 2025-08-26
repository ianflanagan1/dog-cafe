<?php

declare(strict_types=1);

namespace App\Utils;

class FilePermissions
{
    /**
     * Convert decimal (int) to octal string.
     */
    public static function toOctal(int $permissions): string
    {
        return sprintf('%04o', $permissions & 0777);
    }

    /**
     * Convert file permissions to symbolic string (e.g., rwxr-xr--).
     */
    public static function toSymbolic(int $permissions): string
    {
        $symbolic = '';
        $masks = [
            0b100 => 'r',
            0b010 => 'w',
            0b001 => 'x',
        ];

        for ($i = 2; $i >= 0; $i--) {
            $bits = ($permissions >> ($i * 3)) & 0b111;
            foreach ($masks as $mask => $char) {
                $symbolic .= ($bits & $mask) ? $char : '-';
            }
        }

        return $symbolic;
    }

    /**
     * Full representation: octal and symbolic.
     */
    public static function describe(int $permissions): string
    {
        return self::toOctal($permissions) . ' (' . self::toSymbolic($permissions) . ')';
    }
}

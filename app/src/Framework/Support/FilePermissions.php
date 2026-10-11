<?php

declare(strict_types=1);

namespace App\Framework\Support;

final class FilePermissions
{
    /**
     * Convert decimal (int) to octal string.
     */
    public static function toOctal(int $permissions): string
    {
        return sprintf('%04o', $permissions & 0777);
    }
}

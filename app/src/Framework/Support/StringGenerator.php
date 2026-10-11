<?php

declare(strict_types=1);

namespace App\Framework\Support;

/**
 * Cryptographically secure random strings, e.g. for tokens or unguessable names.
 */
final class StringGenerator
{
    /**
     * @param positive-int $bytes Entropy; the result is twice this many characters
     * @return non-empty-string
     */
    public static function hex(int $bytes): string
    {
        return bin2hex(random_bytes($bytes));
    }
}

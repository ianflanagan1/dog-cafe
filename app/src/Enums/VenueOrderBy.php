<?php

declare(strict_types=1);

namespace App\Enums;

enum VenueOrderBy: int
{
    case Smart  = 1;
    case NearTo = 2;
    /**
     * @return non-empty-string
     */
    public function getString(): string
    {
        return match ($this) {
            self::Smart     => 'smart_sort',
            self::NearTo    => '( a^2 + b^2 ))',
        }; // TODO: Currently this is unused and ineffective
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use DateTimeImmutable;
use DateTimeZone;

enum Locality
{
    case UnitedKingdom;

    public function timezone(): DateTimeZone
    {
        return match ($this) {
            self::UnitedKingdom => new DateTimeZone('Europe/London'),
        };
    }

    /**
     * Current local wall-clock time, including daylight saving.
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone());
    }
}

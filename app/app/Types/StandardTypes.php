<?php

declare(strict_types=1);

namespace App\Types;

/**
 * @phpstan-type PosInt int<1, max>
 * @phpstan-type NonNegInt int<0, max>
 * @phpstan-type MapZoom int<0, 22>
 * @phpstan-type Inputs array<string, mixed>
 * @phpstan-type Validated array<string, mixed>
 * @phpstan-type DayOfWeek int<0,6>
 * @phpstan-type Permissions int<0, 0777>
 */
class StandardTypes
{
}

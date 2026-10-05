<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Locality;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LocalityTest extends TestCase
{
    /**
     * @return array<string, array{non-empty-string, non-empty-string}>
     */
    public static function ukProvider(): array
    {
        return [
            'winter: GMT, same as UTC'          => ['2026-01-15 09:30:00', '2026-01-15 09:30 4'],
            'summer: BST, UTC+1'                => ['2026-07-15 09:30:00', '2026-07-15 10:30 3'],
            'summer: UTC late evening is next day' => ['2026-07-18 23:30:00', '2026-07-19 00:30 0'],
        ];
    }

    #[DataProvider('ukProvider')]
    public function test_uk_converts_utc_to_local_wall_clock(string $utc, string $expected): void
    {
        $instant = new DateTimeImmutable($utc, new DateTimeZone('UTC'));

        $local = $instant->setTimezone(Locality::UnitedKingdom->timezone());

        $this->assertSame($expected, $local->format('Y-m-d H:i w'));
    }

    public function test_now_is_in_locality_timezone(): void
    {
        $this->assertSame('Europe/London', Locality::UnitedKingdom->now()->getTimezone()->getName());
    }
}

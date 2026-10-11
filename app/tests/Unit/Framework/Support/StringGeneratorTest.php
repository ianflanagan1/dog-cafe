<?php

declare(strict_types=1);

namespace Tests\Unit\Framework\Support;

use App\Framework\Support\StringGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StringGeneratorTest extends TestCase
{
    /**
     * @return array<non-empty-string, array{positive-int}>
     */
    public static function byte_counts(): array
    {
        return [
            'One byte'      => [1],
            'Eight bytes'   => [8],
            '32 bytes'      => [32],
        ];
    }

    /**
     * @param positive-int $bytes
     */
    #[DataProvider('byte_counts')]
    public function test_hex_encodes_the_given_number_of_bytes_as_two_lowercase_hex_characters_each(int $bytes): void
    {
        $this->assertMatchesRegularExpression('/^[0-9a-f]{' . ($bytes * 2) . '}$/', StringGenerator::hex($bytes));
    }

    public function test_hex_returns_a_different_value_each_call(): void
    {
        $this->assertNotSame(StringGenerator::hex(16), StringGenerator::hex(16));
    }
}

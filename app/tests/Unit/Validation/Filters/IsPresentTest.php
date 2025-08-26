<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Filters;

use PHPUnit\Framework\TestCase;
use App\Validation\Filters\IsPresent;
use PHPUnit\Framework\Attributes\DataProvider;

class IsPresentTest extends TestCase{
    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          value: mixed,
     *      }
     * >
     */
    public static function cases_to_test_rule_passes(): array
    {
        return [
            'Non-empty string' => [
                'value' => 'abcd',
            ],
            'Empty string' => [
                'value' => '',
            ],
            'Int' => [
                'value' => 4,
            ],
            'Float' => [
                'value' => 4.1,
            ],
            'True' => [
                'value' => true,
            ],
            'False' => [
                'value' => false,
            ],
            'Array' => [
                'value' => ['a', 'b'],
            ],
            'Nested array' => [
                'value' => [['a', 'b'], ['a', 'b']],
            ],
        ];
    }

    #[DataProvider('cases_to_test_rule_passes')]
    public function test_rule_passes(mixed $value): void
    {
        $result = (new IsPresent())->filter($value);

        $this->assertTrue($result);
    }

    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          value: mixed,
     *      }
     * >
     */
    public static function cases_to_test_rule_fails(): array
    {
        return [
            'Null' => [
                'value' => null,
            ],
        ];
    }

    #[DataProvider('cases_to_test_rule_fails')]
    public function test_rule_fails(mixed $value): void
    {
        $result = (new IsPresent())->filter($value);

        $this->assertFalse($result);
    }
}

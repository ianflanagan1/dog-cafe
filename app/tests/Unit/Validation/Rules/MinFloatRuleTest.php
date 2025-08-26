<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Rules;

use App\DTOs\Error;
use App\Enums\ErrorCode;
use App\Validation\Rules\MinFloatRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MinFloatRuleTest extends TestCase
{
    protected const string FIELD = 'field';

    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          limit: float,
     *          value: float,
     *      }
     * >
     */
    public static function cases_to_test_rule_passes(): array
    {
        return [
            'Positive integer limit; equal value' => [
                'limit' => 5,
                'value' => 5,
            ],
            'Positive integer limit; higher value' => [
                'limit' => 5,
                'value' => 5.1,
            ],
            'Positive float limit; equal value' => [
                'limit' => 5.5,
                'value' => 5.5,
            ],
            'Positive float limit; higher value' => [
                'limit' => 5.5,
                'value' => 5.6,
            ],
            'Positive float limit; value lower by epsilon' => [
                'limit' => 5.5,
                'value' => 5.49999999999999,
            ],
            'Zero limit; equal value' => [
                'limit' => 0,
                'value' => 0,
            ],
            'Zero limit; higher value' => [
                'limit' => 0,
                'value' => 1.1,
            ],
            'Negative integer limit; equal value' => [
                'limit' => -2,
                'value' => -2,
            ],
            'Negative integer limit; higher value' => [
                'limit' => -2,
                'value' => -1,
            ],
            'Negative float limit; equal value' => [
                'limit' => -2.5,
                'value' => -2.5,
            ],
            'Negative float limit; higher value' => [
                'limit' => -2.5,
                'value' => -1.2,
            ],
        ];
    }

    #[DataProvider('cases_to_test_rule_passes')]
    public function test_rule_passes(float $limit, float $value): void
    {
        $result = (new MinFloatRule($limit))->validate(self::FIELD, $value);

        $this->assertNull($result);
    }

    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          limit: float,
     *          value: float,
     *      }
     * >
     */
    public static function cases_to_test_rule_fails(): array
    {
        return [
            'Positive integer limit, lower value' => [
                'limit' => 5,
                'value' => 4.9,
            ],
            'Positive float limit, lower value' => [
                'limit' => 5.5,
                'value' => 5.4,
            ],
            'Zero limit, lower value' => [
                'limit' => 0,
                'value' => -0.2,
            ],
            'Negative integer limit, lower value' => [
                'limit' => -2,
                'value' => -2.2,
            ],
            'Negative float limit, lower value' => [
                'limit' => -2.5,
                'value' => -2.7,
            ],
        ];
    }

    #[DataProvider('cases_to_test_rule_fails')]
    public function test_rule_fails(float $limit, float $value): void
    {
        $result = (new MinFloatRule($limit))->validate(self::FIELD, $value);

        $this->assertInstanceOf(Error::class, $result);
        $this->assertSame(ErrorCode::ParameterTooSmall, $result->code);
        $this->assertStringContainsString(self::FIELD, $result->message);
        $this->assertStringContainsString((string) $limit, $result->message);
    }
}

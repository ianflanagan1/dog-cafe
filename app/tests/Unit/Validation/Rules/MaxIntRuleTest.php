<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Rules;

use App\DTOs\Error;
use App\Enums\ErrorCode;
use PHPUnit\Framework\TestCase;
use App\Validation\Rules\MaxIntRule;
use PHPUnit\Framework\Attributes\DataProvider;

class MaxIntRuleTest extends TestCase
{
    protected const string FIELD = 'field';

    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          limit: int,
     *          value: int,
     *      }
     * >
     */
    public static function cases_to_test_rule_passes(): array
    {
        return [
            'Positive limit; equal value' => [
                'limit' => 5,
                'value' => 5,
            ],
            'Positive  limit; lower value' => [
                'limit' => 5,
                'value' => 4,
            ],
            'Zero limit; equal value' => [
                'limit' => 0,
                'value' => 0,
            ],
            'Zero limit; lower value' => [
                'limit' => 0,
                'value' => -1,
            ],
            'Negative limit; equal value' => [
                'limit' => -2,
                'value' => -2,
            ],
            'Negative limit; lower value' => [
                'limit' => -2,
                'value' => -4,
            ],
        ];
    }

    #[DataProvider('cases_to_test_rule_passes')]
    public function test_rule_passes(int $limit, int $value): void
    {
        $result = (new MaxIntRule($limit))->validate(self::FIELD, $value);

        $this->assertNull($result);
    }

    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          limit: int,
     *          value: int,
     *      }
     * >
     */
    public static function cases_to_test_rule_fails(): array
    {
        return [
            'Positive  limit, higher value' => [
                'limit' => 5,
                'value' => 6,
            ],
            'Zero limit, higher value' => [
                'limit' => 0,
                'value' => 2,
            ],
            'Negative  limit, higher value' => [
                'limit' => -2,
                'value' => 6,
            ],
        ];
    }

    #[DataProvider('cases_to_test_rule_fails')]
    public function test_rule_fails(int $limit, int $value): void
    {
        $result = (new MaxIntRule($limit))->validate(self::FIELD, $value);

        $this->assertInstanceOf(Error::class, $result);
        $this->assertSame(ErrorCode::ParameterTooLarge, $result->code);
        $this->assertStringContainsString(self::FIELD, $result->message);
        $this->assertStringContainsString((string) $limit, $result->message);
    }
}

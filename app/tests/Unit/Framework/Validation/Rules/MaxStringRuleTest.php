<?php

declare(strict_types=1);

namespace Tests\Unit\Framework\Validation\Rules;

use App\Framework\Errors\Error;
use App\Framework\Errors\ErrorCode;
use App\Framework\Validation\Rules\MaxStringRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MaxStringRuleTest extends TestCase
{
    protected const string FIELD = 'field';

    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          limit: int,
     *          value: string,
     *      }
     * >
     */
    public static function cases_to_test_rule_passes(): array
    {
        return [
            'Positive limit; equal value' => [
                'limit' => 5,
                'value' => '12345',
            ],
            'Positive  limit; lower value' => [
                'limit' => 5,
                'value' => '1234',
            ],
        ];
    }

    #[DataProvider('cases_to_test_rule_passes')]
    public function test_rule_passes(int $limit, string $value): void
    {
        $result = new MaxStringRule($limit)->validate(self::FIELD, $value);

        $this->assertNull($result);
    }

    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          limit: int,
     *          value: string,
     *      }
     * >
     */
    public static function cases_to_test_rule_fails(): array
    {
        return [
            'Positive  limit, higher value' => [
                'limit' => 5,
                'value' => '123456',
            ],
        ];
    }

    #[DataProvider('cases_to_test_rule_fails')]
    public function test_rule_fails(int $limit, string $value): void
    {
        $result = new MaxStringRule($limit)->validate(self::FIELD, $value);

        $this->assertInstanceOf(Error::class, $result);
        $this->assertSame(ErrorCode::ParameterTooLong, $result->code);
        $this->assertStringContainsString(self::FIELD, $result->message);
        $this->assertStringContainsString((string) $limit, $result->message);
    }
}

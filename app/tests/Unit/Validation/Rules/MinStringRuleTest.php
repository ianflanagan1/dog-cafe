<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Rules;

use App\DTOs\Error;
use App\Enums\ErrorCode;
use PHPUnit\Framework\TestCase;
use App\Validation\Rules\MinStringRule;
use PHPUnit\Framework\Attributes\DataProvider;

class MinStringRuleTest extends TestCase
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
            'Positive  limit; higher value' => [
                'limit' => 5,
                'value' => '123456',
            ],
        ];
    }

    #[DataProvider('cases_to_test_rule_passes')]
    public function test_rule_passes(int $limit, string $value): void
    {
        $result = (new MinStringRule($limit))->validate(self::FIELD, $value);

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
            'Positive  limit, lower value' => [
                'limit' => 5,
                'value' => '1234',
            ],
        ];
    }

    #[DataProvider('cases_to_test_rule_fails')]
    public function test_rule_fails(int $limit, string $value): void
    {
        $result = (new MinStringRule($limit))->validate(self::FIELD, $value);

        $this->assertInstanceOf(Error::class, $result);
        $this->assertSame(ErrorCode::ParameterTooShort, $result->code);
        $this->assertStringContainsString(self::FIELD, $result->message);
        $this->assertStringContainsString((string) $limit, $result->message);
    }
}

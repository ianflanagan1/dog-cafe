<?php

declare(strict_types=1);

namespace Tests\Unit\Framework\Validation\Rules;

use App\Framework\Errors\Error;
use App\Framework\Errors\ErrorCode;
use App\Framework\Validation\Rules\RequiredRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RequiredRuleTest extends TestCase
{
    protected const string FIELD = 'field';

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
        $result = (new RequiredRule())->validate(self::FIELD, $value);

        $this->assertNull($result);
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
        $result = (new RequiredRule())->validate(self::FIELD, $value);

        $this->assertInstanceOf(Error::class, $result);
        $this->assertSame(ErrorCode::RequiredParameterMissing, $result->code);
        $this->assertStringContainsString(self::FIELD, $result->message);
    }
}

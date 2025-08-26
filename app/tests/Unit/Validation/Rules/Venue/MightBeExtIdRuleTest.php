<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Rules\Venue;

use App\DTOs\Error;
use App\Enums\ErrorCode;
use PHPUnit\Framework\TestCase;
use App\Validation\Rules\Venue\MightBeExtIdRule;
use PHPUnit\Framework\Attributes\DataProvider;

class MightBeExtIdRuleTest extends TestCase
{
    protected const string FIELD = 'field';

    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          value: string,
     *      }
     * >
     */
    public static function cases_to_test_rule_passes()
    {
        return [
            '22-character alpha-numeric string' => [
                'value' => 'abcdefghijklmnopqrstuv',
            ],
        ];
    }

    #[DataProvider('cases_to_test_rule_passes')]
    public function test_rule_passes(string $value): void
    {
        $result = (new MightBeExtIdRule())->validate(self::FIELD, $value);

        $this->assertNull($result);
    }

    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          value: string,
     *      }
     * >
     */
    public static function cases_to_test_rule_fails()
    {
        return [
            '21-character alpha-numeric string' => [
                'value' => 'abcdefghijklmnopqrstu',
            ],
            '23-character alpha-numeric string' => [
                'value' => 'abcdefghijklmnopqrstuvw',
            ],
            '22-character non-alpha-numeric string' => [
                'value' => 'a-cdefghijklmnopqrstuv',
            ],
        ];
    }

    #[DataProvider('cases_to_test_rule_fails')]
    public function test_rule_fails(string $value): void
    {
        $result = (new MightBeExtIdRule())->validate(self::FIELD, $value);

        $this->assertInstanceOf(Error::class, $result);
        $this->assertSame(ErrorCode::ParameterInvalidValue, $result->code);
        $this->assertStringContainsString(self::FIELD, $result->message);
    }
}

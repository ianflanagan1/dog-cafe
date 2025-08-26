<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use App\Exceptions\InputValidationException;
use App\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          input: mixed,
     *          output: int,
     *      }
     * >
     */
    public static function cases_to_test_filter_integer(): array
    {
        return [
            'Valid' => [
                'input' => '1',
                'output' => 1,
            ],
            'Below min' => [
                'input' => '0',
                'output' => 6,
            ],
            'Above max' => [
                'input' => '10',
                'output' => 6,
            ],
            'Float' => [
                'input' => '2.1',
                'output' => 6,
            ],
            'Alphabetic' => [
                'input' => 'abcd 10',
                'output' => 6,
            ],
            'Empty string' => [
                'input' => '',
                'output' => 6,
            ],
            'Null' => [
                'input' => null,
                'output' => 6,
            ],
            'Array' => [
                'input' => ['a', 'b'],
                'output' => 6,
            ],
        ];
    }

    #[DataProvider('cases_to_test_filter_integer')]
    public function test_filter_integer(mixed $input, int $output): void
    {
        $expectedInputs = [
            'a' => [
                'filter' => [
                    'type' => FILTER_VALIDATE_INT,
                    'options' => [
                        'min_range' => 1,
                        'max_range' => 9,
                    ],
                    'default' => 6,
                ],
            ],
        ];

        $inputs = ['a' => $input];
        $outputs = ['a' => $output];

        $validated = (new Validator)->handle($inputs, $expectedInputs);

        $this->assertSame($outputs, $validated);
    }

    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          input: mixed,
     *          output: float,
     *      }
     * >
     */
    public static function cases_to_test_filter_float(): array
    {
        return [
            'Valid' => [
                'input' => '1.1',
                'output' => 1.1,
            ],
            'Below min' => [
                'input' => '0',
                'output' => 6.2,
            ],
            'Above max' => [
                'input' => '10.2',
                'output' => 6.2,
            ],
            'Alphabetic' => [
                'input' => 'abcd 10',
                'output' => 6.2,
            ],
            'Empty string' => [
                'input' => '',
                'output' => 6.2,
            ],
            'Null' => [
                'input' => null,
                'output' => 6.2,
            ],
            'Array' => [
                'input' => ['a', 'b'],
                'output' => 6.2,
            ],
        ];
    }

    #[DataProvider('cases_to_test_filter_float')]
    public function test_filter_float(mixed $input, float $output): void
    {
        $expectedInputs = [
            'a' => [
                'filter' => [
                    'type' => FILTER_VALIDATE_FLOAT,
                    'options' => [
                        'min_range' => 1,
                        'max_range' => 9,
                    ],
                    'default' => 6.2,
                ],
            ],
        ];

        $inputs = ['a' => $input];
        $outputs = ['a' => $output];

        $validated = (new Validator)->handle($inputs, $expectedInputs);

        $this->assertSame($outputs, $validated);
    }

    /**
     * @return array<
     *      non-empty-string,
     *      array{
     *          options: mixed,
     *          default: mixed,
     *          input: mixed,
     *          output: mixed,
     *      }
     * >
     */
    public static function cases_to_test_filter_callback(): array
    {
        return [
            'Enum tryFrom: match' => [
                'options' => [CallbackEnum::class, 'tryFrom'],
                'default' => CallbackEnum::CASE_CC,
                'input' => 'bb',
                'output' => CallbackEnum::CASE_BB,
            ],
            'Enum tryFrom: default' => [
                'options' => [CallbackEnum::class, 'tryFrom'],
                'default' => CallbackEnum::CASE_CC,
                'input' => 'dd',
                'output' => CallbackEnum::CASE_CC,
            ],
            'Enum arrow function: match' => [
                'options' => fn (string $value): ?CallbackEnum => CallbackEnum::tryFrom($value),
                'default' => CallbackEnum::CASE_CC,
                'input' => 'bb',
                'output' => CallbackEnum::CASE_BB,
            ],
            'Enum arrow function: default' => [
                'options' => fn (string $value): ?CallbackEnum => CallbackEnum::tryFrom($value),
                'default' => CallbackEnum::CASE_CC,
                'input' => 'dd',
                'output' => CallbackEnum::CASE_CC,
            ],
            'Class callable' => [
                'options' => [CallbackClass::class, 'fromString'],
                'default' => null,
                'input' => '11',
                'output' => new CallbackClass(11),
            ],
        ];
    }

    #[DataProvider('cases_to_test_filter_callback')]
    public function test_filter_callback(mixed $options, mixed $default, mixed $input, mixed $output): void
    {
        $expectedInputs = [
            'a' => [
                'filter' => [
                    'type' => FILTER_CALLBACK,
                    'options' => $options,
                    'default' => $default,
                ],
            ],
        ];

        $inputs = ['a' => $input];
        $outputs = ['a' => $output];

        $validated = (new Validator)->handle($inputs, $expectedInputs);

        $this->assertEquals($outputs, $validated);
    }

    public function test_with_no_properties(): void
    {
        $expectedInputs = [
            'a',
        ];

        $inputs = ['a' => 'abc'];
        $outputs = ['a' => 'abc'];

        $validated = (new Validator)->handle($inputs, $expectedInputs);

        $this->assertSame($outputs, $validated);
    }

    public function test_throws_input_validation_exception_on_rule_failure(): void
    {
        $expectedInputs = [
            'a' => [
                'rules' => ['required'],
            ],
        ];

        $inputs = ['a' => null];

        $this->expectException(InputValidationException::class);

        $validated = (new Validator)->handle($inputs, $expectedInputs);
    }
}

enum CallbackEnum: string
{
    case CASE_AA = 'aa';
    case CASE_BB = 'bb';
    case CASE_CC = 'cc';
}

class CallbackClass
{
    public function __construct(public int $property) {}

    public static function fromString(string $input): self
    {
        return new self((int) $input);
    }
}

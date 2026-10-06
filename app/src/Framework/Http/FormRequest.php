<?php

declare(strict_types=1);

namespace App\Framework\Http;

use App\Framework\Support\StandardTypes;
use App\Framework\Validation\Validator;

/**
 * @phpstan-import-type Validated from StandardTypes
 * @phpstan-import-type ExpectedInputs from Validator
 */
abstract class FormRequest extends Request
{
    /** @var ExpectedInputs */
    protected const array EXPECTED_INPUTS = [];
    public function __construct(
        protected Validator $filter,
    ) {
    }

    /**
     * @return Validated
     */
    public static function validate(): array
    {
        if (empty(static::EXPECTED_INPUTS)) {
            return [];
        }

        return Validator::handle(
            self::getInputs(),
            static::EXPECTED_INPUTS,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Validation\Rules\Venue;

use App\DTOs\Error;
use App\Enums\ErrorCode;
use App\Models\Venue;
use App\Types\StandardTypes;
use App\Validation\Rules\RuleInterface;
use InvalidArgumentException;

/**
 * @phpstan-import-type PosInt from StandardTypes
*/
class MightBeExtIdRule implements RuleInterface
{
    /**
     * @param non-empty-string $field
     * @param mixed $value
     * @return ?Error
     */
    public function validate(string $field, mixed $value): ?Error
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException();
        }

        if (!Venue::stringMightBeExtId($value)) {
            return new Error(ErrorCode::ParameterInvalidValue, ['field' => $field]);
        }

        return null;
    }
}

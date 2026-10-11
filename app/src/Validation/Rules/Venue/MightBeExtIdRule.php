<?php

declare(strict_types=1);

namespace App\Validation\Rules\Venue;

use App\Framework\Errors\Error;
use App\Framework\Errors\ErrorCode;
use App\Framework\Support\StandardTypes;
use App\Framework\Validation\Rules\RuleInterface;
use App\Models\Venue;
use InvalidArgumentException;
use Override;

/**
 * @phpstan-import-type PosInt from StandardTypes
*/
final class MightBeExtIdRule implements RuleInterface
{
    #[Override]
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

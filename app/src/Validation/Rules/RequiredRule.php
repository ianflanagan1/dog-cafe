<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\DTOs\Error;
use App\Enums\ErrorCode;

class RequiredRule implements RuleInterface
{
    public function validate(string $field, mixed $value): ?Error
    {
        if ($value === null) {
            return new Error(ErrorCode::RequiredParameterMissing, ['field' => $field]);
        }

        return null;
    }
}

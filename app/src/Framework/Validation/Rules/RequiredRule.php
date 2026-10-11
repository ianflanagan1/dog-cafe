<?php

declare(strict_types=1);

namespace App\Framework\Validation\Rules;

use App\Framework\Errors\Error;
use App\Framework\Errors\ErrorCode;
use Override;

final class RequiredRule implements RuleInterface
{
    #[Override]
    public function validate(string $field, mixed $value): ?Error
    {
        if ($value === null) {
            return new Error(ErrorCode::RequiredParameterMissing, ['field' => $field]);
        }

        return null;
    }
}

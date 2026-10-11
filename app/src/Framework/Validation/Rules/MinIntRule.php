<?php

declare(strict_types=1);

namespace App\Framework\Validation\Rules;

use App\Framework\Errors\Error;
use App\Framework\Errors\ErrorCode;
use InvalidArgumentException;
use Override;

final class MinIntRule implements RuleInterface
{
    public function __construct(private int $min)
    {
    }

    #[Override]
    public function validate(string $field, mixed $value): ?Error
    {
        if (!is_int($value)) {
            throw new InvalidArgumentException();
        }

        if ($value < $this->min) {
            return new Error(ErrorCode::ParameterTooSmall, ['field' => $field, 'parameter' => $this->min]);
        }

        return null;
    }
}

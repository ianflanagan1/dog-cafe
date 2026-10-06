<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\DTOs\Error;
use App\Enums\ErrorCode;
use InvalidArgumentException;

class MaxIntRule implements RuleInterface
{
    public function __construct(protected int $max)
    {
    }

    /**
     * @param non-empty-string $field
     * @param mixed $value
     * @return ?Error
     */
    public function validate(string $field, mixed $value): ?Error
    {
        if (!is_int($value)) {
            throw new InvalidArgumentException();
        }

        if ($value > $this->max) {
            return new Error(ErrorCode::ParameterTooLarge, ['field' => $field, 'parameter' => $this->max]);
        }

        return null;
    }
}

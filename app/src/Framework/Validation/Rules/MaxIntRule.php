<?php

declare(strict_types=1);

namespace App\Framework\Validation\Rules;

use App\Framework\Errors\Error;
use App\Framework\Errors\ErrorCode;
use InvalidArgumentException;

final class MaxIntRule implements RuleInterface
{
    public function __construct(private int $max)
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

<?php

declare(strict_types=1);

namespace App\Framework\Validation\Rules;

use App\Framework\Errors\Error;
use App\Framework\Errors\ErrorCode;
use App\Framework\Support\FloatComparator;
use InvalidArgumentException;

class MaxFloatRule implements RuleInterface
{
    public function __construct(protected float $max)
    {
    }

    /**
     * @param non-empty-string $field
     * @param mixed $value
     * @return ?Error
     */
    public function validate(string $field, mixed $value): ?Error
    {
        if (!is_float($value)) {
            throw new InvalidArgumentException();
        }

        if (FloatComparator::greaterThan($value, $this->max)) {
            return new Error(ErrorCode::ParameterTooLarge, ['field' => $field, 'parameter' => $this->max]);
        }

        return null;
    }
}

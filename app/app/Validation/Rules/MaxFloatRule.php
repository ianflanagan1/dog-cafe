<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\DTOs\Error;
use App\Enums\ErrorCode;
use App\Utils\FloatComparator;
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

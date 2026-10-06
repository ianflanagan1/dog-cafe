<?php

declare(strict_types=1);

namespace App\Framework\Validation\Rules;

use App\Framework\Errors\Error;
use App\Framework\Errors\ErrorCode;
use App\Framework\Support\FloatComparator;
use InvalidArgumentException;

class MinFloatRule implements RuleInterface
{
    public function __construct(protected float $min)
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

        if (FloatComparator::lessThan($value, $this->min)) {
            return new Error(ErrorCode::ParameterTooSmall, ['field' => $field, 'parameter' => $this->min]);
        }

        return null;
    }
}

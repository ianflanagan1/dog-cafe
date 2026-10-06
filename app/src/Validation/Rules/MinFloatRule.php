<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\DTOs\Error;
use App\Enums\ErrorCode;
use App\Utils\FloatComparator;
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

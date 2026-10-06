<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\DTOs\Error;
use App\Enums\ErrorCode;
use InvalidArgumentException;

class MinStringRule implements RuleInterface
{
    public function __construct(protected int $min)
    {
        if ($this->min < 1) {
            throw new InvalidArgumentException();
        }
    }

    /**
     * @param non-empty-string $field
     * @param mixed $value
     * @return ?Error
     */
    public function validate(string $field, mixed $value): ?Error
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException();
        }

        if (!is_string($value) || strlen($value) < $this->min) {
            $min = $this->min;
            $min .= $min > 1 ? ' characters' : ' character';
            return new Error(ErrorCode::ParameterTooShort, ['field' => $field, 'parameter' => $min]);
        }

        return null;
    }
}

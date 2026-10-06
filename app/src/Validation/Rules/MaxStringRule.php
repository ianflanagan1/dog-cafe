<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\DTOs\Error;
use App\Enums\ErrorCode;
use InvalidArgumentException;

class MaxStringRule implements RuleInterface
{
    public function __construct(protected int $max)
    {
        if ($this->max < 1) {
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

        if (strlen($value) > $this->max) {
            $max = $this->max;
            $max .= $max > 1 ? ' characters' : ' character';
            return new Error(ErrorCode::ParameterTooLong, ['field' => $field, 'parameter' => $max]);
        }

        return null;
    }
}

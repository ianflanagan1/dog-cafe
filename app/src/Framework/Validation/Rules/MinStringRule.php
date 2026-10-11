<?php

declare(strict_types=1);

namespace App\Framework\Validation\Rules;

use App\Framework\Errors\Error;
use App\Framework\Errors\ErrorCode;
use InvalidArgumentException;
use Override;

final class MinStringRule implements RuleInterface
{
    public function __construct(private int $min)
    {
        if ($this->min < 1) {
            throw new InvalidArgumentException();
        }
    }

    #[Override]
    public function validate(string $field, mixed $value): ?Error
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException();
        }

        if (strlen($value) < $this->min) {
            $min = $this->min;

            $min .= $min > 1
                ? ' characters'
                : ' character';

            return new Error(ErrorCode::ParameterTooShort, ['field' => $field, 'parameter' => $min]);
        }

        return null;
    }
}

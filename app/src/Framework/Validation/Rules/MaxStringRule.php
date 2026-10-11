<?php

declare(strict_types=1);

namespace App\Framework\Validation\Rules;

use App\Framework\Errors\Error;
use App\Framework\Errors\ErrorCode;
use InvalidArgumentException;
use Override;

final class MaxStringRule implements RuleInterface
{
    public function __construct(private int $max)
    {
        if ($this->max < 1) {
            throw new InvalidArgumentException();
        }
    }

    #[Override]
    public function validate(string $field, mixed $value): ?Error
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException();
        }

        if (strlen($value) > $this->max) {
            $max = $this->max;

            $max .= $max > 1
                ? ' characters'
                : ' character';

            return new Error(ErrorCode::ParameterTooLong, ['field' => $field, 'parameter' => $max]);
        }

        return null;
    }
}

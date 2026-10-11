<?php

declare(strict_types=1);

namespace App\Framework\Validation\Rules;

use App\Framework\Errors\Error;
use App\Framework\Errors\ErrorCode;
use InvalidArgumentException;
use Override;

final class MaxIntRule implements RuleInterface
{
    public function __construct(private int $max)
    {
    }

    #[Override]
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

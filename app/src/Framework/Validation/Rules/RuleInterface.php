<?php

declare(strict_types=1);

namespace App\Framework\Validation\Rules;

use App\Framework\Errors\Error;

interface RuleInterface
{
    /**
     * Validate a value. Return an Error if invalid, or null if valid.
     *
     * @param string $field
     * @param mixed $value
     * @return ?Error
     */
    public function validate(string $field, mixed $value): ?Error;
}

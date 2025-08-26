<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\DTOs\Error;

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

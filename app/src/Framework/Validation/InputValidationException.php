<?php

declare(strict_types=1);

namespace App\Framework\Validation;

use App\Framework\Errors\Error;
use App\Framework\Support\StandardTypes;
use Exception;

/**
 * @phpstan-import-type Inputs from StandardTypes
 */
final class InputValidationException extends Exception
{
    /**
     * @param Inputs $inputs
     * @param non-empty-list<Error> $errors
     */
    public function __construct(
        public readonly array $inputs,
        public readonly array $errors,
    ) {
        parent::__construct('Input validation failed', 422);
    }
}

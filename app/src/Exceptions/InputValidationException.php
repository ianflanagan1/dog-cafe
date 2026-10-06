<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\DTOs\Error;
use App\Types\StandardTypes;
use Exception;

/**
 * @phpstan-import-type Inputs from StandardTypes
 */
class InputValidationException extends Exception
{
    /**
     * @param Inputs $inputs
     * @param non-empty-list<Error> $errors
     */
    public function __construct(
        protected readonly array $inputs,
        protected readonly array $errors,
    ) {
        parent::__construct("Input validation failed", 422);
    }

    /**
     * @return Inputs
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * @return non-empty-list<Error>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

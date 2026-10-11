<?php

declare(strict_types=1);

namespace App\Framework\Http;

use App\Framework\Support\StandardTypes;
use App\Framework\Validation\Validator;

/**
 * @phpstan-import-type Validated from StandardTypes
 * @phpstan-import-type ExpectedInputs from Validator
 */
abstract readonly class FormRequest
{
    /** @var ExpectedInputs */
    protected const array EXPECTED_INPUTS = [];

    public function __construct(
        protected Request $request,
    ) {
    }

    /**
     * @return Validated
     */
    public function validate(): array
    {
        if (static::EXPECTED_INPUTS === []) {
            return [];
        }

        return Validator::handle(
            $this->request->inputs(),
            static::EXPECTED_INPUTS,
        );
    }

    /**
     * @return ?non-empty-string
     */
    public function ip(): ?string
    {
        return $this->request->ip();
    }

    /**
     * @param non-empty-string $key
     * @return ?non-empty-string
     */
    public function formToken(string $key): ?string
    {
        return $this->request->formToken($key);
    }
}

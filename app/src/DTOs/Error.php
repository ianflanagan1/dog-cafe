<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ErrorCode;

readonly class Error
{
    public string $message;

    /**
     * A value object representing a user-visible error with a machine-readable code.
     *
     * @param  ErrorCode  $code  The numerical error code.
     * @param  array<string, scalar>  $placeholders  Array of placeholders with text to inject into the human-readable error message.
     */
    public function __construct(
        public ErrorCode $code,
        array $placeholders = [],
    ) {
        $this->message = $this->code->message($placeholders);
    }

    /**
     * @return array{code: int, message: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code->value,
            'message' => $this->message,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

use RuntimeException;
use Stringable;

enum ErrorCode: int
{
    case RequiredParameterMissing = 1;
    case ParameterInvalidValue = 2;
    case ParameterTooShort = 3;
    case ParameterTooLong = 4;
    case ParameterTooSmall = 6;
    case ParameterTooLarge = 7;
    case ItemNotFound = 5;
    case NotLoggedIn = 8;
    case SystemError = 9;
    /**
     * @param  array<string, scalar|Stringable>  $placeholders  Array of placeholders with text to inject into the message.
     */
    public function message(array $placeholders = []): string
    {
        $message = match ($this) {
            self::RequiredParameterMissing => '`:field` is required',
            self::ParameterInvalidValue => 'Value given for `:field` is not valid',
            self::ParameterTooShort => '`:field` can\'t be shorter than :parameter',
            self::ParameterTooLong => '`:field` can\'t be longer than :parameter',
            self::ParameterTooSmall => '`:field` can\'t be smaller than :parameter',
            self::ParameterTooLarge => '`:field` can\'t be larger than :parameter',
            self::ItemNotFound => 'Item not found',
            self::NotLoggedIn => 'Not logged in',
            self::SystemError => 'System error',
        };

        foreach ($placeholders as $key => $value) {
            $message = str_replace(":$key", (string) $value, $message);
        }

        // Check for missing placeholders
        if (preg_match('/:[a-zA-Z0-9_]+/', $message, $matches)) {
            //  :           Literal (colon)
            //  a-zA-Z0-9   Alpha-numeric character
            //  _           Literal (underscore)
            //  +           One or more such characters

            $missing = implode(', ', $matches);
            throw new RuntimeException("Missing placeholders in error message: {$missing}");
        }

        return $message;
    }
}

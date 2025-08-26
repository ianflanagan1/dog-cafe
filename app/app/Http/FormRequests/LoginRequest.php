<?php

declare(strict_types=1);

namespace App\Http\FormRequests;

/**
 * @phpstan-type LoginValidated array{
 *      location: ?string,
 * }
 */
class LoginRequest extends FormRequest
{
    protected const array EXPECTED_INPUTS = [
        'location',
    ];
}

<?php

declare(strict_types=1);

namespace App\Http\FormRequests;

use App\Framework\Http\FormRequest;

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

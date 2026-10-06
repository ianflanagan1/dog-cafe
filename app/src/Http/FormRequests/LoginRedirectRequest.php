<?php

declare(strict_types=1);

namespace App\Http\FormRequests;

/**
 * @phpstan-type LoginRedirectValidated array{
 *      code: string,
 * }
 */
class LoginRedirectRequest extends FormRequest
{
    protected const array EXPECTED_INPUTS = [
        'code',
    ];
}

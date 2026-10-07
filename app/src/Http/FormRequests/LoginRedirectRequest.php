<?php

declare(strict_types=1);

namespace App\Http\FormRequests;

use App\Framework\Http\FormRequest;

/**
 * @phpstan-type LoginRedirectValidated array{
 *      code: string,
 * }
 */
readonly class LoginRedirectRequest extends FormRequest
{
    protected const array EXPECTED_INPUTS = [
        'code',
    ];
}

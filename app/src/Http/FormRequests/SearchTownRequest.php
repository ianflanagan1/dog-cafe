<?php

declare(strict_types=1);

namespace App\Http\FormRequests;

use App\Framework\Http\FormRequest;

/**
 * @phpstan-type SearchTownValidated array{
 *      search: string,
 * }
 */
final readonly class SearchTownRequest extends FormRequest
{
    protected const array EXPECTED_INPUTS = [
        'search' => [
            'rules' => ['required', 'min:1', 'max:100'],
        ]
    ];
}

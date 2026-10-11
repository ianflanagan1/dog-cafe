<?php

declare(strict_types=1);

namespace App\Http\FormRequests;

use App\Framework\Http\FormRequest;

/**
 * @phpstan-type SuggestValidated array{
 *      suggest: string,
 * }
 */
final readonly class SuggestRequest extends FormRequest
{
    protected const array EXPECTED_INPUTS = [
        'suggest' => [
            'filter' => [
                'type' => FILTER_UNSAFE_RAW,
                'options'   => null,
                'default'   => null,
            ],
            'rules' => ['required', 'min:1', 'max:10000'],
        ],
    ];
}

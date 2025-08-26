<?php

declare(strict_types=1);

namespace App\Http\FormRequests;

/**
 * @phpstan-type SuggestValidated array{
 *      suggest: string,
 * }
 */
class SuggestRequest extends FormRequest
{
    protected const array EXPECTED_INPUTS = [
        'suggest' => [
            'filter' => [
                'type' => FILTER_DEFAULT,
                'options'   => null,
                'default'   => null,
            ],
            'rules' => ['required', 'min:1', 'max:10000'],
        ],
    ];
}

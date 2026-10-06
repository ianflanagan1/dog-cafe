<?php

declare(strict_types=1);

namespace App\Http\FormRequests;

/**
 * @phpstan-type FavValidated array{
 *      ext_id: non-empty-string,
 * }
 */
class FavRequest extends FormRequest
{
    protected const array EXPECTED_INPUTS = [
        'ext_id' => [
            'rules' => ['required', 'extid']
        ]
    ];
}

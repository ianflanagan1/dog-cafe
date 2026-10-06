<?php

declare(strict_types=1);

namespace App\Http\FormRequests;

/**
 * @phpstan-type VenueShortValidated array{
 *      ext_id: non-empty-string,
 * }
 */
class VenueShortRequest extends FormRequest
{
    protected const array EXPECTED_INPUTS = [
        'ext_id' => [
            'filter' => [
                'type' => FILTER_DEFAULT,
                'options'   => null,
                'default'   => null,
            ],
            'rules' => ['required', 'extid'],
        ]
    ];
}

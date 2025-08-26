<?php

declare(strict_types=1);

namespace App\Http\FormRequests;

use App\Enums\VenueType;
use App\Validation\Filters\IsPresent;

/**
 * @phpstan-type MapValidated array{
 *      types: list<VenueType>,
 *      open_now: bool,
 * }
 */
class MapRequest extends FormRequest
{
    protected const array EXPECTED_INPUTS = [
        'types' => [
            'filter' => [
                'type' => FILTER_CALLBACK,
                'options'   => [VenueType::class, 'fromCommaSeparatedString'],
                'default'   => [],
            ],
        ],
        'open_now' => [
            'filter' => [
                'type' => FILTER_CALLBACK,
                'options'   => [IsPresent::class, 'filter'],
                'default'   => false,
            ],
        ],
    ];
}

<?php

declare(strict_types=1);

namespace App\Http\FormRequests;

use App\Enums\VenueType;
use App\Types\StandardTypes;
use App\Validation\Filters\IsPresent;

/**
 * @phpstan-import-type MapZoom from StandardTypes
 *
 * @phpstan-type SearchMapValidated array{
 *      lat1: float,
 *      lat2: float,
 *      lng1: float,
 *      lng2: float,
 *      zoom: MapZoom,
 *      types: list<VenueType>,
 *      open_now: bool,
 *      timestamp: ?int
 * }
 */
class SearchMapRequest extends FormRequest
{
    protected const array EXPECTED_INPUTS = [
        'lat1' => [
            'filter' => [
                'type' => FILTER_VALIDATE_FLOAT,
            ],
            'rules' => ['required', 'min:-90', 'max:90'],
        ],
        'lat2' => [
            'filter' => [
                'type' => FILTER_VALIDATE_FLOAT,
            ],
            'rules' => ['required', 'min:-90', 'max:90' ],
        ],
        'lng1' => [
            'filter' => [
                'type' => FILTER_VALIDATE_FLOAT,
            ],
            'rules' => ['required', 'min:-180', 'max:180' ],
        ],
        'lng2' => [
            'filter' => [
                'type' => FILTER_VALIDATE_FLOAT,
            ],
            'rules' => ['required', 'min:-180', 'max:180' ],
        ],
        'zoom' => [
            'filter' => [
                'type' => FILTER_VALIDATE_INT,
            ],
            'rules' => ['required', 'min:0', 'max:22'],
        ],
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
        'timestamp' => [
            'filter' => [
                'type' => FILTER_VALIDATE_INT,
            ],
        ],
    ];
}

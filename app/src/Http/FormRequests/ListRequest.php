<?php

declare(strict_types=1);

namespace App\Http\FormRequests;

use App\Enums\VenueOrderBy;
use App\Enums\VenueType;
use App\Types\StandardTypes;
use App\Validation\Filters\IsPresent;

/**
 * @phpstan-import-type PosInt from StandardTypes
 *
 * @phpstan-type ListValidated array{
 *      page: PosInt,
 *      types: list<VenueType>,
 *      open_now: bool,
 *      order_by: VenueOrderBy,
 *      timestamp: ?int,
 * }
 */
class ListRequest extends FormRequest
{
    protected const array EXPECTED_INPUTS = [
        'page' => [
            'filter' => [
                'type'      => FILTER_VALIDATE_INT,
                'options'   => [
                    'min_range' => 1,
                ],
                'default'   => 1,
            ],
        ],
        'types' => [
            'filter' => [
                'type'      => FILTER_CALLBACK,
                'options'   => [VenueType::class, 'fromCommaSeparatedString'],
                'default'   => [],
            ],
        ],
        'open_now' => [
            'filter' => [
                'type'      => FILTER_CALLBACK,
                'options'   => [IsPresent::class, 'filter'],
                'default'   => false,
            ],
        ],
        'order_by' => [
            'filter' => [
                'type'      => FILTER_CALLBACK,
                'options'   => [VenueOrderBy::class, 'tryFrom'],
                'default'   => VenueOrderBy::Smart,
            ],
        ],
        'timestamp' => [
            'filter' => [
                'type' => FILTER_VALIDATE_INT,
            ],
        ],
    ];
}

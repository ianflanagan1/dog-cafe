<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\VenueType;
use App\Http\Attributes\Get;
use App\Http\DTOs\ViewParameters;
use App\Http\FormRequests\MapRequest;
use App\Http\FormRequests\SearchMapRequest;
use App\Http\Response\Response;
use App\Resolvers\TownResolver;
use App\MapPoints\MapPointsService;

/**
 * @phpstan-import-type MapValidated from MapRequest
 * @phpstan-import-type SearchMapValidated from SearchMapRequest
 */
readonly class MapController extends Controller
{
    #[Get('/map/:townStr')]
    /**
     * Render map page, starting centred on the resolved town.
     */
    public function map(TownResolver $townResolver, string $townStr): ViewParameters
    {
        $town = $townResolver->resolve($townStr);

        /** @var MapValidated $validated */
        $validated = MapRequest::validate();

        $isType = fn (VenueType $t): bool => in_array($t, $validated['types'], true);

        return new ViewParameters(
            'main',
            'map',
            'map',
            [
                'search'    => true,
                'canonical' => '/map',
            ],
            [
                'canonical' => '/map',
            ],
            [
                'town'          => $town,
                'cafe'          => $isType(VenueType::Cafe),
                'restaurant'    => $isType(VenueType::Restaurant),
                'bar'           => $isType(VenueType::Bar),
                'open_now'      => $validated['open_now'],
            ],
            200,
        );
    }

    #[Get('/api/v1/search-map')]
    /**
     * Return Venues (single and clustered points) for a given map rectangle and filters.
     */
    public function searchMap(MapPointsService $mapPointsService): string
    {
        /** @var SearchMapValidated $validated */
        $validated = SearchMapRequest::validate();

        $points = $mapPointsService->get(
            $validated['lat1'],
            $validated['lat2'],
            $validated['lng1'],
            $validated['lng2'],
            $validated['types'],
            $validated['open_now'],
            $validated['zoom'],
        );

        return Response::json([
            'points'    => $points,
            'timestamp' => $validated['timestamp'],
        ], 200);
    }
}

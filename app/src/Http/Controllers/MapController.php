<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\VenueType;
use App\Framework\Http\Controller;
use App\Framework\Http\Response\Response;
use App\Framework\Routing\Attributes\Get;
use App\Framework\View\ViewParameters;
use App\Http\FormRequests\MapRequest;
use App\Http\FormRequests\SearchMapRequest;
use App\MapPoints\MapPointsService;
use App\Services\TownResolver;

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
    public function map(MapRequest $request, TownResolver $townResolver, string $townStr): ViewParameters
    {
        $town = $townResolver->resolve($townStr, $request->ip());

        /** @var MapValidated $validated */
        $validated = $request->validate();

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
    public function searchMap(SearchMapRequest $request, MapPointsService $mapPointsService): string
    {
        /** @var SearchMapValidated $validated */
        $validated = $request->validate();

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

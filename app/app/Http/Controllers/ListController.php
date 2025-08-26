<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\VenueType;
use App\Exceptions\NotFoundException;
use App\Http\Attributes\Get;
use App\Http\DTOs\ListPageUrls;
use App\Http\DTOs\ViewParameters;
use App\Http\FormRequests\ListRequest;
use App\Http\Response\Response;
use App\Models\Venue;
use App\Repositories\TownRepository;
use App\Resolvers\TownResolver;
use App\Services\ListVenuePageService;

/**
 * @phpstan-import-type ListValidated from ListRequest
 * @phpstan-import-type VenueMinimalFromDatabase from Venue
 */
readonly class ListController extends Controller
{
    public const int PAGE_SIZE = 20;
    protected const string HTML_ROUTE = '/list';
    protected const string API_ROUTE = '/api/v1/search-list';

    #[Get('/:townStr')]
    #[Get(self::HTML_ROUTE . '/:townStr')]
    /**
     * Render a page showing a list of Venues in a particular town according to specified filters.
     */
    public function listHtml(TownResolver $townResolver, ListVenuePageService $listVenuePageService, string $townStr): ViewParameters
    {
        $town = $townResolver->resolve($townStr);

        /** @var ListValidated $validated */
        $validated = ListRequest::validate();

        [$totalPages, $venues] = $listVenuePageService->get(
            $town->id,
            $validated['page'],
            self::PAGE_SIZE,
            $validated['types'],
            $validated['open_now'],
            $validated['order_by'],
        );

        // Generate "current", "next", and "prev" URLs for pagination
        $urls = ListPageUrls::from(
            self::HTML_ROUTE,
            $totalPages,
            $validated['page'],
            $validated['types'],
            $validated['open_now'],
            $town->nameUrl,
        );

        $isType = fn (VenueType $t): bool => in_array($t, $validated['types'], true);

        return new ViewParameters(
            'main',
            'list',
            'list',
            [
                'search'    => true,
                'canonical' => $urls->canonical,
            ],
            [
                'urls'      => $urls,
                'pageTitle' => "{$town->name}'s Dog-Friendly Cafes!",
            ],
            [
                'page'          => $validated['page'],
                'totalPages'    => $totalPages,
                'town'          => $town,
                'cafe'          => $isType(VenueType::Cafe),
                'restaurant'    => $isType(VenueType::Restaurant),
                'bar'           => $isType(VenueType::Bar),
                'open_now'      => $validated['open_now'],
                'urls'          => $urls,
                'venues'        => $venues,
            ],
            200,
        );
    }

    #[Get(self::API_ROUTE . '/:townStr')]
    /**
     * Return list of Venues for lazy-loading the Venues list page.
     */
    public function listJson(TownRepository $townRepository, ListVenuePageService $listVenuePageService, string $townStr): string
    {
        $town = filter_var($townStr, FILTER_CALLBACK, [
            'options' => [$townRepository, 'findByNameUrlLower']
        ]);

        if ($town === false) {
            throw new NotFoundException();
        }

        /** @var ListValidated $validated */
        $validated = ListRequest::validate();

        [$totalPages, $venues] = $listVenuePageService->get(
            $town->id,
            $validated['page'],
            self::PAGE_SIZE,
            $validated['types'],
            $validated['open_now'],
            $validated['order_by'],
        );

        return Response::json([
            'items'         => array_map(fn (array $v): array => ListVenuePageService::processForJson($v), $venues),
            'totalPages'    => $totalPages,
            'timestamp'     => $validated['timestamp'],
        ], 200);
    }
}

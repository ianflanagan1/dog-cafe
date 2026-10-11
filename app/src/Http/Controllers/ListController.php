<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\VenueType;
use App\Framework\Errors\NotFoundException;
use App\Framework\Http\Controller;
use App\Framework\Http\Response;
use App\Framework\Routing\Attributes\Get;
use App\Framework\View\ViewRenderer;
use App\Http\DTOs\ListPageUrls;
use App\Http\FormRequests\ListRequest;
use App\Models\Venue;
use App\Repositories\TownRepository;
use App\Services\ListVenuePageService;
use App\Services\TownResolver;

/**
 * @phpstan-import-type ListValidated from ListRequest
 * @phpstan-import-type VenueMinimalFromDatabase from Venue
 */
final readonly class ListController extends Controller
{
    public const int PAGE_SIZE = 20;
    protected const string HTML_ROUTE = '/list';
    protected const string API_ROUTE = '/api/v1/search-list';

    #[Get('/:townStr')]
    #[Get(self::HTML_ROUTE . '/:townStr')]
    /**
     * Render a page showing a list of Venues in a particular town according to specified filters.
     */
    public function listHtml(
        ListRequest $request,
        ViewRenderer $viewRenderer,
        TownResolver $townResolver,
        ListVenuePageService $listVenuePageService,
        string $townStr,
    ): Response {
        $town = $townResolver->resolve($townStr, $request->ip());

        /** @var ListValidated $validated */
        $validated = $request->validate();

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

        return $viewRenderer->render(
            status: 200,
            page: 'list',
            layoutParameters: [
                'search'    => true,
                'canonical' => $urls->canonical,
            ],
            headParameters: [
                'urls'      => $urls,
                'pageTitle' => "{$town->name}'s Dog-Friendly Cafes!",
            ],
            bodyParameters: [
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
        );
    }

    #[Get(self::API_ROUTE . '/:townStr')]
    /**
     * Return list of Venues for lazy-loading the Venues list page.
     */
    public function listJson(ListRequest $request, TownRepository $townRepository, ListVenuePageService $listVenuePageService, string $townStr): Response
    {
        $town = filter_var($townStr, FILTER_CALLBACK, [
            'options' => $townRepository->findByNameUrlLower(...),
        ]);

        if ($town === false) {
            throw new NotFoundException();
        }

        /** @var ListValidated $validated */
        $validated = $request->validate();

        [$totalPages, $venues] = $listVenuePageService->get(
            $town->id,
            $validated['page'],
            self::PAGE_SIZE,
            $validated['types'],
            $validated['open_now'],
            $validated['order_by'],
        );

        return Response::json(200, [
            'items'         => array_map(fn (array $v): array => ListVenuePageService::processForJson($v), $venues),
            'totalPages'    => $totalPages,
            'timestamp'     => $validated['timestamp'],
        ]);
    }
}

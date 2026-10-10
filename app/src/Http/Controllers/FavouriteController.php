<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locality;
use App\Framework\Errors\NotFoundException;
use App\Framework\Errors\SystemException;
use App\Framework\Http\Controller;
use App\Framework\Http\Response;
use App\Framework\Routing\Attributes\Delete;
use App\Framework\Routing\Attributes\Get;
use App\Framework\Routing\Attributes\Post;
use App\Framework\Session\Auth;
use App\Framework\View\ViewRenderer;
use App\Http\DTOs\ListPageUrls;
use App\Http\FormRequests\FavRequest;
use App\Http\FormRequests\ListRequest;
use App\Models\Venue;
use App\Repositories\VenueHydrator;
use App\Repositories\VenueRepository;
use App\Session\FavouriteSessionStore;

/**
 * @phpstan-import-type ListValidated from ListRequest
 * @phpstan-import-type FavValidated from FavRequest
 */
readonly class FavouriteController extends Controller
{
    public const int PAGE_SIZE = 20;
    protected const string HTML_ROUTE = '/favs';
    protected const string API_ROUTE = '/api/v1/search-favs';

    #[Get(self::HTML_ROUTE)]
    /**
     * Render a page showing a list of Venues favourited by the logged-in user.
     */
    public function favsHtml(
        ListRequest $request,
        ViewRenderer $viewRenderer,
        Auth $auth,
        VenueRepository $venueRepository,
        FavouriteSessionStore $favouriteSessionStore,
    ): Response {
        $auth->guard(true);

        /** @var ListValidated $validated */
        $validated = $request->validate();

        [$totalPages, $venues] = $venueRepository->getVenuePageRawForFavs(
            $favouriteSessionStore->getAll(),
            $validated['page'],
            self::PAGE_SIZE,
            $validated['types'],
            $validated['open_now'],
            $validated['order_by'],
        );

        $urls = ListPageUrls::from(
            self::HTML_ROUTE,
            $totalPages,
            $validated['page'],
            $validated['types'],
            $validated['open_now'],
        );

        $venues = array_map(fn (array $v): array => VenueHydrator::processTypes($v), $venues);
        $venues = array_map(fn (array $v): array => VenueHydrator::processOpeningTimes($v, Locality::UnitedKingdom), $venues);

        return $viewRenderer->render(
            status: 200,
            page: 'favs',
            layoutParameters: [
                'search'    => false,
                'canonical' => $urls->canonical,
            ],
            headParameters: [
                'urls'      => $urls,
            ],
            bodyParameters: [
                'page'          => $validated['page'],
                'totalPages'    => $totalPages,
                'urls'          => $urls,
                'venues'        => $venues,
            ],
        );
    }

    #[Get(self::API_ROUTE)]
    /**
     * Return list of Venues for lazy-loading the Favourites page for the logged-in user.
     */
    public function fansJson(
        ListRequest $request,
        Auth $auth,
        VenueRepository $venueRepository,
        FavouriteSessionStore $favouriteSessionStore,
    ): Response {
        $auth->guard();

        /** @var ListValidated $validated */
        $validated = $request->validate();

        [$totalPages, $venues] = $venueRepository->getVenuePageRawForFavs(
            $favouriteSessionStore->getAll(),
            $validated['page'],
            self::PAGE_SIZE,
            $validated['types'],
            $validated['open_now'],
            $validated['order_by'],
        );

        $venues = array_map(fn (array $v): array => VenueHydrator::processTypes($v), $venues);
        $venues = array_map(fn (array $v): array => VenueHydrator::processOpeningTimes($v, Locality::UnitedKingdom), $venues);

        return Response::json(200, [
            'items'         => $venues,
            'totalPages'    => $totalPages,
            'timestamp'     => $validated['timestamp'],
        ]);
    }

    #[Post('/api/v1/favs')]
    /**
     * Add a favourite Venue for the logged-in user.
     */
    public function favAdd(
        FavRequest $request,
        Auth $auth,
        FavouriteSessionStore $favouriteSessionStore,
        VenueRepository $venueRepository,
    ): Response {
        $auth->guard();

        /** @var FavValidated $validated */
        $validated = $request->validate();
        $id = $venueRepository->findIdByExtId($validated['ext_id']);

        if ($id === null) {
            throw new NotFoundException();
        }

        if (!$favouriteSessionStore->add($id, $validated['ext_id'])) {
            throw new SystemException();
        }

        return Response::json(201, ['success' => true]);
    }

    #[Delete('/api/v1/favs/:extId')]
    /**
     * Delete a favourite Venue for the logged-in user.
     */
    public function favDel(
        Auth $auth,
        FavouriteSessionStore $favouriteSessionStore,
        VenueRepository $venueRepository,
        string $extId,
    ): Response {
        $auth->guard();

        if (!Venue::stringMightBeExtId($extId)) {
            throw new NotFoundException();
        }

        /** @var non-empty-string $extId */

        $id = $venueRepository->findIdByExtId($extId);

        if ($id === null) {
            throw new NotFoundException();
        }

        $favouriteSessionStore->delete($id);

        return Response::noContent();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\NotFoundException;
use App\Exceptions\SystemException;
use App\Http\Attributes\Delete;
use App\Http\Attributes\Get;
use App\Http\Attributes\Post;
use App\Http\DTOs\ListPageUrls;
use App\Http\DTOs\ViewParameters;
use App\Http\FormRequests\FavRequest;
use App\Http\FormRequests\ListRequest;
use App\Http\Response\Response;
use App\Hydrators\VenueHydrator;
use App\Models\Venue;
use App\Repositories\VenueRepository;
use App\Session\Auth;
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
        Auth $auth,
        VenueRepository $venueRepository,
        FavouriteSessionStore $favouriteSessionStore,
    ): ViewParameters {
        $auth->guard(true);

        /** @var ListValidated $validated */
        $validated = ListRequest::validate();

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
        $venues = array_map(fn (array $v): array => VenueHydrator::processOpeningTimes($v), $venues);

        return new ViewParameters(
            'main',
            'favs',
            'favs',
            [
                'search'    => false,
                'canonical' => $urls->canonical,
            ],
            [
                'urls'      => $urls,
            ],
            [
                'page'          => $validated['page'],
                'totalPages'    => $totalPages,
                'urls'          => $urls,
                'venues'        => $venues,
            ],
            200,
        );
    }

    #[Get(self::API_ROUTE)]
    /**
     * Return list of Venues for lazy-loading the Favourites page for the logged-in user.
     */
    public function fansJson(
        Auth $auth,
        VenueRepository $venueRepository,
        FavouriteSessionStore $favouriteSessionStore,
    ): string {
        $auth->guard();

        /** @var ListValidated $validated */
        $validated = ListRequest::validate();

        [$totalPages, $venues] = $venueRepository->getVenuePageRawForFavs(
            $favouriteSessionStore->getAll(),
            $validated['page'],
            self::PAGE_SIZE,
            $validated['types'],
            $validated['open_now'],
            $validated['order_by'],
        );

        $venues = array_map(fn (array $v): array => VenueHydrator::processTypes($v), $venues);
        $venues = array_map(fn (array $v): array => VenueHydrator::processOpeningTimes($v), $venues);

        return Response::json([
            'items'         => $venues,
            'totalPages'    => $totalPages,
            'timestamp'     => $validated['timestamp'],
        ], 200);
    }

    #[Post('/api/v1/favs')]
    /**
     * Add a favourite Venue for the logged-in user.
     */
    public function favAdd(
        Auth $auth,
        FavouriteSessionStore $favouriteSessionStore,
        VenueRepository $venueRepository,
    ): string {
        $auth->guard();

        /** @var FavValidated $validated */
        $validated = FavRequest::validate();
        $id = $venueRepository->findIdByExtId($validated['ext_id']);

        if ($id === null) {
            throw new NotFoundException();
        }

        if (!$favouriteSessionStore->add($id, $validated['ext_id'])) {
            throw new SystemException();
        }

        return Response::json(['success' => true], 201);
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
    ): string {
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

        return Response::empty();
    }
}

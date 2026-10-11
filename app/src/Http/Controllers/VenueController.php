<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locality;
use App\Framework\Errors\NotFoundException;
use App\Framework\Http\Controller;
use App\Framework\Http\Response;
use App\Framework\Routing\Attributes\Get;
use App\Framework\Routing\RouteNotFoundException;
use App\Framework\Session\Auth;
use App\Framework\View\ViewRenderer;
use App\Http\FormRequests\VenueShortRequest;
use App\Models\Venue;
use App\Repositories\VenueHydrator;
use App\Repositories\VenueRepository;
use App\Session\FavouriteSessionStore;

/**
 * @phpstan-import-type VenueShortValidated from VenueShortRequest
 */

final readonly class VenueController extends Controller
{
    #[Get('/api/v1/venue-short')]
    /**
     * Return the minimal details of a venue, when the user selects a Venue on the map.
     */
    public function venueMinimal(VenueShortRequest $request, VenueRepository $venueRepository): Response
    {
        /** @var VenueShortValidated */
        $validated = $request->validate();

        $row = $venueRepository->findMinimalByExtId($validated['ext_id']);

        if ($row === null) {
            throw new NotFoundException();
        }

        $venue = VenueHydrator::processTypes($row);
        $venue = VenueHydrator::processOpeningTimes($venue, Locality::UnitedKingdom);

        return Response::json(200, [
            'venue' => $venue,
        ]);
    }

    #[Get('/venue/:venueReference')]
    /**
     * Render a details page for a particular Venue.
     */
    public function venueFull(
        ViewRenderer $viewRenderer,
        Auth $auth,
        VenueRepository $venueRepository,
        FavouriteSessionStore $favouriteSessionStore,
        string $venueReference,
    ): Response {
        $venue = null;

        // If reference fits the pattern of an external ID, check database for it
        if (Venue::stringMightBeExtId($venueReference)) {
            /** @phpstan-var non-empty-string $venueReference */
            $venue = $venueRepository->findFullByExtId($venueReference);

            // Otherwise, if reference fits the pattern of a human-readable url, check database for it
        } elseif (Venue::stringMightBeHumanUrl($venueReference)) {
            /** @phpstan-var non-empty-string $venueReference */
            $venue = $venueRepository->findFullByHumanUrl($venueReference);
        }

        // Otherwise, or if not found, respond 404
        if ($venue === null) {
            throw new RouteNotFoundException();
        }

        $venue = VenueHydrator::processFull(
            $venue,
            $favouriteSessionStore->getAll(),
        );

        $canonical = '/venue/' . $venue['human_url'];

        return $viewRenderer->render(
            status: 200,
            page: 'venue',
            layoutParameters: [
                'search'    => true,
                'canonical' => $canonical,
            ],
            headParameters: [
                'canonical' => $canonical,
                'name'      => $venue['name'],
            ],
            bodyParameters: [
                'venue'             => $venue,
                'loginRedirectUrl'  => $auth->isLoggedIn() ? null : urlencode($canonical),
            ],
        );
    }
}

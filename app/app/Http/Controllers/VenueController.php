<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\NotFoundException;
use App\Exceptions\RouteNotFoundException;
use App\Http\Attributes\Get;
use App\Http\DTOs\ViewParameters;
use App\Http\FormRequests\VenueShortRequest;
use App\Http\Response\Response;
use App\Hydrators\VenueHydrator;
use App\Models\Venue;
use App\Repositories\VenueRepository;
use App\Session\Auth;
use App\Session\FavouriteSessionStore;

/**
 * @phpstan-import-type VenueShortValidated from VenueShortRequest
 */

readonly class VenueController extends Controller
{
    #[Get('/api/v1/venue-short')]
    /**
     * Return the minimal details of a venue, when the user selects a Venue on the map.
     */
    public function venueMinimal(VenueRepository $venueRepository): string
    {
        /** @var VenueShortValidated $validated */
        $validated = VenueShortRequest::validate();

        $venue = $venueRepository->findMinimalByExtId($validated['ext_id']);

        if ($venue === null) {
            throw new NotFoundException();
        }

        $venue = VenueHydrator::processTypes($venue);
        $venue = VenueHydrator::processOpeningTimes($venue);

        return Response::json([
            'venue' => $venue,
        ], 200);
    }

    #[Get('/venue/:venueReference')]
    /**
     * Render a details page for a particular Venue.
     */
    public function venueFull(
        Auth $auth,
        VenueRepository $venueRepository,
        FavouriteSessionStore $favouriteSessionStore,
        string $venueReference,
    ): ViewParameters {
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

        return new ViewParameters(
            'main',
            'venue',
            'venue',
            [
                'search'    => true,
                'canonical' => $canonical,
            ],
            [
                'canonical' => $canonical,
                'name'      => $venue['name'],
            ],
            [
                'venue'             => $venue,
                'loginRedirectUrl'  => $auth->isLoggedIn() ? null : urlencode($canonical),
            ],
            200,
        );
    }
}

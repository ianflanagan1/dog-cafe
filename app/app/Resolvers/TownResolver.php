<?php

declare(strict_types=1);

namespace App\Resolvers;

use App\Exceptions\RouteNotFoundException;
use App\Models\Town;
use App\Repositories\AppUserRepository;
use App\Repositories\TownRepository;
use App\Services\IpLocatorService;
use App\Services\TownGeoService;
use App\Session\TownSessionStore;

readonly class TownResolver
{
    public function __construct(
        protected TownGeoService $townGeoService,
        protected TownRepository $townRepository,
        protected TownSessionStore $townSessionStore,
        protected AppUserRepository $appUserRepository,
    ) {
    }

    /**
     * Return a town for the List or Map views.
     *
     * Selection order:
     * 1. The town specified in the request URL
     * 2. The last town specified by the user (in List or Map views)
     * 3. The town closest to the user's IP address (UK only)
     * 4. The default town (London)
     */
    public function resolve(string $townStr): Town
    {
        // If a town was specified in the request, return it
        if (!empty($townStr)) {
            $town = $this->townRepository->findByNameUrlLower($townStr);

            if ($town !== null) {
                $this->townSessionStore->saveLastSpecifiedTown($town);
                return $town;
            }

            // If an invalid town was specified, return 404
            throw new RouteNotFoundException();
        }

        // Otherwise, from cache, try to return the town last specified by the user
        $town = $this->townSessionStore->findLastSpecifiedTown();

        if ($town !== null) {
            return $town;
        }

        // Otherwise, from cache, try to return the closest town to the user's IP address
        $town = $this->townSessionStore->findIpTown();

        if ($town !== null) {
            return $town;
        }

        // Otherwise, for UK users, try to get IP address and return closest town
        $location = IpLocatorService::find();

        if ($location !== null && IpLocatorService::isInServedArea($location['countryCode'])) {
            $town = $this->townGeoService->getClosestTown($location['lat'], $location['lng']);
            $this->townSessionStore->saveIpTown($town);
            return $town;
        }

        // Otherwise, return default town (London)
        $town = $this->townRepository->getDefault();
        $this->townSessionStore->saveIpTown($town);

        return $town;
    }
}

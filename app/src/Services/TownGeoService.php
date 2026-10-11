<?php

declare(strict_types=1);

namespace App\Services;

use App\Framework\Support\Geo;
use App\Models\Town;
use App\Repositories\TownRepository;

/**
 * Service for geospatial operations related to towns.
 */
final readonly class TownGeoService
{
    public function __construct(private TownRepository $townRepository)
    {
    }

    public function getClosestTown(float $lat, float $lng): Town
    {
        // TODO: Would be more efficient to divide towns in regions instead of comparing to all towns individually

        $minDistance = INF;
        $closest = null;

        $towns = $this->townRepository->getAll();

        $kmPerLng = Geo::getKmPerLng($lat);

        foreach ($towns as $town) {
            $dist = Geo::getDistanceForOrdinalRanking($lat, $lng, $town->lat, $town->lng, $kmPerLng);
            if ($dist < $minDistance) {
                $minDistance = $dist;
                $closest = $town;
            }
        }

        /** @phpstan-var Town $closest */

        return $closest;
    }
}

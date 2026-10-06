<?php

declare(strict_types=1);

namespace App\MapPoints;

use App\Enums\VenueType;
use App\Models\Venue;
use App\Repositories\VenueRepository;
use App\Types\StandardTypes;

/**
 * Service responsible for producing map point data of Venues within a given map rectangle.
 *
 * At lower zoom levels ("zoomed out") nearby Venues are clustered into a single points, and at lower zoom levels, only
 * individual Venues are returned. Due to the performance cost of calculating clusters of many points, clusters are
 * pre-calculated and saved to cache files on disk for lower zoom levels. At higher zoom levels, points are pulled from
 * database (with clusters calculated as required).
 *
 * A `chunk` is geographic rectangle with pre-calculated points (single and cluster), for a particular set of filters,
 * at a particular zoom level, saved to a cache file on disk. When a search is determined to use cache, the `chunks`
 * that overlap with the search rectangle are combined and then filtered to provide the final results.
 *
 * @phpstan-import-type PosInt from StandardTypes
 * @phpstan-import-type NonNegInt from StandardTypes
 * @phpstan-import-type MapZoom from StandardTypes
 * @phpstan-import-type DayOfWeek from StandardTypes
 * @phpstan-import-type VenueMapRaw from Venue
 * @phpstan-import-type SinglePoint from MapPointsBuilder
 * @phpstan-import-type ClusterPoint from MapPointsBuilder
 */
class MapPointsService
{
    protected const int ZOOM_MIN = 2;
    protected const int CACHED_ZOOM_MAX = 12;
    protected const int CLUSTERING_ZOOM_MAX = 15;

    /**
     * Configuration array for spacial clustering of points at each level of zoom (0-22)
     *
     * Calculating the distance between two points is expensive, so `latClusterLimit` and `lngClusterLimit` are used
     * pre-filter points.
     *
     * @param array{
     *      app_env: non-empty-string,
     * } $config
     * @param array<MapZoom, array{
     *      latClusterLimit: float,
     *      lngClusterLimit: float,
     *      latChunkSize: float,
     *      lngChunkSize: float,
     *      chunkDecimals: PosInt,
     * }> $zoomLevelProperties
     * @param VenueRepository $venueRepository
     */
    public function __construct(
        protected array $config,
        protected array $zoomLevelProperties,
        protected VenueRepository $venueRepository,
    ) {
    }

    /**
     * @param float $lat1
     * @param float $lat2
     * @param float $lng1
     * @param float $lng2
     * @param MapZoom $zoom
     * @param list<VenueType> $types
     * @param bool $openNow
     * @return list<SinglePoint|ClusterPoint>
     */
    public function get(float $lat1, float $lat2, float $lng1, float $lng2, array $types, bool $openNow, int $zoom): array
    {
        // Enforce minimum zoom level
        if ($zoom < self::ZOOM_MIN) {
            $zoom = self::ZOOM_MIN;
        }

        // If zoom level is low enough (and not dev environment), read from cache files
        if ($zoom <= self::CACHED_ZOOM_MAX && $this->config['app_env'] !== 'local') {
            return $this->getFromCacheFiles($lat1, $lat2, $lng1, $lng2, $types, $openNow, $zoom);
        }

        // Otherwise, create points from database
        return $this->getFromDatabase($lat1, $lat2, $lng1, $lng2, $types, $openNow, $zoom);
    }

    /**
     * @param float $lat1
     * @param float $lat2
     * @param float $lng1
     * @param float $lng2
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param MapZoom $zoom
     * @return list<SinglePoint|ClusterPoint>
     */
    protected function getFromCacheFiles(float $lat1, float $lat2, float $lng1, float $lng2, array $types, bool $openNow, int $zoom): array
    {
        $properties = $this->zoomLevelProperties[$zoom];

        return MapCacheFileManager::getPoints(
            $lat1,
            $lat2,
            $lng1,
            $lng2,
            $types,
            $openNow,
            $zoom,
            $properties['latChunkSize'],
            $properties['lngChunkSize'],
            $properties['chunkDecimals'],
        );
    }

    /**
     * @param float $lat1
     * @param float $lat2
     * @param float $lng1
     * @param float $lng2
     * @param MapZoom $zoom
     * @param list<VenueType> $types
     * @param bool $openNow
     * @return list<SinglePoint|ClusterPoint>
     */
    protected function getFromDatabase(float $lat1, float $lat2, float $lng1, float $lng2, array $types, bool $openNow, int $zoom): array
    {
        $venues = $this->venueRepository->fetchForMap($lat1, $lat2, $lng1, $lng2, $types, $openNow);

        if (empty($venues)) {
            return [];
        }

        $properties = $this->zoomLevelProperties[$zoom];

        // If zoom level is high enough, disable clustering
        if ($zoom >= self::CLUSTERING_ZOOM_MAX) {
            return MapPointsBuilder::generateSinglePoints($venues);
        }

        // Otherwise, cluster nearby points
        return MapPointsBuilder::generateSingleAndClusterPoints(
            $venues,
            $properties['latClusterLimit'],
            $properties['lngClusterLimit'],
        );
    }








}

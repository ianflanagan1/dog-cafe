<?php

declare(strict_types=1);

namespace App\MapPoints;

use App\Models\Venue;
use App\Types\StandardTypes;
use App\Utils\Geo;

/**
 * A class to build map points from database data.
 *
 * @phpstan-import-type NonNegInt from StandardTypes
 * @phpstan-import-type VenueMapRaw from Venue
 *
 * Type, Latitude, Longitude, External ID
 * @phpstan-type SinglePoint array{
 *      0: 1,
 *      1: float,
 *      2: float,
 *      3: non-empty-string,
 * }
 *
 * Type, Latitude, Longitude, Point count, BoundingBox
 * @phpstan-type ClusterPoint array{
 *      0: 2,
 *      1: float,
 *      2: float,
 *      3: int<2, max>,
 *      4: BoundingBox,
 * }
 *
 * When a cluster point is clicked, the map zooms to a corresponding area specified by the cluster point's BoundBox:
 * NW Latitude, NW Longitude, SE Latitude, SE Longitude
 * @phpstan-type BoundingBox array{
 *      0: float,
 *      1: float,
 *      2: float,
 *      3: float,
 * }
 *
 * @phpstan-type ClusteringMetadata array{
 *      links: list<NonNegInt>,
 *      clustered: bool,
 * }
 */
class MapPointsBuilder
{
    protected const int LAT_DECIMALS = 6;
    protected const int LNG_DECIMALS = 6;
    protected const int CLUSTER_CATCHMENT_PIXELS = 45;
    protected const float CLUSTER_BORDER_FACTOR = 0.02;

    // Point types
    protected const int POINT_TYPE_SINGLE = 1;
    protected const int POINT_TYPE_CLUSTER = 2;

    /**
     * Process an array of `VenueMapRaw` from the database into an array of `SinglePoints`.
     *
     * @param list<VenueMapRaw> $venues
     * @return list<SinglePoint>
     */
    public static function generateSinglePoints($venues): array
    {
        return array_map([MapPointsBuilder::class, 'createSinglePoint'], $venues);
    }

    /**
     * Process an array of `VenueMapRaw` from the database into an array of `ClusterPoints` and `SinglePoints`.
     *
     * To generate `ClusterPoints`:
     * - For each Venue, record which other Venues are nearby in `links` array
     * - Identify which Venue has the most `links`, create a `ClusterPoint` from those Venues, and mark those Venues as
     *   `clustered` true
     * - Continue with the non-Clustered venue with the most `links`
     * - Repeat until all Venues with non-empty `links` are `clustered` true.
     *
     * Then generate `SinglePoints` from all remaining (`clustered` false) Venues
     *
     * @param list<VenueMapRaw> $venues
     * @param float $latClusterLimit
     * @param float $lngClusterLimit
     * @return list<SinglePoint|ClusterPoint>
     */
    public static function generateSingleAndClusterPoints(array $venues, float $latClusterLimit, float $lngClusterLimit): array
    {
        $clusters = [];
        $metadata = self::createClusteringMetadata($venues, $latClusterLimit, $lngClusterLimit);

        $index = self::getIndexWithMostLinks($metadata);

        while ($index !== null) {
            assert(
                !empty($venues) && !empty($metadata),
                '$venues and $metadata must be non-empty when $index !== null'
            );

            /** @var int<2, max> $count */
            $count = count($metadata[$index]['links']) + 1;
            [$latMin, $latMax, $lngMin, $lngMax] = self::markClusteredAndGetBounds($index, $venues, $metadata);

            $clusters[] = self::createClusterPoint($latMin, $latMax, $lngMin, $lngMax, $count);
            $index = self::updateMetadataAndGetIndexWithMostLinks($metadata);
        }

        $singles = [];

        foreach ($venues as $i => $venue) {
            if (!$metadata[$i]['clustered']) {
                $singles[] = self::createSinglePoint($venue);
            }
        }

        return array_merge($singles, $clusters);
    }

    /**
     * Create a parallel `$metadata` array for `$venues` to hold `links` and `clustered` for each Venue
     *
     * - `links` is an array of indices of the Venues (in `$venues`) that are in close proximity (when their Latitude
     *   differential is below `$latClusterLimit` and their Longitude differential is below `$lngClusterLimit`).
     * - `clustered` is a boolean to show whether or not the Venue has been used in a ClusterPoint.
     *
     * @param list<VenueMapRaw> $venues
     * @param float $latClusterLimit
     * @param float $lngClusterLimit
     * @return list<ClusteringMetadata>
     */
    protected static function createClusteringMetadata(array $venues, float $latClusterLimit, float $lngClusterLimit): array
    {
        $metadata = [];

        foreach (array_keys($venues) as $i) {
            $metadata[$i] = ['links' => [], 'clustered' => false];
        }

        for ($i = 0; $i < count($venues); $i++) {
            for ($j = $i + 1; $j < count($venues); $j++) {
                if (Geo::isWithinRectangle(
                    $venues[$i]['lat'],
                    $venues[$i]['lng'],
                    $venues[$j]['lat'],
                    $venues[$j]['lng'],
                    $latClusterLimit,
                    $lngClusterLimit,
                )) {
                    $metadata[$i]['links'][] = $j;
                    $metadata[$j]['links'][] = $i;
                }
            }
        }

        return $metadata;
    }

    /**
     * Set `clustered` to true for all Venues in a new cluster and determine the minimum and maximum Latitude and
     * Longitude.
     *
     * @param int $index
     * @param non-empty-list<VenueMapRaw> $venues
     * @param non-empty-list<ClusteringMetadata> $metadata
     * @return array{
     *      0: float,
     *      1: float,
     *      2: float,
     *      3: float,
     * }
     */
    protected static function markClusteredAndGetBounds(int $index, array $venues, array &$metadata): array
    {
        $metadata[$index]['clustered'] = true;

        $latMin = $venues[$index]['lat'];
        $latMax = $venues[$index]['lat'];
        $lngMin = $venues[$index]['lng'];
        $lngMax = $venues[$index]['lng'];

        for ($i = 0; $i < count($metadata[$index]['links']); $i++) {
            $pointIndex = $metadata[$index]['links'][$i];

            $metadata[$pointIndex]['clustered'] = true;

            if ($latMin > $venues[$pointIndex]['lat']) {
                $latMin = $venues[$pointIndex]['lat'];

            } elseif ($latMax < $venues[$pointIndex]['lat']) {
                $latMax = $venues[$pointIndex]['lat'];
            }

            if ($lngMin > $venues[$pointIndex]['lng']) {
                $lngMin = $venues[$pointIndex]['lng'];

            } elseif ($lngMax < $venues[$pointIndex]['lng']) {
                $lngMax = $venues[$pointIndex]['lng'];
            }
        }

        return [$latMin, $latMax, $lngMin, $lngMax];
    }

    /**
     * Get the index of the `$metadata` array with the most links.
     *
     * @param list<ClusteringMetadata> $metadata
     * @return ?NonNegInt
     */
    protected static function getIndexWithMostLinks(array $metadata): ?int
    {
        $max = 0;
        $index = null;

        for ($i = 0; $i < count($metadata); $i++) {
            if ($max < count($metadata[$i]['links'])) {
                $max = count($metadata[$i]['links']);
                $index = $i;
            }
        }

        return $index;
    }

    /**
     * Remove and newly `clustered` points from any `links` arrays, and get the index of the `$metadata` array with the
     * most links remaining
     *
     * @param list<ClusteringMetadata> $metadata
     * @return ?NonNegInt
     */
    protected static function updateMetadataAndGetIndexWithMostLinks(array &$metadata): ?int
    {
        $max = 0;
        $index = null;

        for ($i = 0; $i < count($metadata); $i++) {
            if ($metadata[$i]['clustered']) {
                continue;
            }

            for ($j = 0; $j < count($metadata[$i]['links']); $j++) {
                $pointIndex = $metadata[$i]['links'][$j];

                if ($metadata[$pointIndex]['clustered']) {
                    array_splice($metadata[$i]['links'], $j, 1);
                    $j--;
                }
            }

            if ($max < count($metadata[$i]['links'])) {
                $max = count($metadata[$i]['links']);
                $index = $i;
            }
        }

        return $index;
    }

    /**
     * @param float $latMin
     * @param float $latMax
     * @param float $lngMin
     * @param float $lngMax
     * @param int<2, max> $count
     * @return ClusterPoint
     */
    protected static function createClusterPoint(
        float $latMin,
        float $latMax,
        float $lngMin,
        float $lngMax,
        int $count
    ): array {
        // Calculate centre point
        $lat = ($latMin + $latMax) / 2;
        $lng = ($lngMin + $lngMax) / 2;

        // Calculate border margin
        $latBorder = ($latMax - $latMin) * self::CLUSTER_BORDER_FACTOR;
        $lngBorder = ($lngMax - $lngMin) * self::CLUSTER_BORDER_FACTOR;

        return [
            self::POINT_TYPE_CLUSTER,
            self::formatLat($lat),
            self::formatLat($lng),
            $count,
            [
                self::formatLat($latMin - $latBorder),
                self::formatLng($lngMin - $lngBorder),
                self::formatLat($latMax + $latBorder),
                self::formatLng($lngMax + $lngBorder),
            ],
        ];
    }

    /**
     * @param VenueMapRaw $venue
     * @return SinglePoint
     */
    protected static function createSinglePoint(array $venue): array
    {
        return [
            self::POINT_TYPE_SINGLE,
            $venue['lat'],
            $venue['lng'],
            $venue['ext_id'],
        ];
    }

    protected static function formatLat(float $lat): float
    {
        return round($lat, self::LAT_DECIMALS);
    }

    protected static function formatLng(float $lng): float
    {
        return round($lng, self::LNG_DECIMALS);
    }
}

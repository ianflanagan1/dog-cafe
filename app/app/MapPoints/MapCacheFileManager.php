<?php

declare(strict_types=1);

namespace App\MapPoints;

use App\Enums\VenueType;
use App\Types\StandardTypes;
use RuntimeException;

/**
 * A class to retrieve cache files of `Chunks` of map points.
 *
 * A `Chunk` is a pre-calculated array of map points for a geographical "rectangle", the specified zoom level, the
 * specified set of filters, and for the current day-of-week and time interval (if openNow filter is enabled),
 * `json_encode`d in a cache file. `Chunks` tesselate with the size and location of the "rectangles" vary per zoom level
 * and are configured in `./config/`mapZoomLevelProperties`. The width and height of `Chunks` are specified by
 * `latChunkSize` and `lngChunkSize` respectively, and the number of decimals in the filename is specified by
 * `chunkDecimals`.
 *
 * The start point of `Chunks` is set by `self::COVERAGE['lat_min']` and `['lng_min']`, and `Chunks` continue Eastward
 * and Southward until `['lat_max']` and `['lng_max']` are met or exceeded.
 *
 * @phpstan-import-type NonNegInt from StandardTypes
 * @phpstan-import-type MapZoom from StandardTypes
 * @phpstan-import-type DayOfWeek from StandardTypes
 * @phpstan-import-type SinglePoint from MapPointsBuilder
 * @phpstan-import-type ClusterPoint from MapPointsBuilder
 *
 * @phpstan-type Chunk list<SinglePoint|ClusterPoint>
 */
class MapCacheFileManager
{
    protected const array COVERAGE = [ // Covering the UK
        'lat_min' => 49,
        'lat_max' => 61,
        'lng_min' => -9,
        'lng_max' => 2,
    ];
    protected const string CACHE_FILE_NAMESPACE = 'map';
    protected const int POINT_INDEX_TYPE = 0;
    protected const int POINT_INDEX_LAT = 1;
    protected const int POINT_INDEX_LNG = 2;

    /**
     * Get an array points from cache files for the specified geographical "rectangle", the specified zoom level, and
     * the specified filters.
     *
     * @param float $lat1
     * @param float $lat2
     * @param float $lng1
     * @param float $lng2
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param MapZoom $zoom
     * @param float $latChunkSize
     * @param float $lngChunkSize
     * @param NonNegInt $chunkDecimals
     * @return list<SinglePoint|ClusterPoint>
     */
    public static function getPoints(
        float $lat1,
        float $lat2,
        float $lng1,
        float $lng2,
        array $types,
        bool $openNow,
        int $zoom,
        float $latChunkSize,
        float $lngChunkSize,
        int $chunkDecimals,
    ): array {
        $chunks = self::getChunks(
            $lat1,
            $lat2,
            $lng1,
            $lng2,
            $types,
            $openNow,
            $zoom,
            $latChunkSize,
            $lngChunkSize,
            $chunkDecimals,
        );

        return self::getPointsFromChunks(
            $lat1,
            $lat2,
            $lng1,
            $lng2,
            $chunks,
        );
    }

    /**
     * From an array of `Chunks`, return only the point that are within the specified geographical "rectangle".
     *
     * @param float $lat1
     * @param float $lat2
     * @param float $lng1
     * @param float $lng2
     * @param list<list<SinglePoint|ClusterPoint>> $chunks
     * @return list<SinglePoint|ClusterPoint>
     */
    protected static function getPointsFromChunks(float $lat1, float $lat2, float $lng1, float $lng2, array $chunks): array
    {
        $points = [];

        foreach ($chunks as $chunk) {
            $index = 0;

            // Skip points with lat too high
            while (
                $index < count($chunk)
                && $chunk[$index][self::POINT_INDEX_LAT] > $lat2
            ) {
                $index++;
            }

            // Iterate through points with lat in correct range
            while (
                $index < count($chunk)
                && $chunk[$index][self::POINT_INDEX_LAT] > $lat1
            ) {
                if (
                    $chunk[$index][self::POINT_INDEX_LNG] > $lng1
                    && $chunk[$index][self::POINT_INDEX_LNG] < $lng2
                ) {
                    $points[] = $chunk[$index];
                }

                $index++;
            }
        }

        return $points;
    }

    /**
     * Get an array of `Chunks` for the passed zoom level and filters that overlap with the passed "rectangle".
     *
     * @param float $lat1
     * @param float $lat2
     * @param float $lng1
     * @param float $lng2
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param MapZoom $zoom
     * @param float $latChunkSize
     * @param float $lngChunkSize
     * @param NonNegInt $chunkDecimals
     * @return list<Chunk>
     */
    protected static function getChunks(
        float $lat1,
        float $lat2,
        float $lng1,
        float $lng2,
        array $types,
        bool $openNow,
        int $zoom,
        float $latChunkSize,
        float $lngChunkSize,
        int $chunkDecimals,
    ): array {
        $chunks = [];

        $cacheFileBase = self::determineCacheFileBase($zoom, $types, $openNow);

        [$chunkLatMin, $chunkLatMax, $chunkLngMin, $chunkLngMax] = self::determineChunkBounds($lat1, $lat2, $lng1, $lng2, $latChunkSize, $lngChunkSize);

        $runningLat = $chunkLatMin;

        // From the Northern-most, Western-most `Chunk` that overlaps with the given rectangle, loop Southwards and Eastwards
        // until the whole rectangle is covered
        while ($runningLat < $chunkLatMax) {
            $runningLng = $chunkLngMin;

            while ($runningLng < $chunkLngMax) {
                $cacheFile = self::getCacheFile($cacheFileBase, $runningLat, $runningLng, $chunkDecimals);

                if (file_exists($cacheFile)) {
                    $content = file_get_contents($cacheFile);

                    if ($content === false) {
                        throw new RuntimeException();
                    }

                    /** @var Chunk $chunk */
                    $chunk = json_decode($content, true);

                    $chunks[] = $chunk;
                }

                $runningLng += $lngChunkSize;
            }

            $runningLat += $latChunkSize;
        }

        return $chunks;
    }

    /**
     * Determine the appropriate minimum and maximum Latitude and Longitude of `Chunks` that overlap with the pass rectangle.
     *
     * @param float $lat1
     * @param float $lat2
     * @param float $lng1
     * @param float $lng2
     * @param float $latChunkSize
     * @param float $lngChunkSize
     * @return array{
     *      0: float,
     *      1: float,
     *      2: float,
     *      3: float,
     * }
     */
    protected static function determineChunkBounds(float $lat1, float $lat2, float $lng1, float $lng2, float $latChunkSize, float $lngChunkSize): array
    {
        if ($lat1 < self::COVERAGE['lat_min']) {
            $chunkLatMin = self::COVERAGE['lat_min'];
        } else {
            $chunkLatMin = floor(($lat1 - self::COVERAGE['lat_min']) / $latChunkSize) * $latChunkSize + self::COVERAGE['lat_min'];
        }

        if ($lng1 < self::COVERAGE['lng_min']) {
            $chunkLngMin = self::COVERAGE['lng_min'];
        } else {
            $chunkLngMin = floor(($lng1 - self::COVERAGE['lng_min']) / $lngChunkSize) * $lngChunkSize + self::COVERAGE['lng_min'];
        }

        $chunkLatMax = min(self::COVERAGE['lat_max'], $lat2);
        $chunkLngMax = min(self::COVERAGE['lng_max'], $lng2);

        return [$chunkLatMin, $chunkLatMax, $chunkLngMin, $chunkLngMax];
    }

    /**
     * Determine the location base for cache files from the passed zoom level and filters.
     *
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param MapZoom $zoom
     * @return non-empty-string
     */
    protected static function determineCacheFileBase(int $zoom, array $types, bool $openNow): string
    {
        $typesString = VenueType::typesToString($types);

        if (!$openNow) {
            return self::buildCacheFileBase($zoom, $typesString);
        }

        $day = (int) date('w');     // 0 - 6
        $currentTime = date('H:i'); // 09:05
        $cacheFileTime = self::getCacheFileTime($typesString, $day, $currentTime);

        return self::buildCacheFileBase($zoom, $typesString, true, $day, $cacheFileTime);
    }

    /**
     * @param MapZoom $zoom
     * @param non-empty-string $typesString
     * @param bool $openNow
     * @param ?DayOfWeek $day
     * @param ?non-empty-string $time
     * @return non-empty-string
     */
    protected static function buildCacheFileBase(int $zoom, string $typesString, bool $openNow = false, ?int $day = null, ?string $time = null): string
    {
        $typesString = "/types={$typesString}";

        $openNowString = $openNow
            ? "opennow={$day}_{$time}"
            : '';

        return CACHE_FILE_PATH . '/' . self::CACHE_FILE_NAMESPACE . "/zoom={$zoom}/{$typesString}/{$openNowString}";
    }

    /**
     * Get the time of the cache files that cover the current time.
     *
     * @param non-empty-string $typesString
     * @param DayOfWeek $day
     * @param non-empty-string $currentTime
     * @return non-empty-string
     */
    protected static function getCacheFileTime(string $typesString, int $day, string $currentTime): string
    {
        // Load file of opening times
        $openTimesCacheFile = self::getOpenTimesCacheFile($typesString, $day);
        $content = file_get_contents($openTimesCacheFile);

        if ($content === false) {
            throw new RuntimeException();
        }

        /** @var list<non-empty-string> $openTimes */
        $openTimes = json_decode($content, true);

        // Progress through the list of opening times until current time is reached
        $index = 0;

        while ($currentTime > $openTimes[$index]) {
            $index++;
        }

        if ($index > 0) {
            $index--;
        }

        return $openTimes[$index];
    }

    /**
     * Get location of a cache file.
     *
     * @param non-empty-string $cacheFileBase
     * @param float $lat
     * @param float $lng
     * @param NonNegInt $decimals
     * @return non-empty-string
     */
    protected static function getCacheFile(string $cacheFileBase, float $lat, float $lng, int $decimals): string
    {
        return $cacheFileBase . '/lat=' . (string) round($lat, $decimals) . '/lng=' . (string) round($lng, $decimals);
    }

    /**
     * Get the location of the cache file listing times for the passed day and filters.
     *
     * These times mark when at least one Venue opens or closes.
     *
     * @param non-empty-string $typesString
     * @param DayOfWeek $day
     * @return non-empty-string
     */
    protected static function getOpenTimesCacheFile(string $typesString, int $day): string
    {
        return CACHE_FILE_PATH . '/' . self::CACHE_FILE_NAMESPACE . '/relevantTimes_' . $typesString . '_' . $day;
    }
}

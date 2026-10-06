<?php

declare(strict_types=1);

namespace App\Utils;

class Geo
{
    protected const float EARTH_CIRCUMFERENCE_KM = 40075.017;   // (Earth radius (km) × 2 × π)
    protected const float KM_PER_LAT = 111.31949079327357;      // Earth circumference (km) / 360 degrees

    /**
     * Calculate an approximate distance from a fixed `Main` point.
     *
     * Intended for **ordinal rankings only**, i.e. ordering a list of `Secondary` points by their distance from `Main`.
     *
     * The Earth's curved surface is approximated by a tangential plane at `Main`, so all non-zero results underestimate
     * the true distance, with the error increasing superlinearly as the true distance increases. However, the ordinal
     * rankings are roughly maintained. At increasingly extreme distances (>100km), the ordinal rankings are less
     * reliable.
     *
     * For accurate distance calculations, use the (slower) Haversine formula instead.
     */
    public static function getDistanceForOrdinalRanking(
        float $mainLat,
        float $mainLng,
        float $secondaryLat,
        float $secondaryLng,
        float $kmPerLng,
    ): float {
        return sqrt((($mainLat - $secondaryLat) * self::KM_PER_LAT) ** 2 + (($mainLng - $secondaryLng) * $kmPerLng) ** 2);
    }

    /**
     * Calculate one degree of Longitude in kilometers for a given Latitude.
     *
     * Due to spherical geometry, the relationship between Latitude and Longitude varies with Latitude (very slightly
     * near the equator, and dramatically near the poles).
     */
    public static function getKmPerLng(float $lat): float
    {
        return self::EARTH_CIRCUMFERENCE_KM / 360 * cos(deg2rad($lat));
    }

    /**
     * Determine if both points fit within a "rectangle" with lengths specified by `$latClusterLimit` and `$lngClusterLimit`.
     */
    public static function isWithinRectangle(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
        float $latClusterLimit,
        float $lngClusterLimit,
    ): bool {
        return abs($lat1 - $lat2) < $latClusterLimit && abs($lng1 - $lng2) < $lngClusterLimit;
    }
}

<?php

declare(strict_types=1);

namespace App\Hydrators;

use App\Enums\VenueType;
use App\Models\Venue;
use App\Types\StandardTypes;
use App\Utils\Time;

/**
 * @phpstan-import-type PosInt from StandardTypes
 *
 * @phpstan-import-type VenueMinimalForHtml from Venue
 * @phpstan-import-type VenueMinimalFromDatabase from Venue
 * @phpstan-import-type VenueMinimalForCache from Venue
 * @phpstan-import-type VenueFull from Venue
 * @phpstan-import-type VenueFullFromDatabase from Venue
 * @phpstan-import-type OpenClose from Venue
 * @phpstan-import-type OpenCloseArray from Venue
 */
class VenueHydrator
{
    /**
     * @param VenueMinimalFromDatabase $venue
     * @return VenueMinimalForCache
     */
    public static function processTypes(array $venue): array
    {
        $venue['types'] = VenueType::getCacheArray($venue['is_cafe'], $venue['is_restaurant'], $venue['is_bar']);
        unset($venue['is_cafe'], $venue['is_restaurant'], $venue['is_bar']);

        return $venue;
    }

    /**
     * @param VenueMinimalForCache $venue
     * @return VenueMinimalForHtml
     */
    public static function processOpeningTimes(array $venue): array
    {
        $time  = date('H:i');       // Hour with zero, minute with zero             date('H:i:s') => '09:09:33'
        $today = (int) date('w');   // Day of week: 0 (Sunday) to 6 (Saturday)

        // Get the opening/closing times for today
        $openTime = $venue[Venue::COLUMN_PREFIX_OPEN_TIME . $today];
        $closeTime = $venue[Venue::COLUMN_PREFIX_CLOSE_TIME . $today];

        // Venue is currently closed and will open today
        if ($openTime !== null && $time < $openTime) {
            $isOpen = false;
            $changeTime = 'at ' . Time::militaryToCivilianTime($openTime);

            // Venue is currently open
        } elseif ($closeTime !== null && $time < $closeTime) {
            $isOpen = true;
            $changeTime = Time::militaryToCivilianTime($closeTime);

            // Venue is currently closed and won't open today, so loop through day of the week to find next opening time
        } else {
            $isOpen = false;
            $changeTime = '-';

            $day = $today + 1;

            for ($i = 0; $i < 7; $i++) {
                if ($day == 7) {
                    $day = 0;
                }

                $nextOpen = $venue[Venue::COLUMN_PREFIX_OPEN_TIME . $day];

                if ($nextOpen !== null) {
                    if ($day == $today + 1) {
                        $changeTime = 'tomorrow ' . Time::militaryToCivilianTime($nextOpen);
                        break;
                    }

                    $changeTime = Time::dayIntToString($day) . ' ' . Time::militaryToCivilianTime($nextOpen);
                    break;
                }
                $day++;
            }
        }

        return [
            'ext_id'      => $venue['ext_id'],
            'name'        => $venue['name'],
            'image'       => $venue['image'],
            'types'       => $venue['types'],
            'open'        => $isOpen,
            'change_time' => $changeTime,
        ];
    }

    /**
     * @param VenueFullFromDatabase $venue
     * @param list<PosInt> $favourites
     * @return VenueFull
     */
    public static function processFull(array $venue, array $favourites = []): array
    {
        $openClose = self::getOpenClose($venue);

        // Limit to 7 images
        if ($venue['images'] !== null) {
            $images = json_decode($venue['images'], true);

            assert(
                is_array($images),
                'Decoded images must be an array'
            );

            $images = array_splice($images, 0, 7);

        } else {
            $images = [];
        }

        $fav = 0;

        foreach ($favourites as $favourite) {
            if ($favourite == $venue['id']) {
                $fav = 1;
                break;
            }
        }

        $venue['images'] = $images;
        $venue['fav'] = $fav;
        $venue['openClose'] = $openClose;
        $venue['types'] = VenueType::getCacheArray($venue['is_cafe'], $venue['is_restaurant'], $venue['is_bar']);

        return $venue;
    }

    /**
     * @param VenueFullFromDatabase $venue
     * @return OpenCloseArray
     */
    protected static function getOpenClose(array $venue): array
    {
        $output = [];

        for ($day = 0; $day < 7; $day++) {
            $output[] = self::getOpenCloseByDay($venue, $day);
        }

        /** @phpstan-var OpenCloseArray $output */

        return $output;
    }

    /**
     * @param VenueFullFromDatabase $venue
     * @param int<0,6> $day
     * @return OpenClose
     */
    protected static function getOpenCloseByDay(array $venue, int $day): array
    {
        $openTime = $venue[Venue::COLUMN_PREFIX_OPEN_TIME . $day];

        if ($openTime === null) {
            return [
                'day'   => Time::dayIntToString($day),
                'time'  => 'closed',
            ];

        }

        $closeTime = $venue[Venue::COLUMN_PREFIX_CLOSE_TIME . $day];

        assert(
            $closeTime !== null,
            '$closeTime must be non-null if $openTime is non-null'
        );

        return [
            'day'   => Time::dayIntToString($day),
            'time'  => Time::militaryToCivilianTime($openTime) . ' - ' . Time::militaryToCivilianTime($closeTime),
        ];
    }
}

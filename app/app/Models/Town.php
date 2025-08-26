<?php

declare(strict_types=1);

namespace App\Models;

use App\Types\StandardTypes;

/**
 * @phpstan-import-type PosInt from StandardTypes
 * @phpstan-import-type MapZoom from StandardTypes
 *
 * @phpstan-type TownFromDatabase array{
 *     id: PosInt,
 *     name: non-empty-string,
 *     name_url: non-empty-string,
 *     name_url_lower: non-empty-string,
 *     name_search: non-empty-string,
 *     county: non-empty-string,
 *     venue_count: int,
 *     lat: float,
 *     lng: float
 * }
 *
 * 0: name-url, 1: name, 2: county, 3: venue count
 * @phpstan-type TownForJson array{
 *      0: string,
 *      1: string,
 *      2: string,
 *      3: int
 * }
 */

class Town extends Model
{
    public const string TABLE = 'town';

    /**
     * @param PosInt $id
     * @param non-empty-string $name
     * @param non-empty-string $nameUrl
     * @param non-empty-string $nameUrlLower
     * @param non-empty-string $nameSearch
     * @param non-empty-string $county
     * @param int $venueCount
     * @param float $lat
     * @param float $lng
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $nameUrl,
        public readonly string $nameUrlLower,
        public readonly string $nameSearch,
        public readonly string $county,
        public readonly int $venueCount,
        public readonly float $lat,
        public readonly float $lng
    ) {
    }

    /**
     * @param TownFromDatabase $array
     * @return self
     */
    public static function fromArray(array $array): self
    {
        return new self(
            id:             $array['id'],
            name:           $array['name'],
            nameUrl:        $array['name_url'],
            nameUrlLower:   $array['name_url_lower'],
            nameSearch:     $array['name_search'],
            county:         $array['county'],
            venueCount:     $array['venue_count'],
            lat:            $array['lat'],
            lng:            $array['lng'],
        );
    }

    /**
     * @return TownFromDatabase
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_url' => $this->nameUrl,
            'name_url_lower' => $this->nameUrlLower,
            'name_search' => $this->nameSearch,
            'county' => $this->county,
            'venue_count' => $this->venueCount,
            'lat' => $this->lat,
            'lng' => $this->lng,
        ];
    }

    /**
     * @return MapZoom
     */
    public function zoomLevel(): int
    {
        if ($this->venueCount > 100) {
            return 11;
        }

        if ($this->venueCount > 50) {
            return 12;
        }

        return 13;
    }
}

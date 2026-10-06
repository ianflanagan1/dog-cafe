<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Town;
use App\Types\StandardTypes;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

/**
 * @phpstan-import-type PosInt from StandardTypes
 *
 * @phpstan-import-type TownFromDatabase from Town
 */

readonly class TownRepository
{
    protected const string CACHE_KEY_TOWNS_LIST = 'townsList';
    protected const string CACHE_KEY_DEFAULT_TOWN = 'defaultTown';
    protected const int DEFAULT_ID = 3719; // London

    public function __construct(
        protected GenericRepository $genericRepository,
        protected CacheInterface $cache,
    ) {
    }

    /**
     * @param PosInt $id
     * @return Town
     */
    public function getById(int $id): Town
    {
        foreach ($this->getAllArrays() as $town) {
            if ($town['id'] === $id) {
                return Town::fromArray($town);
            }
        }

        throw new RuntimeException();
    }

    /**
     * @param non-empty-string $nameUrl
     * @return ?Town
     */
    public function findByNameUrlLower(string $nameUrl): ?Town
    {
        $nameUrlLower = strtolower($nameUrl);

        foreach ($this->getAllArrays() as $town) {
            if ($town['name_url_lower'] === $nameUrlLower) {
                return Town::fromArray($town);
            }
        }

        return null;
    }

    /**
     * @return non-empty-list<Town>
     */
    public function getAll(): array
    {
        $towns = $this->getAllArrays();

        return array_map(
            fn (array $array): Town => Town::fromArray($array),
            $towns
        );
    }

    /**
     * Get the default Town (London)
     *
     * @return Town
     */
    public function getDefault(): Town
    {
        // Check cache first
        $town = $this->findFromCache(self::CACHE_KEY_DEFAULT_TOWN);

        // If found, decode and hydrate
        if ($town !== null) {
            $town = json_decode($town, true);
            /** @phpstan-var TownFromDatabase $town */
            return Town::fromArray($town);
        }

        // Otherwise, get from full towns list and write to cache
        $town = $this->getById(self::DEFAULT_ID);
        $this->saveToCache(self::CACHE_KEY_DEFAULT_TOWN, $town->toArray());

        return $town;
    }

    /**
     * @return non-empty-list<TownFromDatabase>
     */
    protected function getAllArrays(): array
    {
        // Check cache first
        $towns = $this->findFromCache(self::CACHE_KEY_TOWNS_LIST);

        // If found, decode
        if ($towns !== null) {
            $towns = json_decode($towns, true);

            // Otherwise, get from database and refresh cache
        } else {
            $towns = $this->getAllArraysFromDatabase();
            $this->saveToCache(self::CACHE_KEY_TOWNS_LIST, $towns);
        }

        assert(
            is_array($towns) && !empty($towns),
            '$towns (all towns) must be non-empty array'
        );

        return $towns;
    }

    /**
     * @return non-empty-list<TownFromDatabase>
     */
    protected function getAllArraysFromDatabase(): array
    {
        $towns = $this->genericRepository->fetchRows(
            'id, name, name_url, name_url_lower, name_search, county, venue_count, lat, lng',
            Town::TABLE,
            'venue_count > ?',
            0,
            'venue_count DESC',
        );

        assert(
            !empty($towns),
            '$towns array (all towns, from database) must be non-empty'
        );

        /** @var non-empty-list<TownFromDatabase> $towns */

        foreach ($towns as &$town) {
            $town['lat'] = (float) $town['lat'];
            $town['lng'] = (float) $town['lng'];
        }

        return $towns;
    }

    /**
     * @param non-empty-string $key
     * @return ?non-empty-string
     */
    protected function findFromCache(string $key): ?string
    {
        $res = $this->cache->get($key);

        assert(
            $res === null
            || (is_string($res) && !empty($res)),
            '$town (from cache) must be null or a string'
        );

        return $res;
    }

    /**
     * @param non-empty-string $key
     * @param TownFromDatabase|list<TownFromDatabase> $input
     * @return void
     */
    protected function saveToCache(string $key, array $input): void
    {
        $this->cache->set(
            $key,
            json_encode($input),
        );
    }
}

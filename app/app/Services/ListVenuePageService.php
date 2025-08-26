<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\SqlWhere;
use App\Enums\VenueOrderBy;
use App\Enums\VenueType;
use App\Hydrators\VenueHydrator;
use App\Models\Venue;
use App\Repositories\GenericRepository;
use App\Repositories\VenueRepository;
use App\Types\StandardTypes;
use Psr\SimpleCache\CacheInterface;

/**
 * Service responsible for fetching lists of venues with caching.
 *
 * Implements logic to:
 *  - Check cache for total pages and items separately
 *  - Retrieve data from the database if cache misses occur
 *  - Process results and save them back to the cache
 *  - Return a `VenuePage` array consisting of total page count, and a list of processed venue data
 *
 * @phpstan-import-type PosInt from StandardTypes
 * @phpstan-import-type NonNegInt from StandardTypes
 * @phpstan-import-type VenueMinimalForHtml from Venue
 * @phpstan-import-type VenueMinimalForCache from Venue
 * @phpstan-import-type VenueMinimalForJson from Venue
 * @phpstan-import-type VenuePage from VenueRepository
 */
class ListVenuePageService
{
    protected const int TTL = 604800; // 1 week

    public function __construct(
        protected CacheInterface $cache,
        protected VenueRepository $venueRepository,
    ) {
    }

    /**
     * Process the `types` array of a post-cache Venue, ready for outputting in JSON format.
     *
     * @param VenueMinimalForHtml $venue
     * @return VenueMinimalForJson
     */
    public static function processForJson(array $venue): array
    {
        $venue['types'] = array_map(fn ($type) => $type['value'], $venue['types']);
        return $venue;
    }

    /**
     * Get a `VenuePage` array consisting of total page count, and a list of processed venue data.
     *
     * @param PosInt $townId
     * @param PosInt $page
     * @param PosInt $pageSize
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param VenueOrderBy $orderBy
     * @return VenuePage
     */
    public function get(
        int $townId,
        int $page,
        int $pageSize,
        array $types,
        bool $openNow,
        VenueOrderBy $orderBy,
    ): array {
        // "Dynamic" queries depend on variables lke current day/time etc.
        if ($openNow) {
            return $this->getForDynamicState($townId, $page, $pageSize, $types, $openNow, $orderBy);
        }

        $totalPages = $this->getTotalPages($townId, $pageSize, $types, $openNow, $where);

        // If we already know there are no items, return empty
        if (!GenericRepository::pageHasItems($totalPages, $page)) {
            return [$totalPages, []];
        }

        // Otherwise, retrieve items
        $items = $this->getItems($townId, $page, $pageSize, $types, $openNow, $orderBy, $where);

        return [$totalPages, $items];
    }

    /**
     * @param PosInt $townId
     * @param PosInt $pageSize
     * @param list<VenueType> $types
     * @return non-empty-string
     */
    protected static function buildTotalPagesCacheKey(int $townId, int $pageSize, array $types): string
    {
        $types = array_map(fn ($type) => $type->value, $types);
        sort($types);
        $typesStr = implode(',', $types);

        return "venueListTotalPages:{$townId}:{$typesStr}:{$pageSize}";
    }

    /**
     * @param PosInt $townId
     * @param PosInt $page
     * @param PosInt $pageSize
     * @param list<VenueType> $types
     * @return non-empty-string
     */
    protected static function buildItemsCacheKey(int $townId, int $page, int $pageSize, array $types, VenueOrderBy $orderBy): string
    {
        $types = array_map(fn ($type) => $type->value, $types);
        sort($types);
        $typesStr = implode(',', $types);

        return "venueListItems:{$townId}:{$typesStr}:{$orderBy->value}:{$pageSize}:{$page}";
    }

    /**
     * Handles dynamic queries (e.g. affected by current day/time).
     *
     * TODO: Currently this bypasses cache because `$openNow` depends on current time vs the Venue opening/closing
     * times, but this can be improved. We already record the "steps" of opening times for each map zone in the
     * `relevantTimes_` cache files used by`MapPointsService` class `getOpenTimesCacheLocation()` method. We can do the
     * same for each city
     *
     * @param PosInt $townId
     * @param PosInt $page
     * @param PosInt $pageSize
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param VenueOrderBy $orderBy
     * @return VenuePage
     */
    protected function getForDynamicState(
        int $townId,
        int $page,
        int $pageSize,
        array $types,
        bool $openNow,
        VenueOrderBy $orderBy,
    ): array {

        [$totalPages, $items] = $this->venueRepository->getVenuePageRawForList(
            $townId,
            $page,
            $pageSize,
            $types,
            $openNow,
            $orderBy
        );

        $items = array_map(fn (array $v): array => VenueHydrator::processTypes($v), $items);
        $items = array_map(fn (array $v): array => VenueHydrator::processOpeningTimes($v), $items);

        return [$totalPages, $items];
    }

    /**
     * @param PosInt $townId
     * @param PosInt $pageSize
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param ?SqlWhere $where
     * @return NonNegInt
     */
    protected function getTotalPages(
        int $townId,
        int $pageSize,
        array $types,
        bool $openNow,
        ?SqlWhere &$where,
    ): int {
        // Check cache
        $cacheKey = self::buildTotalPagesCacheKey($townId, $pageSize, $types);
        $totalPages = $this->cache->get($cacheKey);

        assert(
            $totalPages === null || ctype_digit($totalPages),
            '$totalPages (from cache) must be null or a string of an integer'
        );

        if ($totalPages !== null) {
            $totalPages = (int) $totalPages;
            assert($totalPages >= 0, '$totalPages must be non-negative');
            return $totalPages;
        }

        // Otherwise, get from database and save to cache
        if ($where === null) {
            $where = VenueRepository::getWhereForList($townId, $types, $openNow);
        }

        return $this->totalPagesRefreshCache(
            $cacheKey,
            $pageSize,
            $where,
        );
    }

    /**
     * @param PosInt $townId
     * @param PosInt $page
     * @param PosInt $pageSize
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param VenueOrderBy $orderBy
     * @param ?SqlWhere $where
     * @return list<VenueMinimalForHtml>
     */
    protected function getItems(
        int $townId,
        int $page,
        int $pageSize,
        array $types,
        bool $openNow,
        VenueOrderBy $orderBy,
        ?SqlWhere &$where,
    ): array {
        // Check cache
        $cacheKey = self::buildItemsCacheKey($townId, $page, $pageSize, $types, $orderBy);
        $items = $this->cache->get($cacheKey);

        assert(
            $items === null
            || (is_string($items) && !empty($items)),
            '$items (from cache) must be null or a string'
        );

        if ($items !== null) {
            /** @var list<VenueMinimalForCache> $items */
            $items = json_decode($items, true);

            // Otherwise, get from database and save to cache
        } else {
            if ($where === null) {
                $where = VenueRepository::getWhereForList($townId, $types, $openNow);
            }

            $items = $this->itemsRefreshCache(
                $cacheKey,
                $page,
                $pageSize,
                $where,
                $orderBy,
            );
        }

        // Determine "open" status and "opening at" or "closing at" message
        return array_map(fn (array $v): array => VenueHydrator::processOpeningTimes($v), $items);
    }

    /**
     * @param non-empty-string $itemsCacheKey
     * @param PosInt $page
     * @param PosInt $pageSize
     * @param SqlWhere $where
     * @param VenueOrderBy $orderBy
     * @return list<VenueMinimalForCache>
     */
    protected function itemsRefreshCache(
        string $itemsCacheKey,
        int $page,
        int $pageSize,
        SqlWhere $where,
        VenueOrderBy $orderBy,
    ): array {
        $items = $this->venueRepository->fetch(
            $page,
            $pageSize,
            $where,
            $orderBy,
        );

        $items = array_map(fn (array $v): array => VenueHydrator::processTypes($v), $items);
        $this->cache->set($itemsCacheKey, json_encode($items), self::TTL);
        return $items;
    }

    /**
     * @param non-empty-string $totalPageCacheKey
     * @param PosInt $pageSize
     * @param SqlWhere $where
     * @return NonNegInt
     */
    protected function totalPagesRefreshCache(
        string $totalPageCacheKey,
        int $pageSize,
        SqlWhere $where,
    ): int {
        $result = $this->venueRepository->getTotalPages($pageSize, $where);
        $this->cache->set($totalPageCacheKey, $result, self::TTL);
        return $result;
    }
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\Locality;
use App\Enums\VenueOrderBy;
use App\Enums\VenueType;
use App\Framework\Database\GenericRepository;
use App\Framework\Database\SqlWhere;
use App\Framework\Support\StandardTypes;
use App\Models\Venue;

/**
 * @phpstan-import-type PosInt from StandardTypes
 * @phpstan-import-type NonNegInt from StandardTypes
 * @phpstan-import-type VenueMinimalFromDatabase from Venue
 * @phpstan-import-type VenueMinimalForHtml from Venue
 * @phpstan-import-type VenueFullFromDatabase from Venue
 * @phpstan-import-type VenueMapRaw from Venue
 *
 * @phpstan-type VenuePageRaw array{
 *      0: NonNegInt,
 *      1: list<VenueMinimalFromDatabase>,
 * }
 *
 * @phpstan-type VenuePage array{
 *      0: NonNegInt,
 *      1: list<VenueMinimalForHtml>,
 * }
 */
final readonly class VenueRepository
{
    private const string COLUMNS_FULL = 'id, ext_id, name, human_url, town, lat, lng, images, is_cafe, is_restaurant, is_bar,
        open_0, close_0, open_1, close_1, open_2, close_2, open_3, close_3, open_4, close_4, open_5, close_5, open_6, close_6,
        street, area, locality, region, postcode, public_phone, website';
    private const string COLUMNS_MINIMAL = 'ext_id, name, image, is_cafe, is_restaurant, is_bar,
        open_0, close_0, open_1, close_1, open_2, close_2, open_3, close_3, open_4, close_4, open_5, close_5, open_6, close_6';
    private const string COLUMNS_MAP = 'ext_id, lat, lng';

    public function __construct(
        private GenericRepository $genericRepository,
    ) {
    }

    /**
     * @param float $lat1
     * @param float $lat2
     * @param float $lng1
     * @param float $lng2
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param bool $filterActive
     * @return SqlWhere
     */
    public static function getWhereForMap(float $lat1, float $lat2, float $lng1, float $lng2, array $types = [], bool $openNow = false, bool $filterActive = true): SqlWhere
    {
        $where = new SqlWhere();

        $where->addParameter(min($lat1, $lat2));
        $where->addParameter(max($lat1, $lat2));
        $where->addParameter(min($lng1, $lng2));
        $where->addParameter(max($lng1, $lng2));

        $where->addStatement('lat BETWEEN ? AND ?');
        $where->addStatement('lng BETWEEN ? AND ?');

        return self::commonWhereStatements($where, $types, $openNow, Locality::UnitedKingdom, null, $filterActive);
        ;
    }

    /**
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param ?PosInt $townId
     * @param bool $filterActive
     * @return SqlWhere
     */
    public static function getWhereForList(?int $townId = null, array $types = [], bool $openNow = false, bool $filterActive = true): SqlWhere
    {
        $where = new SqlWhere();
        return self::commonWhereStatements($where, $types, $openNow, Locality::UnitedKingdom, $townId, $filterActive);
    }

    /**
     * @param list<PosInt> $favourites
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param ?PosInt $townId
     * @param bool $filterActive
     * @return SqlWhere
     */
    public static function getWhereForFavs(array $favourites, ?int $townId = null, array $types = [], bool $openNow = false, bool $filterActive = true): SqlWhere
    {
        $where = new SqlWhere();

        if ($favourites !== []) {
            $where->addStatement('(id = ' . implode(' OR id = ', $favourites) . ')');
        }

        return self::commonWhereStatements($where, $types, $openNow, Locality::UnitedKingdom, $townId, $filterActive);
    }

    /**
     * @param non-empty-string $extId
     * @return ?VenueFullFromDatabase
     */
    public function findFullByExtId(string $extId): ?array
    {
        /** @phpstan-var ?VenueFullFromDatabase */
        $res = $this->findByExtId($extId, self::COLUMNS_FULL);
        return $res;
    }

    /**
     * @param non-empty-string $extId
     * @return ?VenueMinimalFromDatabase
     */
    public function findMinimalByExtId(string $extId): ?array
    {
        /** @phpstan-var ?VenueMinimalFromDatabase */
        $res = $this->findByExtId($extId, self::COLUMNS_MINIMAL);
        return $res;
    }

    /**
     * @param non-empty-string $humanUrl
     * @return ?VenueFullFromDatabase
     */
    public function findFullByHumanUrl(string $humanUrl): ?array
    {
        /** @phpstan-var ?VenueFullFromDatabase */

        $res = $this->genericRepository->findRow(
            self::COLUMNS_FULL,
            Venue::TABLE,
            'human_url',
            $humanUrl,
        );

        return $res;
    }

    /**
     * @param non-empty-string $extId
     * @return ?PosInt
     */
    public function findIdByExtId(string $extId): ?int
    {
        $res = $this->genericRepository->findValue(
            'id',
            Venue::TABLE,
            'ext_id',
            $extId,
        );

        assert(
            $res === null
            || (is_int($res) && $res > 0),
            'ID must be null  or positive int'
        );

        return $res;
    }

    /**
     * @param SqlWhere $where
     * @param PosInt $page
     * @param PosInt $pageSize
     * @param VenueOrderBy $orderBy
     * @return list<VenueMinimalFromDatabase>
     */
    public function fetch(int $page, int $pageSize, SqlWhere $where, VenueOrderBy $orderBy): array
    {
        /** @phpstan-var list<VenueMinimalFromDatabase> */

        $res = $this->genericRepository->fetchRowsWithSqlWhere(
            self::COLUMNS_MINIMAL,
            Venue::TABLE,
            $where,
            $orderBy->getString(),
            $pageSize,
            $pageSize * ($page - 1),
        );

        return $res;
    }

    /**
     * @param PosInt $townId
     * @param PosInt $page
     * @param PosInt $pageSize
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param VenueOrderBy $orderBy
     * @return VenuePageRaw
     */
    public function getVenuePageRawForList(
        int $townId,
        int $page,
        int $pageSize,
        array $types,
        bool $openNow,
        VenueOrderBy $orderBy,
    ): array {
        $where = self::getWhereForList($townId, $types, $openNow);
        $totalPages = $this->getTotalPages($pageSize, $where);

        if (!GenericRepository::pageHasItems($totalPages, $page)) {
            $items = [];

        } else {
            $items = $this->fetch(
                $page,
                $pageSize,
                $where,
                $orderBy,
            );
        }

        return [$totalPages, $items];
    }

    /**
     * @param PosInt $pageSize
     * @param SqlWhere $where
     * @return NonNegInt
     */
    public function getTotalPages(int $pageSize, SqlWhere $where): int
    {
        return $this->genericRepository->getTotalPages($where, $pageSize);
    }

    /**
     * @param list<PosInt> $favourites
     * @param PosInt $page
     * @param PosInt $pageSize
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param VenueOrderBy $orderBy
     * @return VenuePageRaw
     */
    public function getVenuePageRawForFavs(array $favourites, int $page, int $pageSize, array $types, bool $openNow, VenueOrderBy $orderBy): array
    {
        if ($favourites === []) {
            return [0, []];
        }

        $where = self::getWhereForFavs($favourites, null, $types, $openNow, false);
        $totalPages = $this->genericRepository->getTotalPages($where, $pageSize);

        $items = $this->fetch(
            $page,
            $pageSize,
            $where,
            $orderBy,
        );

        return [$totalPages, $items];
    }

    /**
     * @param float $lat1
     * @param float $lat2
     * @param float $lng1
     * @param float $lng2
     * @param list<VenueType> $types
     * @param bool $openNow
     * @return list<VenueMapRaw>
     */
    public function fetchForMap(float $lat1, float $lat2, float $lng1, float $lng2, array $types, bool $openNow): array
    {
        /**
         * @phpstan-var list<array{
         *     ext_id: non-empty-string,
         *     lat: string,
         *     lng: string,
         * }>
         */

        $items = $this->genericRepository->fetchRowsWithSqlWhere(
            self::COLUMNS_MAP,
            Venue::TABLE,
            self::getWhereForMap($lat1, $lat2, $lng1, $lng2, $types, $openNow),
            'lat DESC',
        );

        foreach ($items as &$venue) {
            $venue['lat'] = (float) $venue['lat'];
            $venue['lng'] = (float) $venue['lng'];
        }

        /**
         * @phpstan-var list<array{
         *     ext_id: non-empty-string,
         *     lat: float,
         *     lng: float,
         * }>
         */

        return $items;
    }

    /**
     * @param SqlWhere $where
     * @param list<VenueType> $types
     * @param bool $openNow
     * @param Locality $locality
     * @param ?PosInt $townId
     * @param bool $filterActive
     * @return SqlWhere
     */
    private static function commonWhereStatements(SqlWhere $where, array $types, bool $openNow, Locality $locality, ?int $townId = null, bool $filterActive = true): SqlWhere
    {
        // Don't return venues that are permanently closed
        if ($filterActive) {
            $where->addStatement('active = true');
        }

        if ($townId !== null) {
            $where->addStatement("town_id = {$townId}");
        }

        switch (count($types)) {
            case 1: $where->addStatement("{$types[0]->dbColumn()} = true");
                break;
            case 2: $where->addStatement("({$types[0]->dbColumn()} = true OR {$types[1]->dbColumn()} = true)");
                break;
        }

        if ($openNow) {
            $now    = $locality->now();
            $day    = (int) $now->format('w');
            $open   = Venue::COLUMN_PREFIX_OPEN_TIME . $day;
            $close  = Venue::COLUMN_PREFIX_CLOSE_TIME . $day;

            $where->addParameter($now->format('H:i:s'));
            $where->addStatement("CAST(? AS TIME) BETWEEN {$open} AND {$close}");
        }

        return $where;
    }

    /**
     * @param non-empty-string $extId
     * @param non-empty-string $columns
     * @return VenueFullFromDatabase|VenueMinimalFromDatabase|null
     */
    private function findByExtId(string $extId, string $columns): ?array
    {
        /** @phpstan-var VenueFullFromDatabase|VenueMinimalFromDatabase|null */

        $res = $this->genericRepository->findRow(
            $columns,
            Venue::TABLE,
            'ext_id',
            $extId,
        );

        return $res;
    }
}

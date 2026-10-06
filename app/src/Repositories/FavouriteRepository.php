<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use App\Types\StandardTypes;

/**
 * @phpstan-import-type PosInt from StandardTypes
 *
 * @phpstan-type FavouritesArray list<PosInt>
 */
class FavouriteRepository
{
    public function __construct(
        protected Database $database,
    ) {
    }

    /**
     * Summary of save
     * @param PosInt $appUserId
     * @param PosInt $venueId
     * @param non-empty-string $venueExtId
     * @return int
     */
    public function save(int $appUserId, int $venueId, string $venueExtId): int
    {
        $favouriteId = $this->database->insert(
            'INSERT INTO favourite (app_user_id, venue_id, venue_ext_id, created_at)
            VALUES (?, ?, ?, NOW())',
            [
                $appUserId,
                $venueId,
                $venueExtId,
            ],
        );

        return $favouriteId;
    }

    /**
     * @param PosInt $appUserId
     * @param PosInt $venueId
     * @return bool
     */
    public function delete(int $appUserId, int $venueId): bool
    {
        $rows = $this->database->action(
            'UPDATE favourite SET active = false
            WHERE active = true AND app_user_id = ? AND venue_id = ?',
            [
                $appUserId,
                $venueId,
            ]
        );

        return $rows > 0
            ? true
            : false;
    }

    /**
     * @param PosInt $appUserId
     * @return FavouritesArray
     */
    public function fetchForUser(int $appUserId): array
    {
        /** @var FavouritesArray $res */

        $res =  $this->database->fetchAllColumn(
            'SELECT venue_id FROM favourite WHERE app_user_id = ?',
            [$appUserId]
        );

        return $res;
    }
}

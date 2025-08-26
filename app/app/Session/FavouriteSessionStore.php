<?php

declare(strict_types=1);

namespace App\Session;

use App\Repositories\FavouriteRepository;
use App\Types\StandardTypes;

/**
 * @phpstan-import-type PosInt from StandardTypes
 * @phpstan-import-type FavouritesArray from FavouriteRepository
 */
class FavouriteSessionStore
{
    protected const string SESSION_KEY = 'appUserFavourites';

    public function __construct(
        protected Session $session,
        protected FavouriteRepository $favouriteRepository,
        protected Auth $auth,
    ) {
    }

    /**
     * @return FavouritesArray
     */
    public function getAll(): array
    {
        $appUserId = $this->auth->id();

        if ($appUserId === null) {
            return [];
        }

        $res = $this->session->get(self::SESSION_KEY);

        assert(
            $res === null || is_array($res),
            'Favourites must be null or array'
        );

        /** @phpstan-var ?FavouritesArray $res */

        return $res ?? $this->refresh($appUserId);
    }

    /**
     * @param PosInt $venueId
     * @param non-empty-string $extId
     * @return bool
     */
    public function add(int $venueId, string $extId): bool
    {
        $appUserId = $this->auth->id();

        if ($appUserId === null) {
            return false;
        }

        $this->favouriteRepository->save($appUserId, $venueId, $extId);

        $favourites = $this->getAll();
        $favourites[] = $venueId;
        $this->session->put(self::SESSION_KEY, $favourites);

        return true;
    }

    /**
     * @param PosInt $venueId
     * @return bool
     */
    public function delete(int $venueId): bool
    {
        $appUserId = $this->auth->id();

        if ($appUserId === null) {
            return false;
        }

        $res = $this->favouriteRepository->delete($appUserId, $venueId);

        if ($res === false) {
            return false;
        }

        $this->session->put(
            self::SESSION_KEY,
            array_diff($this->getAll(), [$venueId])
        );

        return true;
    }

    /**
     * @param PosInt $appUserId
     * @return FavouritesArray
     */
    public function refresh(int $appUserId): array
    {
        $favourites = $this->favouriteRepository->fetchForUser($appUserId);
        $this->session->put(self::SESSION_KEY, $favourites);

        return $favourites;
    }
}

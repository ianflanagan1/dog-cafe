<?php

declare(strict_types=1);

namespace App\Session;

use App\Models\Town;

/**
 * Session-backed store for tracking which town to centre on for the current user.
 *
 * Encapsulates two pieces of per-user state:
 * 1. The most recently specified town by the user.
 * 2. The town inferred from the user's IP address.
 */
readonly class TownSessionStore
{
    protected const string SESSION_KEY_SEARCH_TOWN = 'lastSpecifiedTown';
    protected const string SESSION_KEY_IP_TOWN = 'ipTown';

    public function __construct(
        protected Session $session,
    ) {
    }

    // 1. Most recent town specified by the user

    public function saveLastSpecifiedTown(Town $town): void
    {
        $this->session->put(self::SESSION_KEY_SEARCH_TOWN, $town);
    }

    public function deleteLastSpecifiedTown(): void
    {
        $this->session->forget(self::SESSION_KEY_SEARCH_TOWN);
    }

    public function findLastSpecifiedTown(): ?Town
    {
        return $this->findTownFromSession(self::SESSION_KEY_SEARCH_TOWN);
    }

    // 2. Closest town to the user's IP address

    public function saveIpTown(Town $town): void
    {
        $this->session->put(self::SESSION_KEY_IP_TOWN, $town);
    }

    public function deleteIpTown(): void
    {
        $this->session->forget(self::SESSION_KEY_IP_TOWN);
    }

    public function findIpTown(): ?Town
    {
        return $this->findTownFromSession(self::SESSION_KEY_IP_TOWN);
    }

    protected function findTownFromSession(string $key): ?Town
    {
        $town = $this->session->get($key);

        assert(
            $town === null || $town instanceof Town,
            '$town must be null or an instance of Town'
        );

        return $town;
    }
}

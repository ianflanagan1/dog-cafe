<?php

declare(strict_types=1);

namespace App\Session;

use App\Types\StandardTypes;

/**
 * Helper for managing named lists stored in the session.
 *
 * Provides operations to retrieve, add to, check for, and consume values from
 * session-backed lists while enforcing size limits and normalizing missing data
 * to empty arrays. Intended for lightweight per-user history or recent-item lists.
 *
 * @phpstan-import-type PosInt from StandardTypes
 */
class SessionListManager
{
    public function __construct(
        protected Session $session,
    ) {
    }

    /**
     * @param non-empty-string $key
     * @return list<mixed>
     */
    public function getAllValues(string $key): array
    {
        $res = $this->session->get($key);

        assert(
            $res === null || is_array($res),
            'List must be null or array'
        );

        return is_array($res)
            ? $res
            : [];
    }

    /**
     * @param non-empty-string $key
     * @param mixed $value
     * @param PosInt $max
     * @return void
     */
    public function addValue(string $key, mixed $value, int $max): void
    {
        $list = $this->getAllValues($key);
        $list[] = $value;

        // If exceeds $max, trim oldest values
        if (count($list) > $max) {
            $list = array_slice($list, -$max);
        }

        $this->session->put($key, $list);
    }

    /**
     * @param non-empty-string $key
     * @param mixed $value
     * @return bool
     */
    public function hasValue(string $key, mixed $value): bool
    {
        $list = $this->getAllValues($key);

        return array_search($value, $list, true) === false
            ? false
            : true;
    }

    /**
     * @param non-empty-string $key
     * @param mixed $value
     * @return bool
     */
    public function consumeValue(string $key, mixed $value): bool
    {
        $list = $this->getAllValues($key);
        $index = array_search($value, $list, true);

        if ($index === false) {
            return false;
        }

        array_splice($list, $index, 1);
        $this->session->put($key, $list);

        return true;
    }
}

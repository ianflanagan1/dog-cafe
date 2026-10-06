<?php

declare(strict_types=1);

namespace App;

use DateInterval;
use DateTime;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Redis;
use Stringable;

readonly class RedisCache implements CacheInterface
{
    /**
     * @param Redis $redis,
     * @param array{
     *      host: string,
     *      port: int,
     *      password: string,
     * } $config
     */
    public function __construct(
        protected Redis $redis,
        array $config
    ) {
        $this->redis->connect((string) $config['host'], $config['port']);
        $this->redis->auth((string) $config['password']);
    }

    /**
     * @param non-empty-string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);
        return $value === false ? $default : $value;
    }

    /**
     * @param non-empty-string $key
     * @param mixed $value
     * @param null|int|DateInterval $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->assertRedisCompatibleValue($value);

        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime('@0'))->add($ttl)->getTimestamp();
        } elseif ($ttl === null) {
            $ttl = 0;
        }

        if ($ttl > 0) {
            return (bool) $this->redis->set($key, $value, $ttl);
        }

        return (bool) $this->redis->set($key, $value);
    }

    /**
     * @param non-empty-string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->redis->del($key) === 1;
    }

    public function clear(): bool
    {
        return $this->redis->flushDB();
    }

    /**
     * @param iterable<non-empty-string> $keys
     * @param mixed $default
     * @return iterable<non-empty-string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keys = (array) $keys;
        $values = $this->redis->mGet($keys);
        $results = [];

        foreach ($values as $index => $value) {
            $results[$keys[$index]] = $value === false ? $default : $value;
        }

        return $results;
    }

    /**
     * @param iterable<non-empty-string, mixed> $values
     * @param null|int|\DateInterval $ttl
     * @return bool
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $values = (array) $values;

        foreach ($values as $value) {
            $this->assertRedisCompatibleValue($value);
        }

        $result = $this->redis->mSet($values);

        if ($ttl !== null) {
            if ($ttl instanceof DateInterval) {
                $ttl = (new DateTime('@0'))->add($ttl)->getTimestamp();
            }

            foreach (array_keys($values) as $key) {
                $this->redis->expire($key, (int) $ttl);
            }
        }

        return $result;
    }

    /**
     * @param iterable<non-empty-string> $keys
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $keys = (array) $keys;

        return $this->redis->del($keys) === count($keys);
    }

    /**
     * @param non-empty-string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return (bool) $this->redis->exists($key);
    }

    protected function assertRedisCompatibleValue(mixed $value): void
    {
        if (!is_scalar($value) && !$value instanceof Stringable && $value !== null) {

            if (is_object($value)) {
                $type = get_class($value);
            } else {
                $type = gettype($value);
            }

            throw new InvalidArgumentException('Only scalar, Stringable, or null values are supported for Redis storage. Type: ' . $type);
        }
    }
}

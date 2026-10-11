<?php

declare(strict_types=1);

namespace App\Framework\Cache;

use Closure;
use DateInterval;
use DateTime;
use InvalidArgumentException;
use Override;
use Psr\SimpleCache\CacheInterface;
use Redis;
use Stringable;

class RedisCache implements CacheInterface
{
    private ?Redis $redis = null;

    /**
     * @param Closure(): Redis $redisFactory Returns a connected client. Called on first use, so injecting the cache costs nothing until it's used
     */
    public function __construct(
        private readonly Closure $redisFactory,
    ) {
    }

    #[Override]
    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertValidKey($key);

        $value = $this->connection()->get($key);

        return $value === false
            ? $default
            : $value;
    }

    #[Override]
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->assertValidKey($key);
        $this->assertRedisCompatibleValue($value);

        if ($ttl instanceof DateInterval) {
            $ttl = new DateTime('@0')->add($ttl)->getTimestamp();
        } elseif ($ttl === null) {
            $ttl = 0;
        }

        if ($ttl > 0) {
            return (bool) $this->connection()->set($key, $value, ['EX' => $ttl]);
        }

        return (bool) $this->connection()->set($key, $value);
    }

    #[Override]
    public function delete(string $key): bool
    {
        $this->assertValidKey($key);

        return $this->connection()->del($key) === 1;
    }

    #[Override]
    public function clear(): bool
    {
        return $this->connection()->flushDB();
    }

    #[Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keys = iterator_to_array($keys, false);
        array_walk($keys, $this->assertValidKey(...));
        $values = $this->connection()->mGet($keys);
        $results = [];

        // mGet() returns values in the same order as the keys
        foreach (array_combine($keys, $values) as $key => $value) {
            $results[$key] = $value === false
                ? $default
                : $value;
        }

        return $results;
    }

    /**
     * @param iterable<mixed, mixed> $values
     */
    #[Override]
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $values = iterator_to_array($values);

        foreach ($values as $key => $value) {
            // PHP stores numeric string keys like '123' as ints
            $this->assertValidKey((string) $key);
            $this->assertRedisCompatibleValue($value);
        }

        $result = $this->connection()->mSet($values);

        if ($ttl !== null) {
            if ($ttl instanceof DateInterval) {
                $ttl = new DateTime('@0')->add($ttl)->getTimestamp();
            }

            foreach (array_keys($values) as $key) {
                $this->connection()->expire($key, (int) $ttl);
            }
        }

        return $result;
    }

    #[Override]
    public function deleteMultiple(iterable $keys): bool
    {
        $keys = iterator_to_array($keys, false);
        array_walk($keys, $this->assertValidKey(...));

        return $this->connection()->del($keys) === count($keys);
    }

    #[Override]
    public function has(string $key): bool
    {
        $this->assertValidKey($key);

        return (bool) $this->connection()->exists($key);
    }

    /**
     * @phpstan-assert non-empty-string $key
     *
     * @throws InvalidKeyException PSR-16 requires keys of at least one character
     */
    protected function assertValidKey(mixed $key): void
    {
        if (!is_string($key) || $key === '') {
            throw new InvalidKeyException('Cache keys must be non-empty strings. Got: ' . var_export($key, true));
        }
    }

    protected function assertRedisCompatibleValue(mixed $value): void
    {
        if (!is_scalar($value) && !$value instanceof Stringable && $value !== null) {
            throw new InvalidArgumentException('Only scalar, Stringable, or null values are supported for Redis storage. Type: ' . get_debug_type($value));
        }
    }

    private function connection(): Redis
    {
        if ($this->redis === null) {
            $redis = ($this->redisFactory)();

            // assertRedisCompatibleValue() and get() assume values go in and come out as plain strings
            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);

            $this->redis = $redis;
        }

        return $this->redis;
    }
}

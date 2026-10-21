<?php

declare(strict_types=1);

namespace App\Framework\Cache;

use Closure;
use DateInterval;
use DateTime;
use Override;
use Psr\SimpleCache\CacheInterface;
use Redis;
use RuntimeException;

/**
 * A PSR-16 cache in Redis. Values of any serializable type come back as they were stored.
 */
class RedisCache implements CacheInterface
{
    /**
     * Prepended to every key in Redis, so clear() can remove the cache's items alone: sessions share the database,
     * and production disables FLUSHDB (deploy/redis/redis.conf). The `:` is Redis's namespacing convention. PSR-16
     * reserves it only in the keys callers pass.
     */
    private const string PREFIX = 'cache:';

    /**
     * Legal keys: the characters every PSR-16 implementation must accept (A-Z, a-z, 0-9, _ and .), which excludes the
     * ones it reserves ({}()/\@:). Separate namespace segments with `.`
     */
    private const string KEY_PATTERN = '/^[A-Za-z0-9_.]+$/D';

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

        return $this->unserialize($this->connection()->get(self::PREFIX . $key), $default);
    }

    #[Override]
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->assertValidKey($key);

        $seconds = self::seconds($ttl);

        if ($seconds !== null && $seconds <= 0) {
            // PSR-16: an item whose TTL is zero or less has already expired
            return $this->deleteRedisKeys([self::PREFIX . $key]);
        }

        $result = $seconds === null
            ? $this->connection()->set(self::PREFIX . $key, serialize($value))
            : $this->connection()->set(self::PREFIX . $key, serialize($value), ['EX' => $seconds]);

        return $result === true;
    }

    #[Override]
    public function delete(string $key): bool
    {
        $this->assertValidKey($key);

        return $this->deleteRedisKeys([self::PREFIX . $key]);
    }

    #[Override]
    public function clear(): bool
    {
        $redis = $this->connection();
        $iterator = null;

        // scan() returns false once the iteration is complete, and may return no keys before then
        while (($redisKeys = $redis->scan($iterator, self::PREFIX . '*')) !== false) {
            if ($redisKeys !== [] && !is_int($redis->unlink($redisKeys))) {
                return false;
            }
        }

        return true;
    }

    #[Override]
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keys = $this->validKeys($keys);

        if ($keys === []) {
            return [];
        }

        $values = $this->connection()->mget(array_map(fn (string $key): string => self::PREFIX . $key, $keys));

        if (!is_array($values)) {
            throw new RuntimeException('Redis MGET failed');
        }

        $results = [];

        // mget() returns values in the same order as the keys
        foreach (array_combine($keys, $values) as $key => $value) {
            $results[$key] = $this->unserialize($value, $default);
        }

        return $results;
    }

    /**
     * @param iterable<mixed, mixed> $values
     */
    #[Override]
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $serialized = [];

        foreach ($values as $key => $value) {
            // PHP stores numeric string keys like '123' as ints
            $key = is_int($key)
                ? (string) $key
                : $key;

            $this->assertValidKey($key);
            $serialized[self::PREFIX . $key] = serialize($value);
        }

        if ($serialized === []) {
            return true;
        }

        $seconds = self::seconds($ttl);

        if ($seconds !== null && $seconds <= 0) {
            // PSR-16: an item whose TTL is zero or less has already expired
            return $this->deleteRedisKeys(array_keys($serialized));
        }

        $result = $this->connection()->mset($serialized) === true;

        if ($seconds !== null) {
            foreach (array_keys($serialized) as $redisKey) {
                $this->connection()->expire($redisKey, $seconds);
            }
        }

        return $result;
    }

    #[Override]
    public function deleteMultiple(iterable $keys): bool
    {
        $redisKeys = array_map(fn (string $key): string => self::PREFIX . $key, $this->validKeys($keys));

        return $redisKeys === []
            || $this->deleteRedisKeys($redisKeys);
    }

    #[Override]
    public function has(string $key): bool
    {
        $this->assertValidKey($key);

        return $this->connection()->exists(self::PREFIX . $key) === 1;
    }

    /**
     * @phpstan-assert non-empty-string $key
     *
     * @throws InvalidKeyException PSR-16 requires keys to be strings of at least one character. This accepts the
     *                             characters every PSR-16 implementation must, and rejects the ones it reserves
     */
    protected function assertValidKey(mixed $key): void
    {
        if (!is_string($key) || preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw new InvalidKeyException('Cache keys must match ' . self::KEY_PATTERN . '. Got: ' . var_export($key, true));
        }
    }

    /**
     * @param iterable<mixed> $keys
     * @return list<non-empty-string>
     */
    private function validKeys(iterable $keys): array
    {
        $valid = [];

        foreach ($keys as $key) {
            $this->assertValidKey($key);
            $valid[] = $key;
        }

        return $valid;
    }

    /**
     * Succeeds whether or not the keys existed
     *
     * @param non-empty-list<string> $redisKeys
     */
    private function deleteRedisKeys(array $redisKeys): bool
    {
        return is_int($this->connection()->del($redisKeys));
    }

    /**
     * @param mixed $value What Redis returned: the serialized value, or false for a missing key
     */
    private function unserialize(mixed $value, mixed $default): mixed
    {
        return is_string($value)
            ? unserialize($value)
            : $default;
    }

    private static function seconds(null|int|DateInterval $ttl): ?int
    {
        return $ttl instanceof DateInterval
            ? new DateTime('@0')->add($ttl)->getTimestamp()
            : $ttl;
    }

    private function connection(): Redis
    {
        if ($this->redis === null) {
            $redis = ($this->redisFactory)();

            // Values are serialized here, so the client must store and return them as they are
            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);

            $this->redis = $redis;
        }

        return $this->redis;
    }
}

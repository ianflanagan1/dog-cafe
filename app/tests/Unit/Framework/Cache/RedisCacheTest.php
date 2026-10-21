<?php

declare(strict_types=1);

namespace Tests\Unit\Framework\Cache;

use App\Framework\Cache\RedisCache;
use Closure;
use DateInterval;
use Generator;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInvalidArgumentException;
use Redis;
use stdClass;

/**
 * RedisCache runs against an in-memory stand-in for Redis, so each test states what the cache does rather than which
 * Redis calls it makes.
 */
final class RedisCacheTest extends TestCase
{
    private RedisCache $cache;

    private int $factoryCalls = 0;

    /** @var array<string, string> Everything in the fake Redis database, keyed as Redis sees it */
    private array $stored = [];

    /** @var array<string, int> Seconds until expiry, for keys that have one */
    private array $expiries = [];

    /** @var array<int, mixed> */
    private array $clientOptions = [];

    #[Override]
    protected function setUp(): void
    {
        $redis = $this->fakeRedis();

        $this->cache = new RedisCache(
            function () use ($redis): Redis {
                $this->factoryCalls++;
                return $redis;
            },
        );
    }

    public function test_constructing_the_cache_does_not_connect(): void
    {
        $this->assertSame(0, $this->factoryCalls);
    }

    public function test_the_connection_is_made_once_on_first_use(): void
    {
        $this->cache->get('key1');
        $this->cache->get('key2');

        $this->assertSame(1, $this->factoryCalls);
    }

    public function test_the_client_is_told_not_to_serialize_because_the_cache_serializes_values_itself(): void
    {
        $this->cache->get('key');

        $this->assertSame(Redis::SERIALIZER_NONE, $this->clientOptions[Redis::OPT_SERIALIZER] ?? null);
    }

    public function test_a_missing_item_returns_the_default(): void
    {
        $this->assertNull($this->cache->get('missing'));
        $this->assertSame('default', $this->cache->get('missing', 'default'));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function valuesOfEveryType(): array
    {
        $object = new stdClass();
        $object->name = 'value';

        return [
            'string' => ['value'],
            'numeric string' => ['5'],
            'int' => [5],
            'float' => [1.5],
            'true' => [true],
            'false' => [false],
            'null' => [null],
            'array' => [['a' => 1, 'b' => [2, 3]]],
            'object' => [$object],
        ];
    }

    #[DataProvider('valuesOfEveryType')]
    public function test_a_stored_value_comes_back_with_its_type_intact(mixed $value): void
    {
        $this->cache->set('key', $value);

        $this->assertEquals($value, $this->cache->get('key', 'default'));
        $this->assertSame(get_debug_type($value), get_debug_type($this->cache->get('key', 'default')));
    }

    public function test_an_item_set_without_a_ttl_never_expires(): void
    {
        $this->cache->set('key', 'value');

        $this->assertSame([], $this->expiries);
    }

    /**
     * @return array<string, array{int|DateInterval}>
     */
    public static function oneHourTtls(): array
    {
        return [
            'seconds' => [3600],
            'interval' => [new DateInterval('PT1H')],
        ];
    }

    #[DataProvider('oneHourTtls')]
    public function test_an_item_set_with_a_ttl_expires_after_it(int|DateInterval $ttl): void
    {
        $this->assertTrue($this->cache->set('key', 'value', $ttl));

        $this->assertSame('value', $this->cache->get('key'));
        $this->assertSame(['cache:key' => 3600], $this->expiries);
    }

    /**
     * @return array<string, array{int|DateInterval}>
     */
    public static function expiredTtls(): array
    {
        $negativeInterval = new DateInterval('PT1H');
        $negativeInterval->invert = 1;

        return [
            'zero' => [0],
            'negative seconds' => [-1],
            'negative interval' => [$negativeInterval],
        ];
    }

    #[DataProvider('expiredTtls')]
    public function test_setting_an_item_with_a_ttl_of_zero_or_less_removes_it(int|DateInterval $ttl): void
    {
        $this->cache->set('key', 'old');

        $this->assertTrue($this->cache->set('key', 'new', $ttl));

        $this->assertFalse($this->cache->has('key'));
    }

    public function test_deleting_an_item_removes_it(): void
    {
        $this->cache->set('key', 'value');

        $this->assertTrue($this->cache->delete('key'));

        $this->assertFalse($this->cache->has('key'));
    }

    public function test_deleting_a_missing_item_succeeds(): void
    {
        $this->assertTrue($this->cache->delete('missing'));
    }

    public function test_has_reports_whether_an_item_is_stored(): void
    {
        $this->cache->set('key', null);

        $this->assertTrue($this->cache->has('key'));
        $this->assertFalse($this->cache->has('missing'));
    }

    public function test_clear_removes_every_cached_item_and_nothing_else_in_the_redis_database(): void
    {
        $this->stored['PHPREDIS_SESSION:abc'] = 'session data';
        $this->cache->set('key1', 'value');
        $this->cache->set('key2', 'value');

        $this->assertTrue($this->cache->clear());

        $this->assertFalse($this->cache->has('key1'));
        $this->assertFalse($this->cache->has('key2'));
        $this->assertSame(['PHPREDIS_SESSION:abc' => 'session data'], $this->stored);
    }

    public function test_get_multiple_returns_each_value_or_the_default(): void
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key3', 3);

        $expected = [
            'key1' => 'value1',
            'key2' => 'default',
            'key3' => 3,
        ];

        $this->assertSame($expected, $this->cache->getMultiple(['key1', 'key2', 'key3'], 'default'));
        $this->assertSame($expected, $this->cache->getMultiple(self::generate(['key1', 'key2', 'key3']), 'default'));
    }

    public function test_set_multiple_stores_every_value(): void
    {
        $this->assertTrue($this->cache->setMultiple(['key1' => 'value1', 'key2' => 2]));
        $this->assertTrue($this->cache->setMultiple(self::generate(['key3' => 'value3'])));

        $this->assertSame(
            ['key1' => 'value1', 'key2' => 2, 'key3' => 'value3'],
            $this->cache->getMultiple(['key1', 'key2', 'key3']),
        );
    }

    public function test_set_multiple_accepts_numeric_string_keys_that_php_stores_as_ints(): void
    {
        $this->assertTrue($this->cache->setMultiple(['123' => 'value']));

        $this->assertSame('value', $this->cache->get('123'));
    }

    #[DataProvider('oneHourTtls')]
    public function test_set_multiple_with_a_ttl_expires_every_item_after_it(int|DateInterval $ttl): void
    {
        $this->cache->setMultiple(['key1' => 'value1', 'key2' => 'value2'], $ttl);

        $this->assertSame(['cache:key1' => 3600, 'cache:key2' => 3600], $this->expiries);
    }

    #[DataProvider('expiredTtls')]
    public function test_set_multiple_with_a_ttl_of_zero_or_less_removes_the_items(int|DateInterval $ttl): void
    {
        $this->cache->set('key1', 'old');

        $this->assertTrue($this->cache->setMultiple(['key1' => 'new', 'key2' => 'new'], $ttl));

        $this->assertSame([], $this->stored);
    }

    public function test_delete_multiple_removes_the_items_and_succeeds_when_some_were_missing(): void
    {
        $this->cache->set('key1', 'value');
        $this->cache->set('key2', 'value');
        $this->cache->set('kept', 'value');

        $this->assertTrue($this->cache->deleteMultiple(self::generate(['key1', 'key2', 'missing'])));

        $this->assertSame(['key1' => null, 'key2' => null, 'kept' => 'value'], $this->cache->getMultiple(['key1', 'key2', 'kept']));
    }

    public function test_operations_on_no_keys_succeed(): void
    {
        $this->assertSame([], $this->cache->getMultiple([]));
        $this->assertTrue($this->cache->setMultiple([]));
        $this->assertTrue($this->cache->deleteMultiple([]));
    }

    public function test_keys_made_of_letters_digits_underscores_and_dots_are_accepted(): void
    {
        $key = 'AZaz09_.' . str_repeat('x', 100);

        $this->cache->set($key, 'value');

        $this->assertSame('value', $this->cache->get($key));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function keysOutsideThePortablePsr16Set(): array
    {
        return [
            'empty' => [''],
            'colon (reserved)' => ['a:b'],
            'braces (reserved)' => ['a{b}'],
            'parentheses (reserved)' => ['a(b)'],
            'slash (reserved)' => ['a/b'],
            'backslash (reserved)' => ['a\\b'],
            'at sign (reserved)' => ['a@b'],
            'space' => ['a b'],
            'comma' => ['a,b'],
            'hyphen' => ['a-b'],
            'trailing newline' => ["a\n"],
            'non-ASCII letter' => ['café'],
        ];
    }

    #[DataProvider('keysOutsideThePortablePsr16Set')]
    public function test_keys_outside_the_portable_psr16_set_are_rejected(string $key): void
    {
        $this->expectException(SimpleCacheInvalidArgumentException::class);

        $this->cache->get($key);
    }

    /**
     * @return array<string, array{Closure(RedisCache): mixed}>
     */
    public static function callsWithAnIllegalKey(): array
    {
        return [
            'get' => [static fn (RedisCache $cache): mixed => $cache->get('a:b')],
            'set' => [static fn (RedisCache $cache): bool => $cache->set('a:b', 'value')],
            'delete' => [static fn (RedisCache $cache): bool => $cache->delete('a:b')],
            'has' => [static fn (RedisCache $cache): bool => $cache->has('a:b')],
            'getMultiple' => [static fn (RedisCache $cache): iterable => $cache->getMultiple(['key1', 'a:b'])],
            'getMultiple with a non-string key' => [static fn (RedisCache $cache): iterable => $cache->getMultiple([1])], // @phpstan-ignore argument.type (the int key is the illegal input)
            'setMultiple' => [static fn (RedisCache $cache): bool => $cache->setMultiple(['key1' => 'value', 'a:b' => 'value'])],
            'deleteMultiple' => [static fn (RedisCache $cache): bool => $cache->deleteMultiple(['key1', 'a:b'])],
        ];
    }

    /**
     * @param Closure(RedisCache): mixed $call
     */
    #[DataProvider('callsWithAnIllegalKey')]
    public function test_every_operation_rejects_an_illegal_key(Closure $call): void
    {
        $this->expectException(SimpleCacheInvalidArgumentException::class);

        $call($this->cache);
    }

    /**
     * A stand-in for the phpredis client, covering the calls RedisCache makes, backed by $stored and $expiries
     */
    private function fakeRedis(): Redis
    {
        $redis = self::createStub(Redis::class);

        $redis->method('setOption')->willReturnCallback(function (int $option, mixed $value): bool {
            $this->clientOptions[$option] = $value;
            return true;
        });

        $redis->method('get')->willReturnCallback(
            fn (string $key): string|false => $this->stored[$key] ?? false,
        );

        $redis->method('set')->willReturnCallback(function (string $key, mixed $value, mixed $options = null): bool {
            self::assertIsString($value);
            $this->stored[$key] = $value;
            unset($this->expiries[$key]);

            if (is_array($options) && isset($options['EX'])) {
                self::assertIsInt($options['EX']);
                $this->expiries[$key] = $options['EX'];
            }

            return true;
        });

        $redis->method('mget')->willReturnCallback($this->fakeMget(...));
        $redis->method('mset')->willReturnCallback($this->fakeMset(...));

        $redis->method('expire')->willReturnCallback(function (string $key, int $seconds): bool {
            if (!isset($this->stored[$key])) {
                return false;
            }

            $this->expiries[$key] = $seconds;
            return true;
        });

        $redis->method('del')->willReturnCallback($this->fakeRemove(...));
        $redis->method('unlink')->willReturnCallback($this->fakeRemove(...));

        $redis->method('exists')->willReturnCallback(
            fn (string $key): int => (int) isset($this->stored[$key]),
        );

        // Returns every match in one batch, then false to end the iteration as phpredis does
        $scanned = false;
        $redis->method('scan')->willReturnCallback(function (mixed $iterator, string $pattern) use (&$scanned): array|false {
            if ($scanned) {
                return false;
            }

            $scanned = true;

            return array_values(array_filter(array_keys($this->stored), fn (string $key): bool => fnmatch($pattern, $key)));
        });

        return $redis;
    }

    /**
     * @param list<string> $keys
     * @return list<string|false>
     */
    private function fakeMget(array $keys): array
    {
        return array_map(fn (string $key): string|false => $this->stored[$key] ?? false, $keys);
    }

    /**
     * @param array<string, string> $pairs
     */
    private function fakeMset(array $pairs): bool
    {
        foreach ($pairs as $key => $value) {
            $this->stored[$key] = $value;
            unset($this->expiries[$key]);
        }

        return true;
    }

    /**
     * @param list<string> $keys
     * @return int How many of the keys existed
     */
    private function fakeRemove(array $keys): int
    {
        $removed = 0;

        foreach ($keys as $key) {
            if (isset($this->stored[$key])) {
                unset($this->stored[$key], $this->expiries[$key]);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * @template TKey
     * @template TValue
     * @param array<TKey, TValue> $items
     * @return Generator<TKey, TValue>
     */
    private static function generate(array $items): Generator
    {
        yield from $items;
    }
}

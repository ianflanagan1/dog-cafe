<?php

declare(strict_types=1);

namespace Tests\Unit\Framework\Cache;

use App\Framework\Cache\RedisCache;
use Closure;
use DateInterval;
use Generator;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException as SimpleCacheInvalidArgumentException;
use Redis;
use stdClass;
use Stringable;

class RedisCacheTest extends TestCase
{
    /** @phpstan-var MockObject&Redis */
    protected MockObject&Redis $redisMock;

    protected RedisCache $cache;

    protected int $factoryCalls = 0;

    #[Override]
    protected function setUp(): void
    {
        $this->redisMock = $this->createMock(Redis::class);

        $this->cache = new RedisCache(
            function (): MockObject&Redis {
                $this->factoryCalls++;
                return $this->redisMock;
            },
        );
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_constructing_the_cache_does_not_connect(): void
    {
        $this->assertSame(0, $this->factoryCalls);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function test_the_connection_is_made_once_on_first_use(): void
    {
        $this->cache->get('key1');
        $this->cache->get('key2');

        $this->assertSame(1, $this->factoryCalls);
    }

    public function test_values_are_stored_unserialized_whatever_the_injected_client_was_configured_with(): void
    {
        $this->redisMock->expects($this->once())
            ->method('setOption')
            ->with(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);

        $this->cache->get('key');
    }

    public function test_get_returns_value_when_key_exists(): void
    {
        $this->redisMock->expects($this->once())
            ->method('get')
            ->with('test_key')
            ->willReturn('test_value');

        $result = $this->cache->get('test_key');
        $this->assertEquals('test_value', $result);
    }

    public function test_get_returns_null_default_when_key_does_not_exist(): void
    {
        $this->redisMock->expects($this->once())
            ->method('get')
            ->with('nonexistent_key')
            ->willReturn(false);

        $result = $this->cache->get('nonexistent_key');
        $this->assertNull($result);
    }

    public function test_get_returns_passed_default_when_key_does_not_exist(): void
    {
        $this->redisMock->expects($this->once())
            ->method('get')
            ->with('nonexistent_key')
            ->willReturn(false);

        $result = $this->cache->get('nonexistent_key', 'default_value');
        $this->assertEquals('default_value', $result);
    }

    public function test_set_without_ttl(): void
    {
        $this->redisMock->expects($this->once())
            ->method('set')
            ->with('test_key', 'test_value')
            ->willReturn(true);

        $result = $this->cache->set('test_key', 'test_value');
        $this->assertTrue($result);
    }

    public function test_set_with_integer_ttl(): void
    {
        $this->redisMock->expects($this->once())
            ->method('set')
            ->with('test_key', 'test_value', ['EX' => 3600])
            ->willReturn(true);

        $result = $this->cache->set('test_key', 'test_value', 3600);
        $this->assertTrue($result);
    }

    public function test_set_with_date_interval_ttl(): void
    {
        $interval = new DateInterval('PT1H'); // 1 hour

        $this->redisMock->expects($this->once())
            ->method('set')
            ->with('test_key', 'test_value', ['EX' => 3600])
            ->willReturn(true);

        $result = $this->cache->set('test_key', 'test_value', $interval);
        $this->assertTrue($result);
    }

    public function test_set_with_zero_ttl(): void
    {
        $this->redisMock->expects($this->once())
            ->method('set')
            ->with('test_key', 'test_value')
            ->willReturn(true);

        $result = $this->cache->set('test_key', 'test_value', 0);
        $this->assertTrue($result);
    }

    public function test_set_throws_exception_for_invalid_value(): void
    {
        $this->redisMock->expects($this->never())->method('set');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only scalar, Stringable, or null values are supported for Redis storage. Type: array');

        $this->cache->set('test_key', ['invalid' => 'array']);
    }

    public function test_set_accepts_stringable_object(): void
    {
        $stringable = new class () implements Stringable {
            #[Override]
            public function __toString(): string
            {
                return 'stringable_value';
            }
        };

        $this->redisMock->expects($this->once())
            ->method('set')
            ->with('test_key', $stringable)
            ->willReturn(true);

        $result = $this->cache->set('test_key', $stringable);
        $this->assertTrue($result);
    }

    public function test_set_accepts_null_value(): void
    {
        $this->redisMock->expects($this->once())
            ->method('set')
            ->with('test_key', null)
            ->willReturn(true);

        $result = $this->cache->set('test_key', null);
        $this->assertTrue($result);
    }

    public function test_delete_existing_key(): void
    {
        $this->redisMock->expects($this->once())
            ->method('del')
            ->with('test_key')
            ->willReturn(1);

        $result = $this->cache->delete('test_key');
        $this->assertTrue($result);
    }

    public function test_delete_non_existent_key(): void
    {
        $this->redisMock->expects($this->once())
            ->method('del')
            ->with('nonexistent_key')
            ->willReturn(0);

        $result = $this->cache->delete('nonexistent_key');
        $this->assertFalse($result);
    }

    public function test_clear(): void
    {
        $this->redisMock->expects($this->once())
            ->method('flushDB')
            ->willReturn(true);

        $result = $this->cache->clear();
        $this->assertTrue($result);
    }

    public function test_get_multiple(): void
    {
        $keys = ['key1', 'key2', 'key3'];
        $values = ['value1', false, 'value3'];

        $this->redisMock->expects($this->once())
            ->method('mget')
            ->with($keys)
            ->willReturn($values);

        $result = $this->cache->getMultiple($keys, 'default');

        $expected = [
            'key1' => 'value1',
            'key2' => 'default',
            'key3' => 'value3',
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_get_multiple_accepts_keys_from_a_generator(): void
    {
        $this->redisMock->expects($this->once())
            ->method('mget')
            ->with(['key1', 'key2'])
            ->willReturn(['value1', 'value2']);

        $result = $this->cache->getMultiple(self::generate(['key1', 'key2']));

        $expected = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $this->assertEquals($expected, $result);
    }

    public function test_set_multiple_without_ttl(): void
    {
        $values = ['key1' => 'value1', 'key2' => 'value2'];

        $this->redisMock->expects($this->once())
            ->method('mset')
            ->with($values)
            ->willReturn(true);

        $result = $this->cache->setMultiple($values);
        $this->assertTrue($result);
    }

    public function test_set_multiple_with_ttl(): void
    {
        $values = ['key1' => 'value1', 'key2' => 'value2'];

        $this->redisMock->expects($this->once())
            ->method('mset')
            ->with($values)
            ->willReturn(true);

        $expireCalls = [];
        $this->redisMock->expects($this->exactly(2))
            ->method('expire')
            ->willReturnCallback(function (string $key, int $ttl) use (&$expireCalls): bool {
                $expireCalls[] = [$key, $ttl];

                return true;
            });

        $result = $this->cache->setMultiple($values, 3600);

        $this->assertTrue($result);
        $this->assertEquals([['key1', 3600], ['key2', 3600]], $expireCalls);
    }

    public function test_set_multiple_with_date_interval_ttl(): void
    {
        $values = ['key1' => 'value1'];
        $interval = new DateInterval('PT30M'); // 30 minutes

        $this->redisMock->expects($this->once())
            ->method('mset')
            ->with($values)
            ->willReturn(true);

        $this->redisMock->expects($this->once())
            ->method('expire')
            ->with('key1', 1800); // 30 minutes in seconds

        $result = $this->cache->setMultiple($values, $interval);
        $this->assertTrue($result);
    }

    public function test_set_multiple_accepts_numeric_string_keys_that_php_stores_as_ints(): void
    {
        $values = ['123' => 'value1'];

        $this->redisMock->expects($this->once())
            ->method('mset')
            ->with($values)
            ->willReturn(true);

        $result = $this->cache->setMultiple($values);
        $this->assertTrue($result);
    }

    public function test_set_multiple_accepts_values_from_a_generator(): void
    {
        $values = ['key1' => 'value1', 'key2' => 'value2'];

        $this->redisMock->expects($this->once())
            ->method('mset')
            ->with($values)
            ->willReturn(true);

        $result = $this->cache->setMultiple(self::generate($values));
        $this->assertTrue($result);
    }

    public function test_set_multiple_throws_exception_for_invalid_value(): void
    {
        $this->redisMock->expects($this->never())->method('mset');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only scalar, Stringable, or null values are supported for Redis storage. Type: stdClass');

        $values = ['key1' => 'valid', 'key2' => new stdClass()];
        $this->cache->setMultiple($values);
    }

    public function test_delete_multiple_all_keys_exist(): void
    {
        $keys = ['key1', 'key2', 'key3'];

        $this->redisMock->expects($this->once())
            ->method('del')
            ->with($keys)
            ->willReturn(3);

        $result = $this->cache->deleteMultiple($keys);
        $this->assertTrue($result);
    }

    public function test_delete_multiple_some_keys_do_not_exist(): void
    {
        $keys = ['key1', 'key2', 'key3'];

        $this->redisMock->expects($this->once())
            ->method('del')
            ->with($keys)
            ->willReturn(2); // Only 2 out of 3 keys were deleted

        $result = $this->cache->deleteMultiple($keys);
        $this->assertFalse($result);
    }

    public function test_delete_multiple_accepts_keys_from_a_generator(): void
    {
        $this->redisMock->expects($this->once())
            ->method('del')
            ->with(['key1', 'key2'])
            ->willReturn(2);

        $result = $this->cache->deleteMultiple(self::generate(['key1', 'key2']));
        $this->assertTrue($result);
    }

    public function test_has_returns_true_when_key_exists(): void
    {
        $this->redisMock->expects($this->once())
            ->method('exists')
            ->with('existing_key')
            ->willReturn(1);

        $result = $this->cache->has('existing_key');
        $this->assertTrue($result);
    }

    public function test_has_returns_false_when_key_does_not_exist(): void
    {
        $this->redisMock->expects($this->once())
            ->method('exists')
            ->with('nonexistent_key')
            ->willReturn(0);

        $result = $this->cache->has('nonexistent_key');
        $this->assertFalse($result);
    }

    /**
     * @return array<string, array{Closure(RedisCache): mixed}>
     */
    public static function callsWithAnEmptyKey(): array
    {
        return [
            'get' => [static fn (RedisCache $cache): mixed => $cache->get('')],
            'set' => [static fn (RedisCache $cache): bool => $cache->set('', 'value')],
            'delete' => [static fn (RedisCache $cache): bool => $cache->delete('')],
            'has' => [static fn (RedisCache $cache): bool => $cache->has('')],
            'getMultiple' => [static fn (RedisCache $cache): iterable => $cache->getMultiple(['key1', ''])],
            'setMultiple' => [static fn (RedisCache $cache): bool => $cache->setMultiple(['key1' => 'value', '' => 'value'])],
            'deleteMultiple' => [static fn (RedisCache $cache): bool => $cache->deleteMultiple(['key1', ''])],
        ];
    }

    /**
     * @param Closure(RedisCache): mixed $call
     */
    #[AllowMockObjectsWithoutExpectations]
    #[DataProvider('callsWithAnEmptyKey')]
    public function test_an_empty_key_is_rejected_as_psr16_invalid(Closure $call): void
    {
        $this->expectException(SimpleCacheInvalidArgumentException::class);

        $call($this->cache);
    }

    public function test_assert_redis_compatible_value_with_valid_types(): void
    {
        // Test with various scalar types and null
        $validValues = [
            'string',
            123,
            45.67,
            true,
            false,
            null,
        ];

        $this->redisMock->expects($this->exactly(count($validValues)))
            ->method('set')
            ->willReturn(true);

        foreach ($validValues as $value) {
            $this->cache->set('test_key', $value);
        }
    }

    public function test_assert_redis_compatible_value_with_invalid_object(): void
    {
        $this->redisMock->expects($this->never())->method('set');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only scalar, Stringable, or null values are supported for Redis storage. Type: stdClass');

        $this->cache->set('test_key', new stdClass());
    }

    public function test_assert_redis_compatible_value_with_invalid_array(): void
    {
        $this->redisMock->expects($this->never())->method('set');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only scalar, Stringable, or null values are supported for Redis storage. Type: array');

        $this->cache->set('test_key', [1, 2, 3]);
    }

    public function test_assert_redis_compatible_value_with_resource(): void
    {
        $this->redisMock->expects($this->never())->method('set');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only scalar, Stringable, or null values are supported for Redis storage. Type: resource (stream)');

        $resource = fopen('php://memory', 'r');
        if ($resource !== false) {
            $this->cache->set('test_key', $resource);
            fclose($resource);
        }
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

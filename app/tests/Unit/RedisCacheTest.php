<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\RedisCache;
use DateInterval;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Redis;

class RedisCacheTest extends TestCase
{
    protected const array CONFIG = [
        'host' => 'localhost',
        'port' => 6379,
        'password' => 'password',
    ];

    /** @phpstan-var MockObject&Redis */
    protected MockObject&Redis $redisMock;

    protected RedisCache $cache;

    protected function setUp(): void
    {
        $this->redisMock = $this->createMock(Redis::class);

        $this->cache = new RedisCache(
            $this->redisMock,
            self::CONFIG,
        );
    }

    public function test_constructor_connects_to_redis(): void
    {
        $this->redisMock->expects($this->once())
            ->method('connect')
            ->with(self::CONFIG['host'], self::CONFIG['port']);

        $this->redisMock->expects($this->once())
            ->method('auth')
            ->with(self::CONFIG['password']);

        new RedisCache($this->redisMock, self::CONFIG);
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
            ->with('test_key', 'test_value', 3600)
            ->willReturn(true);

        $result = $this->cache->set('test_key', 'test_value', 3600);
        $this->assertTrue($result);
    }

    public function test_set_with_date_interval_ttl(): void
    {
        $interval = new DateInterval('PT1H'); // 1 hour

        $this->redisMock->expects($this->once())
            ->method('set')
            ->with('test_key', 'test_value', 3600)
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only scalar, Stringable, or null values are supported for Redis storage. Type: array');

        $this->cache->set('test_key', ['invalid' => 'array']);
    }

    public function test_set_accepts_stringable_object(): void
    {
        $stringable = new class implements \Stringable
        {
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
            ->method('mGet')
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

    public function test_get_multiple_with_iterator(): void
    {
        $keys = ['key1', 'key2'];
        $values = ['value1', 'value2'];

        $this->redisMock->expects($this->once())
            ->method('mGet')
            ->with($keys)
            ->willReturn($values);

        $result = $this->cache->getMultiple($keys);

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
            ->method('mSet')
            ->with($values)
            ->willReturn(true);

        $result = $this->cache->setMultiple($values);
        $this->assertTrue($result);
    }

    public function test_set_multiple_with_ttl(): void
    {
        $values = ['key1' => 'value1', 'key2' => 'value2'];

        $this->redisMock->expects($this->once())
            ->method('mSet')
            ->with($values)
            ->willReturn(true);

        $expireCalls = [];
        $this->redisMock->expects($this->exactly(2))
            ->method('expire')
            ->willReturnCallback(function ($key, $ttl) use (&$expireCalls) {
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
            ->method('mSet')
            ->with($values)
            ->willReturn(true);

        $this->redisMock->expects($this->once())
            ->method('expire')
            ->with('key1', 1800); // 30 minutes in seconds

        $result = $this->cache->setMultiple($values, $interval);
        $this->assertTrue($result);
    }

    public function test_set_multiple_throws_exception_for_invalid_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only scalar, Stringable, or null values are supported for Redis storage. Type: stdClass');

        $values = ['key1' => 'valid', 'key2' => new \stdClass];
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

    public function test_delete_multiple_with_iterator(): void
    {
        $keys = ['key1', 'key2'];

        $this->redisMock->expects($this->once())
            ->method('del')
            ->with($keys)
            ->willReturn(2);

        $result = $this->cache->deleteMultiple($keys);
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

        foreach ($validValues as $value) {
            // This should not throw an exception
            $this->cache->set('test_key', $value);
        }

        // If we reach here without exceptions, the test passes
        $this->assertTrue(true);
    }

    public function test_assert_redis_compatible_value_with_invalid_object(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only scalar, Stringable, or null values are supported for Redis storage. Type: stdClass');

        $this->cache->set('test_key', new \stdClass);
    }

    public function test_assert_redis_compatible_value_with_invalid_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only scalar, Stringable, or null values are supported for Redis storage. Type: array');

        $this->cache->set('test_key', [1, 2, 3]);
    }

    public function test_assert_redis_compatible_value_with_resource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only scalar, Stringable, or null values are supported for Redis storage. Type: resource');

        $resource = fopen('php://memory', 'r');
        if ($resource) {
            $this->cache->set('test_key', $resource);
            fclose($resource);
        }
    }
}

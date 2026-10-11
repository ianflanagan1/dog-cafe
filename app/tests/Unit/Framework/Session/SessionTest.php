<?php

declare(strict_types=1);

namespace Tests\Unit\Framework\Session;

use App\Framework\Session\Session;
use App\Framework\Session\SessionException;
use Override;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
final class SessionTest extends TestCase
{
    private const array REDIS_CONFIG = ['host' => 'redis', 'port' => 6379, 'password' => 'secret'];

    /**
     * @param array<string, scalar> $options
     * @return array<string, mixed>
     */
    private static function startOptions(array $options): array
    {
        $session = new class (self::REDIS_CONFIG, $options) extends Session {
            /** @return array<string, mixed> */
            public function exposedStartOptions(): array
            {
                return $this->startOptions();
            }
        };

        return $session->exposedStartOptions();
    }

    /**
     * A session stored in files, so the start/close behaviour can be exercised without Redis
     */
    private static function fileSession(): Session
    {
        return new class (self::REDIS_CONFIG, []) extends Session {
            #[Override]
            protected function startOptions(): array
            {
                return [
                    ...parent::startOptions(),
                    'save_handler'  => 'files',
                    'save_path'     => sys_get_temp_dir(),
                    'use_cookies'   => false,
                ];
            }
        };
    }

    /**
     * A file session that records the cookies it sends instead of sending them
     *
     * @return Session&object{sentCookies: list<array{name: string, value: string, options: array<string, scalar>}>}
     */
    private static function cookieRecordingSession(): Session
    {
        return new class (self::REDIS_CONFIG, ['cookie_httponly' => true, 'cookie_samesite' => 'Strict']) extends Session {
            /** @var list<array{name: string, value: string, options: array<string, scalar>}> */
            public array $sentCookies = []; // @phpstan-ignore shipmonk.publicPropertyNotReadonly (a spy: sendCookie() appends to it)

            #[Override]
            protected function startOptions(): array
            {
                return [
                    ...parent::startOptions(),
                    'save_handler'  => 'files',
                    'save_path'     => sys_get_temp_dir(),
                    'use_cookies'   => false,
                    'cookie_path'   => '/app',
                    'cookie_domain' => 'example.com',
                ];
            }

            #[Override]
            protected function sendCookie(string $name, string $value, array $options): void
            {
                $this->sentCookies[] = ['name' => $name, 'value' => $value, 'options' => $options];
            }
        };
    }

    public function test_the_session_starts_with_the_app_config(): void
    {
        $options = self::startOptions(['name' => 'APPSESSID', 'cookie_samesite' => 'Strict']);

        $this->assertSame('APPSESSID', $options['name'] ?? null);
        $this->assertSame('Strict', $options['cookie_samesite'] ?? null);
    }

    public function test_sessions_are_stored_in_the_configured_redis_whatever_the_app_config_says(): void
    {
        $options = self::startOptions(['save_handler' => 'files', 'save_path' => '/tmp']);

        $this->assertSame('redis', $options['save_handler'] ?? null);
        $this->assertSame('tcp://redis:6379?auth=secret&timeout=2.5', $options['save_path'] ?? null);
    }

    public function test_a_redis_session_is_refused_without_locking_because_concurrent_requests_would_overwrite_each_other(): void
    {
        ini_set('redis.session.locking_enabled', '0');
        $session = new Session(self::REDIS_CONFIG, []);

        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('redis.session.locking_enabled');

        $session->get('key');
    }

    public function test_constructing_the_session_does_not_start_it(): void
    {
        self::fileSession();

        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function test_the_session_starts_on_first_use(): void
    {
        $session = self::fileSession();

        $session->get('key');

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function test_closing_ends_the_session_and_can_be_repeated(): void
    {
        $session = self::fileSession();
        $session->put('key', 'value');

        $session->close();
        $session->close();

        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function test_closing_an_unused_session_does_not_start_it(): void
    {
        $session = self::fileSession();

        $session->close();

        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function test_values_can_still_be_read_after_closing(): void
    {
        $session = self::fileSession();
        $session->put('key', 'value');

        $session->close();

        $this->assertSame('value', $session->get('key'));
    }

    public function test_reading_a_session_closed_before_use_does_not_hold_it_open(): void
    {
        $session = self::fileSession();
        $session->close();

        $session->get('key');

        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function test_writing_after_closing_is_refused_because_it_would_not_be_saved(): void
    {
        $session = self::fileSession();
        $session->close();

        $this->expectException(SessionException::class);

        $session->put('key', 'value');
    }

    public function test_destroying_the_session_discards_its_data(): void
    {
        $session = self::fileSession();
        $session->put('key', 'value');

        $session->destroy();

        $this->assertNull($session->get('key'));
        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function test_destroying_the_session_expires_its_cookie_in_the_browser(): void
    {
        $session = self::cookieRecordingSession();
        $session->put('key', 'value');

        $session->destroy();

        $this->assertCount(1, $session->sentCookies);
        $cookie = $session->sentCookies[0];

        $this->assertSame(session_name(), $cookie['name']);
        $this->assertSame('', $cookie['value']);
        $this->assertLessThan(time(), $cookie['options']['expires'] ?? null);
        $this->assertSame('/app', $cookie['options']['path'] ?? null);
        $this->assertSame('example.com', $cookie['options']['domain'] ?? null);
        $this->assertTrue($cookie['options']['httponly'] ?? null);
        $this->assertSame('Strict', $cookie['options']['samesite'] ?? null);
    }

    public function test_writing_after_destroying_is_refused_because_it_would_not_be_saved(): void
    {
        $session = self::fileSession();
        $session->put('key', 'value');
        $session->destroy();

        $this->expectException(SessionException::class);

        $session->put('key', 'value');
    }
}

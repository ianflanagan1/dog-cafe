<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
final class SessionConfigTest extends TestCase
{
    /**
     * @return array<string, scalar>
     */
    private static function config(): array
    {
        return require CONFIG_PATH . '/session.php';
    }

    /**
     * @return array<string, scalar>
     */
    private static function configWithEnv(string $setting): array
    {
        putenv($setting); // @phpstan-ignore disallowed.function (each test runs in its own process, so the change can't leak)

        return self::config();
    }

    public function test_every_setting_is_a_session_directive(): void
    {
        foreach (array_keys(self::config()) as $key) {
            $this->assertNotFalse(ini_get('session.' . $key), "`$key` is not a session.* directive");
        }
    }

    public function test_the_session_is_protected_against_fixation_and_cookie_theft(): void
    {
        $config = self::config();

        $this->assertTrue($config['use_strict_mode'] ?? null);
        $this->assertTrue($config['use_cookies'] ?? null);
        $this->assertTrue($config['use_only_cookies'] ?? null);
        $this->assertFalse($config['use_trans_sid'] ?? null);
        $this->assertTrue($config['cookie_httponly'] ?? null);
        $this->assertSame('Lax', $config['cookie_samesite'] ?? null);
    }

    public function test_the_cookie_is_shared_across_the_public_domain_by_default(): void
    {
        $this->assertSame('dogcafeuk.com', self::configWithEnv('SESSION_DOMAIN')['cookie_domain']);
    }

    public function test_an_empty_domain_restricts_the_cookie_to_the_requested_host(): void
    {
        $this->assertSame('', self::configWithEnv('SESSION_DOMAIN=')['cookie_domain']);
    }

    public function test_the_cookie_is_only_sent_over_https_by_default(): void
    {
        $this->assertTrue(self::configWithEnv('SESSION_SECURE_COOKIE')['cookie_secure']);
    }

    public function test_the_cookie_can_be_allowed_over_http(): void
    {
        $this->assertFalse(self::configWithEnv('SESSION_SECURE_COOKIE=false')['cookie_secure']);
    }
}

<?php

declare(strict_types=1);

namespace App\Session;

use App\Exceptions\SessionException;
use App\Interfaces\SessionInterface;

class Session implements SessionInterface
{
    protected const string FLASH_NAME = 'flash';

    /**
     * Lightweight wrapper around PHP sessions with Redis as the backend.
     *
     * @param array{
     *      host: string,
     *      port: int,
     *      password: string
     * } $redisConfig
     */
    public function __construct(
        protected readonly array $redisConfig
    ) {
    }

    public function start(): void
    {
        if ($this->isActive()) {
            throw new SessionException('Session has already been started');
        }

        if (headers_sent($fileName, $line)) {
            throw new SessionException('Headers have already sent by ' . $fileName . ':' . $line);
        }

        $options = [
            'save_handler'  => 'redis',
            'save_path'     => 'tcp://' . $this->redisConfig['host'] . ':' . $this->redisConfig['port'] . '?auth=' . $this->redisConfig['password'] . '&timeout=2.5'
        ];

        if (!session_start($options)) {
            throw new SessionException('Unable to start the session');
        }
    }

    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->has($key)
            ? $_SESSION[$key]
            : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function close(): void
    {
        session_write_close();
    }

    public function regenerate(): bool
    {
        return session_regenerate_id(true);
    }

    /**
     * @param string $key
     * @param list<string> $messages
     * @return void
     */
    public function flash(string $key, array $messages): void
    {
        $_SESSION[self::FLASH_NAME][$key] = $messages;
    }

    /**
     * @param string $key
     * @return list<string>
     */
    public function getFlash(string $key): array
    {
        $messages = $_SESSION[self::FLASH_NAME][$key] ?? [];

        unset($_SESSION[self::FLASH_NAME][$key]);

        return $messages;
    }
}

<?php

declare(strict_types=1);

namespace App\Framework\Session;

use Override;
use SensitiveParameter;

class Session implements SessionInterface
{
    private bool $started = false;
    private bool $closed = false;

    /**
     * Lightweight wrapper around PHP sessions with Redis as the backend.
     *
     * The session starts on first use and is locked from then until close(), so concurrent requests from the same
     * user queue behind it. Close as soon as no more writes are needed; close() can be called any number of times.
     *
     * @param array{
     *      host: string,
     *      port: int,
     *      password: string
     * } $redisConfig
     * @param array<string, scalar> $options session.* directives without the prefix (config/session.php). Any left out fall back to php.ini
     */
    public function __construct(
        #[SensitiveParameter] protected readonly array $redisConfig,
        protected readonly array $options,
    ) {
    }

    #[Override]
    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    #[Override]
    public function put(string $key, mixed $value): void
    {
        $this->startForWriting();
        $_SESSION[$key] = $value;
    }

    #[Override]
    public function get(string $key, mixed $default = null): mixed
    {
        $this->startForReading();

        return $this->has($key)
            ? $_SESSION[$key]
            : $default;
    }

    #[Override]
    public function has(string $key): bool
    {
        $this->startForReading();

        return array_key_exists($key, $_SESSION);
    }

    #[Override]
    public function close(): void
    {
        if ($this->isActive()) {
            session_write_close();
        }

        $this->closed = true;
    }

    #[Override]
    public function regenerate(): bool
    {
        $this->startForWriting();

        return session_regenerate_id(true);
    }

    #[Override]
    public function destroy(): void
    {
        $this->startForWriting();

        $name = session_name();
        $parameters = session_get_cookie_params();

        $_SESSION = [];
        session_destroy();
        $this->closed = true;

        if ($name !== false) {
            $this->sendCookie($name, '', [
                'expires'   => 1,
                'path'      => $parameters['path'],
                'domain'    => $parameters['domain'],
                'secure'    => $parameters['secure'],
                'httponly'  => $parameters['httponly'],
                'samesite'  => $parameters['samesite'],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function startOptions(): array
    {
        return [
            ...$this->options,
            'save_handler'  => 'redis',
            'save_path'     => 'tcp://' . $this->redisConfig['host'] . ':' . $this->redisConfig['port'] . '?auth=' . $this->redisConfig['password'] . '&timeout=2.5',
        ];
    }

    /**
     * @param array{expires: int, path: string, domain: string, secure: bool, httponly: bool, samesite: 'Lax'|'lax'|'Strict'|'strict'|'None'|'none'} $options
     */
    protected function sendCookie(string $name, string $value, array $options): void
    {
        setcookie($name, $value, $options);
    }

    private function start(bool $readAndClose): void
    {
        if ($this->isActive()) {
            throw new SessionException('Session has already been started');
        }

        if (headers_sent($fileName, $line)) {
            throw new SessionException('Headers have already sent by ' . $fileName . ':' . $line);
        }

        $options = $readAndClose
            ? [...$this->startOptions(), 'read_and_close' => true]
            : $this->startOptions();

        // The lock is what stops concurrent requests overwriting each other's changes. session_start() can't enable it, so php.ini must
        if (($options['save_handler'] ?? null) === 'redis' && filter_var(ini_get('redis.session.locking_enabled'), FILTER_VALIDATE_BOOL) !== true) {
            throw new SessionException('redis.session.locking_enabled must be on in php.ini');
        }

        if (!session_start($options)) {
            throw new SessionException('Unable to start the session');
        }

        $this->started = true;
    }

    /**
     * Once closed, a session that was never started is read without taking the lock
     */
    private function startForReading(): void
    {
        if (!$this->started) {
            $this->start(readAndClose: $this->closed);
        }
    }

    private function startForWriting(): void
    {
        if ($this->closed) {
            throw new SessionException('The session has been closed, so changes would not be saved');
        }

        if (!$this->started) {
            $this->start(readAndClose: false);
        }
    }
}

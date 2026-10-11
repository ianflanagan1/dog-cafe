<?php

declare(strict_types=1);

namespace App\Framework\Session;

interface SessionInterface
{
    public function close(): void;

    public function isActive(): bool;

    public function get(string $key, mixed $default = null): mixed;

    public function regenerate(): bool;

    /**
     * Deletes the session's data and expires its cookie. Like close(), no further writes are accepted
     */
    public function destroy(): void;

    public function put(string $key, mixed $value): void;

    public function forget(string $key): void;

    public function has(string $key): bool;

    /**
     * @param string $key
     * @param list<string> $messages
     * @return void
     */
    public function flash(string $key, array $messages): void;

    /**
     * @param string $key
     * @return list<string>
     */
    public function getFlash(string $key): array;
}

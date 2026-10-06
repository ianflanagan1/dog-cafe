<?php

declare(strict_types=1);

namespace App\Session;

use App\Exceptions\UnauthenticatedException;
use App\Types\StandardTypes;

/**
 * @phpstan-import-type PosInt from StandardTypes
 *
 * @phpstan-type UserArray array{
 *      id: PosInt,
 *      picture: ?non-empty-string,
 * }
 */
class Auth
{
    protected const string SESSION_KEY = 'appUserId';

    protected bool $resolved = false;

    /** @var ?UserArray The current user profile. Null means not logged in */
    protected ?array $user = null;

    public function __construct(
        protected Session $session,
    ) {
    }

    public function isLoggedIn(): bool
    {
        return $this->id() !== null;
    }

    /**
     * @return ?PosInt
     */
    public function id(): ?int
    {
        $res = $this->findValue('id');

        assert(
            $res === null
            || (is_int($res) && $res > 0),
            'ID must be positive integer',
        );

        return $res;
    }

    /**
     * @return ?non-empty-string
     */
    public function picture(): ?string
    {
        $res = $this->findValue('picture');

        assert(
            $res === null
            || (is_string($res) && $res != ''),
            'Picture must be null or non-empty string',
        );

        return $res;
    }

    /**
     * @param bool $redirect
     * @return PosInt
     */
    public function guard(bool $redirect = false): int
    {
        $id = $this->id();

        if ($id === null) {
            throw new UnauthenticatedException($redirect);
        }

        return $id;
    }

    /**
     * @param PosInt $id
     * @param ?non-empty-string $picture
     * @return void
     */
    public function login(int $id, ?string $picture): void
    {
        $userArray = [
            'id' => $id,
            'picture' => $picture,
        ];

        $this->session->put(self::SESSION_KEY, $userArray);

        $this->user = $userArray;
        $this->resolved = true;
    }

    public function logout(): void
    {
        if (! ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();

            $sessionName = session_name();

            if ($sessionName !== false) {
                setcookie(
                    $sessionName,
                    '',
                    time() - 42000,
                    $parameters['path'],
                    $parameters['domain'],
                    $parameters['secure'],
                    $parameters['httponly']
                );
            }
        }

        session_destroy();

        $this->user = null;
    }

    /**
     * @param non-empty-string $key
     * @return mixed
     */
    protected function findValue(string $key): mixed
    {
        if (!$this->resolved) {
            $this->resolveFromSession();
        }

        return $this->user[$key] ?? null;
    }

    /**
     * @return ?UserArray
     */
    protected function resolveFromSession(): ?array
    {
        $res = $this->session->get(self::SESSION_KEY);
        $this->resolved = true;

        assert(
            $res === null
            || (is_array($res) && !empty($res)),
            'Use profile must be null or non-empty array'
        );

        /** @phpstan-var ?UserArray $res */

        $this->user = $res;
        return $res;
    }
}

<?php

declare(strict_types=1);

namespace App\Session;

readonly class FormTokenHandler
{
    protected const int MAX_TOKENS = 10;
    protected const string SESSION_PREFIX = 'formToken-';

    public function __construct(protected SessionListManager $sessionListManager)
    {
    }

    /**
     * @param non-empty-string $key
     * @param ?non-empty-string $token
     * @return bool
     */
    public function isValidToken(string $key, ?string $token): bool
    {
        if ($token === null) {
            return false;
        }

        return $this->sessionListManager->consumeValue(
            self::SESSION_PREFIX . $key,
            $token
        );
    }

    /**
     * @param non-empty-string $key
     * @return non-empty-string
     */
    public function createToken(string $key): string
    {
        $token = self::generateToken();

        $this->sessionListManager->addValue(
            self::SESSION_PREFIX . $key,
            $token,
            self::MAX_TOKENS,
        );

        return $token;
    }

    /**
     * @return non-empty-string
     */
    protected static function generateToken(): string
    {
        return sha1((string) mt_rand());
    }
}

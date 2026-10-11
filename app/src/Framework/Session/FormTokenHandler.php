<?php

declare(strict_types=1);

namespace App\Framework\Session;

use App\Framework\Support\StringGenerator;
use SensitiveParameter;

final readonly class FormTokenHandler
{
    private const int MAX_TOKENS = 10;
    private const string SESSION_PREFIX = 'formToken-';

    public function __construct(private SessionListManager $sessionListManager)
    {
    }

    /**
     * @param non-empty-string $key
     * @param ?non-empty-string $token
     * @return bool
     */
    public function isValidToken(string $key, #[SensitiveParameter] ?string $token): bool
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
    private static function generateToken(): string
    {
        return StringGenerator::hex(32);
    }
}

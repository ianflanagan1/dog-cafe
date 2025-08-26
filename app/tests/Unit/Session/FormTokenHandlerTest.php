<?php

declare(strict_types=1);

namespace Tests\Unit\Session;

use PHPUnit\Framework\TestCase;
use App\Session\FormTokenHandler;
use App\Session\SessionListManager;
use Tests\Traits\ConstantAccessTrait;
use PHPUnit\Framework\MockObject\MockObject;

final class FormTokenHandlerTest extends TestCase
{
    use ConstantAccessTrait;

    /** @var SessionListManager&MockObject */
    protected SessionListManager $sessionListManagerMock;

    protected FormTokenHandler $formTokenHandler;
    protected int $MAX_TOKENS;
    protected string $SESSION_PREFIX;

    protected function setUp(): void
    {
        $this->sessionListManagerMock = $this->createMock(SessionListManager::class);
        $this->formTokenHandler = new FormTokenHandler($this->sessionListManagerMock);

        // Get protected class constants
        /** @var array{0: non-empty-string, 1: int} $result */
        $result = self::getConstantValue(FormTokenHandler::class, ['SESSION_PREFIX', 'MAX_TOKENS']);

        [$this->SESSION_PREFIX, $this->MAX_TOKENS] = $result;
    }

    public function test_isValidToken_returns_false_if_token_is_null(): void
    {
        $result = $this->formTokenHandler->isValidToken('a-key', null);
        $this->assertFalse($result);
    }

    public function test_isValidToken_delegates_to_session_list_manager(): void
    {
        $key = 'a-key';
        $token = 'abc123';

        $this->sessionListManagerMock
            ->expects($this->once())
            ->method('consumeValue')
            ->with($this->SESSION_PREFIX . $key, $token)
            ->willReturn(true);

        $result = $this->formTokenHandler->isValidToken($key, $token);
        $this->assertTrue($result);
    }

    public function test_isValidToken_returns_false_if_SessionListManager_returns_false(): void
    {
        $key = 'a-key';
        $token = 'abc123';

        $this->sessionListManagerMock
            ->expects($this->once())
            ->method('consumeValue')
            ->with($this->SESSION_PREFIX . $key, $token)
            ->willReturn(false);

        $result = $this->formTokenHandler->isValidToken($key, $token);
        $this->assertFalse($result);
    }

    public function test_createToken_generates_and_stores_and_returns_sha1_token(): void
    {
        $capturedToken = null;

        $this->sessionListManagerMock
            ->expects($this->once())
            ->method('addValue')
            ->with(
                $this->SESSION_PREFIX . 'a-key',
                $this->callback(function ($token) use (&$capturedToken) {
                    $capturedToken = $token;
                    return true;
                }),
                $this->MAX_TOKENS,
            );

        $token = $this->formTokenHandler->createToken('a-key');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/i', $token);
        $this->assertSame($capturedToken, $token);
    }

    public function test_createToken_returns_unique_values_for_different_keys(): void
    {
        $token1 = $this->formTokenHandler->createToken('key1');
        $token2 = $this->formTokenHandler->createToken('key2');

        $this->assertNotSame($token1, $token2);
    }

    public function test_createToken_returns_unique_values_for_same_key(): void
    {
        $token1a = $this->formTokenHandler->createToken('key1');
        $token1b = $this->formTokenHandler->createToken('key1');

        $this->assertNotSame($token1a, $token1b);
    }
}

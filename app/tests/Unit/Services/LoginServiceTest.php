<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Framework\Session\Auth;
use App\Framework\Session\Session;
use App\Repositories\AppUserRepository;
use App\Services\AppUserPictureService;
use App\Services\LoginService;
use App\Session\FavouriteSessionStore;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Uri\Rfc3986\Uri;

final class LoginServiceTest extends TestCase
{
    private LoginService $loginService;

    #[Override]
    protected function setUp(): void
    {
        $this->loginService = new LoginService(
            [
                'googleClientId'        => 'google-client',
                'googleClientSecret'    => 'google-secret',
                'discordClientId'       => 'discord-client',
                'discordClientSecret'   => 'discord-secret',
            ],
            $this->createStub(AppUserRepository::class),
            $this->createStub(Session::class),
            $this->createStub(FavouriteSessionStore::class),
            $this->createStub(AppUserPictureService::class),
            $this->createStub(Auth::class),
            'https://example.com',
        );
    }

    /**
     * @return array<non-empty-string, array{non-empty-string, non-empty-string, non-empty-string}>
     */
    public static function providers(): array
    {
        return [
            'Google'    => ['getLoginUrlGoogle', 'accounts.google.com', 'google-client'],
            'Discord'   => ['getLoginUrlDiscord', 'discord.com', 'discord-client'],
        ];
    }

    #[DataProvider('providers')]
    public function test_a_login_url_carries_the_given_state(string $method, string $host, string $clientId): void
    {
        $query = $this->loginUrlQuery($method, $host);

        $this->assertSame('a-state-token', $query['state'] ?? null);
    }

    #[DataProvider('providers')]
    public function test_a_login_url_carries_the_client_id_and_a_redirect_uri_under_the_app_url(string $method, string $host, string $clientId): void
    {
        $query = $this->loginUrlQuery($method, $host);

        $this->assertSame($clientId, $query['client_id'] ?? null);
        $this->assertSame('https://example.com/redirect-login', $query['redirect_uri'] ?? null);
        $this->assertSame('code', $query['response_type'] ?? null);
    }

    /**
     * @return array<mixed>
     */
    private function loginUrlQuery(string $method, string $host): array
    {
        /** @var string */
        $url = $this->loginService->{$method}('/redirect-login', 'a-state-token');

        $uri = new Uri($url);

        $this->assertSame('https', $uri->getScheme());
        $this->assertSame($host, $uri->getHost());

        parse_str($uri->getQuery() ?? '', $query);

        return $query;
    }
}

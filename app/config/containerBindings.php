<?php

declare(strict_types=1);

use App\Config;
use App\Database\Database;
use App\Interfaces\SessionInterface;
use App\RedisCache;
use App\Repositories\AppUserRepository;
use App\Repositories\VenueRepository;
use App\Routing\RouteRegistry;
use App\Services\AppUserPictureService;
use App\Services\LoginService;
use App\MapPoints\MapPointsService;
use App\Session\Auth;
use App\Session\FavouriteSessionStore;
use App\Session\Session;
use Psr\SimpleCache\CacheInterface;

return [
    // assign concrete class to calls for interfaces
    CacheInterface::class => RedisCache::class,
    SessionInterface::class => Session::class,

    // complex class setup
    Config::class => function () {
        return new Config(require CONFIG_PATH . '/app.php');
    },
    Database::class => function (Config $config): Database {
        /**
         * @var array{
         *      driver: non-empty-string,
         *      port: int,
         *      host: non-empty-string,
         *      database: non-empty-string,
         *      user: non-empty-string,
         *      password: non-empty-string,
         *      options?: list<int|bool>
         * } $dbConfig
         */
        $dbConfig = $config->get('dbCore');

        // This is moved outside the Database class to aid unit testing
        $pdoFactory = function(array $config, array $defaultOptions): PDO {
            try {
                $pdo = new PDO(
                    sprintf(
                        '%s:host=%s;port=%d;dbname=%s;',
                        $config['driver'],
                        $config['host'],
                        $config['port'],
                        $config['database']
                    ),
                    $config['user'],
                    $config['password'],
                    $config['options'] ?? $defaultOptions,
                );
            } catch (PDOException $e) {
                throw new RuntimeException('Failed to connect to database: ' . $e->getMessage(), (int) $e->getCode(), $e);
            }

            return $pdo;
        };

        $database = new Database($dbConfig, $pdoFactory);
        return $database;
    },
    Session::class => function (Config $config) {
        /**
         * @var array{
         *      host: non-empty-string,
         *      port: int,
         *      password: non-empty-string,
         * } $redisConfig
         */
        $redisConfig = $config->get('redis');

        $session = new Session($redisConfig);
        $session->start();
        return $session;
    },
    RedisCache::class => function (Redis $redis, Config $config): RedisCache {
        /**
         * @var array{
         *      host: non-empty-string,
         *      port: int,
         *      password: non-empty-string,
         * } $redisConfig
         */
        $redisConfig = $config->get('redis');

        return new RedisCache(
            $redis,
            $redisConfig,
        );
    },
    RouteRegistry::class => function (RedisCache $cache): RouteRegistry {
        $router = new RouteRegistry($cache);
        $router->start(require CONFIG_PATH . '/routerRegisterFromAttributes.php');
        return $router;
    },
    LoginService::class => function (
        Config $config,
        AppUserRepository $appUserRepository,
        Session $session,
        FavouriteSessionStore $favouriteSessionStore,
        AppUserPictureService $appUserPictureService,
        Auth $auth,
    ): LoginService {
        /**
         * @var array{
         *      googleClientId: non-empty-string,
         *      googleClientSecret: non-empty-string,
         *      discordClientId: non-empty-string,
         *      discordClientSecret: non-empty-string,
         * }
         */
        $loginProvidersConfig = $config->get('loginProviders');

        return new LoginService(
            $loginProvidersConfig,
            $appUserRepository,  // TODO: try relying on defaults
            $session,
            $favouriteSessionStore,
            $appUserPictureService,
            $auth,
        );
    },
    MapPointsService::class => function (VenueRepository $venueRepository, Config $config): MapPointsService {
        /**
         * @var array{
         *      app_env: non-empty-string,
         * }
         */
        $appConfig = $config->get('app');

        return new MapPointsService(
            $appConfig,
            require CONFIG_PATH . '/mapZoomLevelProperties.php',
            $venueRepository,
        );
    }
];

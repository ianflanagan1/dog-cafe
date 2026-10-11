<?php

declare(strict_types=1);

use App\Framework\Cache\RedisCache;
use App\Framework\Config\Config;
use App\Framework\Database\Database;
use App\Framework\Routing\RouteRegistry;
use App\Framework\Session\Auth;
use App\Framework\Session\Session;
use App\Framework\Session\SessionInterface;
use App\MapPoints\MapPointsService;
use App\Repositories\AppUserRepository;
use App\Repositories\VenueRepository;
use App\Services\AppUserPictureService;
use App\Services\LoginService;
use App\Session\FavouriteSessionStore;
use Psr\SimpleCache\CacheInterface;

return [
    // assign concrete class to calls for interfaces
    CacheInterface::class => RedisCache::class,
    SessionInterface::class => Session::class,

    // complex class setup
    Config::class => function (): Config {
        /** @var array<string, array<string, ?scalar>> */
        $config = require CONFIG_PATH . '/app.php';

        return new Config($config);
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
         * }
         */
        $dbConfig = $config->get('dbCore');

        // This is moved outside the Database class to aid unit testing
        /**
         * @param array{
         *      driver: string,
         *      port: int,
         *      host: string,
         *      database: string,
         *      user: string,
         *      password: string,
         * } $config
         */
        $pdoFactory = function (#[SensitiveParameter] array $config): PDO {
            try {
                $pdo = PDO::connect(
                    sprintf(
                        '%s:host=%s;port=%d;dbname=%s;',
                        $config['driver'],
                        $config['host'],
                        $config['port'],
                        $config['database']
                    ),
                    $config['user'],
                    $config['password'],
                );
            } catch (PDOException $e) {
                throw new RuntimeException('Failed to connect to database: ' . $e->getMessage(), (int) $e->getCode(), $e);
            }

            return $pdo;
        };

        $database = new Database($dbConfig, $pdoFactory);
        return $database;
    },
    Session::class => function (Config $config): Session {
        /**
         * @var array{
         *      host: non-empty-string,
         *      port: int,
         *      password: non-empty-string,
         * }
         */
        $redisConfig = $config->get('redis');

        /** @var array<string, scalar> */
        $sessionConfig = $config->get('session');

        return new Session($redisConfig, $sessionConfig);
    },
    RedisCache::class => function (Config $config): RedisCache {
        /**
         * @var array{
         *      host: non-empty-string,
         *      port: int,
         *      password: non-empty-string,
         * }
         */
        $redisConfig = $config->get('redis');

        $redisFactory = function () use ($redisConfig): Redis {
            $redis = new Redis();
            $redis->connect($redisConfig['host'], $redisConfig['port']);
            $redis->auth($redisConfig['password']);
            return $redis;
        };

        return new RedisCache($redisFactory);
    },
    RouteRegistry::class => function (RedisCache $cache): RouteRegistry {
        /** @var list<class-string> */
        $controllers = require CONFIG_PATH . '/routerRegisterFromAttributes.php';

        $router = new RouteRegistry($cache);
        $router->start($controllers);
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

        /**
         * @var array{
         *      url: non-empty-string,
         * }
         */
        $appConfig = $config->get('app');

        return new LoginService(
            $loginProvidersConfig,
            $appUserRepository,  // TODO: try relying on defaults
            $session,
            $favouriteSessionStore,
            $appUserPictureService,
            $auth,
            $appConfig['url'],
        );
    },
    MapPointsService::class => function (VenueRepository $venueRepository, Config $config): MapPointsService {
        /**
         * @var array{
         *      app_env: non-empty-string,
         * }
         */
        $appConfig = $config->get('app');

        /**
         * @var array<int<0, 22>, array{
         *      latClusterLimit: float,
         *      lngClusterLimit: float,
         *      latChunkSize: float,
         *      lngChunkSize: float,
         *      chunkDecimals: int<1, max>,
         * }>
         */
        $zoomLevelProperties = require CONFIG_PATH . '/mapZoomLevelProperties.php';

        return new MapPointsService(
            $appConfig,
            $zoomLevelProperties,
            $venueRepository,
        );
    }
];

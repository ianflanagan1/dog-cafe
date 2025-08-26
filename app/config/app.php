<?php

declare(strict_types=1);

return [
    'app' => [
        'app_env'   => trim(getenv('APP_ENV') ?: 'production'),
    ],
    'dbCore' => [
        'driver'    => trim(getenv('DB_CORE_DRIVER') ?: 'pgsql'),
        'port'      => (int) trim(getenv('DB_CORE_PORT') ?: '5432'),
        'host'      => trim(getenv('DB_CORE_HOST') ?: 'dog-cafe-postgres'),
        'database'  => trim(getenv('POSTGRES_DB') ?: 'admin'),
        'user'      => trim(getenv('POSTGRES_USER') ?: 'admin'),
        'password'  => trim(getenv('POSTGRES_PASSWORD') ?: ''),
    ],
    'redis' => [
        'host'      => trim(getenv('REDIS_HOST') ?: 'dog-cafe-redis'),
        'port'      => (int) trim(getenv('REDIS_PORT') ?: '6379'),
        'password'  => trim(getenv('REDIS_PASSWORD') ?: ''),
    ],
    'loginProviders' => [
        'googleClientId'        => trim(getenv('GOOGLE_OAUTH_CLIENT_ID') ?: ''),
        'googleClientSecret'    => trim(getenv('GOOGLE_OAUTH_CLIENT_SECRET') ?: ''),
        'discordClientId'       => trim(getenv('DISCORD_CLIENT_ID') ?: ''),
        'discordClientSecret'   => trim(getenv('DISCORD_CLIENT_SECRET') ?: ''),
    ],
];

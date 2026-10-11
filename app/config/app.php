<?php

declare(strict_types=1);

/** Unset and empty both fall back to the default */
$env = static function (string $name, string $default): string {
    $value = getenv($name);

    return $value === false || $value === ''
        ? $default
        : $value;
};

return [
    'app' => [
        'app_env'   => trim($env('APP_ENV', 'production')),
        // COUPLED:public-domain. default = caddy:site address
        'url'       => rtrim(trim($env('APP_URL', 'https://dogcafeuk.com')), '/'),
    ],
    'dbCore' => [
        'driver'    => trim($env('DB_CORE_DRIVER', 'pgsql')),
        'port'      => (int) trim($env('DB_CORE_PORT', '5432')),
        'host'      => trim($env('DB_CORE_HOST', 'dog-cafe-postgres')),
        'database'  => trim($env('POSTGRES_DB', 'admin')),
        'user'      => trim($env('POSTGRES_USER', 'admin')),
        'password'  => trim($env('POSTGRES_PASSWORD', '')),
    ],
    'redis' => [
        'host'      => trim($env('REDIS_HOST', 'dog-cafe-cache')),
        'port'      => (int) trim($env('REDIS_PORT', '6379')),
        'password'  => trim($env('REDIS_PASSWORD', '')),
    ],
    'session' => require __DIR__ . '/session.php',
    'loginProviders' => [
        'googleClientId'        => trim($env('GOOGLE_OAUTH_CLIENT_ID', '')),
        'googleClientSecret'    => trim($env('GOOGLE_OAUTH_CLIENT_SECRET', '')),
        'discordClientId'       => trim($env('DISCORD_CLIENT_ID', '')),
        'discordClientSecret'   => trim($env('DISCORD_CLIENT_SECRET', '')),
    ],
];

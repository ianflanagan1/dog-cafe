<?php

declare(strict_types=1);

namespace App\Environment;

class Server
{
    public static function getSchemeAndHost(): string
    {
        $scheme = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']))
            ? $_SERVER['HTTP_X_FORWARDED_PROTO']
            : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $port = $_SERVER['HTTP_X_FORWARDED_PORT'] ?? ($_SERVER['SERVER_PORT'] ?? null);

        if (
            $port &&
            !($port == 80 || $port == 443) &&
            strpos($host, ':') === false
        ) {
            $host .= ':' . $port;
        }

        return $scheme . '://' . $host;
    }
}

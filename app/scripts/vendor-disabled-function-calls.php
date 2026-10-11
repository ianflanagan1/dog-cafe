<?php

declare(strict_types=1);

/**
 * Production vendor calls to functions that production disables, reviewed as unreachable from this app.
 *
 * They stay disabled: if one of these paths ever runs, it throws "Call to undefined function" rather than executing.
 * Checked by check-vendor-disabled-functions.php, which fails on a new call and on an entry that no longer matches.
 *
 * @return array<string, array<string, non-empty-string>> Package => function => why it can't run here
 */
return [
    'google/auth' => [
        'exec'                  => 'CredentialsLoader: runs a gcloud mTLS certificate command, only when a client certificate config file exists; none is configured',
    ],
    'guzzlehttp/psr7' => [
        'move_uploaded_file'    => 'UploadedFile::moveTo(): the app never uses PSR-7 uploads (php.ini file_uploads = Off)',
    ],
    'monolog/monolog' => [
        'fsockopen'             => 'SocketHandler: the app never configures Monolog handlers',
        'mail'                  => 'NativeMailerHandler: the app never configures Monolog handlers',
        'openlog'               => 'SyslogHandler: the app never configures Monolog handlers',
        'pfsockopen'            => 'SocketHandler: the app never configures Monolog handlers',
        'posix_kill'            => 'SignalHandler: the app never registers Monolog signal handlers',
        'proc_close'            => 'ProcessHandler: the app never configures Monolog handlers',
        'proc_open'             => 'ProcessHandler: the app never configures Monolog handlers',
        'shell_exec'            => 'GitProcessor, MercurialProcessor: the app never configures Monolog processors',
        'socket_connect'        => 'CubeHandler: the app never configures Monolog handlers',
        'socket_create'         => 'CubeHandler, SyslogUdp\UdpSocket: the app never configures Monolog handlers',
        'socket_send'           => 'CubeHandler: the app never configures Monolog handlers',
    ],
    'phpseclib/phpseclib' => [
        'fsockopen'             => 'SSH2, SSH Agent, X509 URL fetching: google/auth only uses phpseclib for RSA keys',
        'ini_set'               => 'Crypt\Random: fallback for when random_bytes() is unavailable, which never happens on PHP 8',
        'php_uname'             => 'Crypt\Common\SymmetricKey: guarded by function_exists(\'php_uname\'), which is false when disabled',
    ],
];

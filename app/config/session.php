<?php

declare(strict_types=1);

/**
 * Every session.* directive the app relies on, passed to session_start() by App\Framework\Session\Session.
 * php.ini sets none of them, so a key left out here gets PHP's built-in default
 */

$domain = getenv('SESSION_DOMAIN');    // Unset -> production default; set but empty -> host-only cookie
$secureCookie = getenv('SESSION_SECURE_COOKIE');  // Unset or empty -> secure

return [
    'name'              => 'DOGCAFEID',
    'use_strict_mode'   => true,        // Prevents PHP accepting uninitialised session IDs. Helps prevent session fixation attacks
    'use_cookies'       => true,
    'use_only_cookies'  => true,
    'use_trans_sid'     => false,       // Appending session IDs to URLs can cause session fixation, leaking session IDs via referrer headers, logs etc.
    // COUPLED:public-domain. default = caddy:site address; != .env.example SESSION_DOMAIN (dev serves localhost; deliberate)
    'cookie_domain'     => $domain !== false
        ? trim($domain)
        : 'dogcafeuk.com',
    'cookie_secure'     => $secureCookie === false || $secureCookie === ''
        ? true
        : filter_var($secureCookie, FILTER_VALIDATE_BOOL),
    'cookie_httponly'   => true,        // Prevents client-side scripts from accessing session cookie
    'cookie_samesite'   => 'Lax',       // Sent on top-level cross-site GET navigations (OAuth redirects back from Google/Discord), not on cross-site POST/fetch. Helps mitigate CSRF
    'cookie_lifetime'   => 31536000,    // Seconds. 31536000 = 1 year; 0 -> expire when browser closed
    'gc_maxlifetime'    => 31536000,    // Seconds. 31536000 = 1 year; Max time session data is retained
];

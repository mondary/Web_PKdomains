<?php
/**
 * Configure and start session with proper cookie settings for iframe/cross-origin support.
 * Must be called before any output.
 */
function secure_session_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
              || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    $cookie_params = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => $secure ? 'None' : 'Lax'
    ];

    session_set_cookie_params($cookie_params);
    session_start();
}

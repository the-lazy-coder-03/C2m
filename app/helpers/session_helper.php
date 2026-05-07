<?php

require_once __DIR__ . '/../../config/config.php';

if (!function_exists('startUserSession')) {
    /**
     * Start a short PHP session for flash messages. Login state is stored in JWT.
     */
    function startUserSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}

if (!function_exists('endUserSession')) {
    /**
     * Destroy the current public user session and remove the session cookie.
     */
    function endUserSession(): void
    {
        startUserSession();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}

if (!function_exists('getCsrfToken')) {
    /**
     * Create or return a CSRF token for forms that change data.
     */
    function getCsrfToken(string $key): string
    {
        startUserSession();

        if (empty($_SESSION['csrf_tokens'][$key])) {
            $_SESSION['csrf_tokens'][$key] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_tokens'][$key];
    }
}

if (!function_exists('isValidCsrfToken')) {
    /**
     * Check that a submitted CSRF token matches the token stored in the session.
     */
    function isValidCsrfToken(string $key, string $token): bool
    {
        startUserSession();
        $storedToken = $_SESSION['csrf_tokens'][$key] ?? '';

        return $storedToken !== '' && hash_equals($storedToken, $token);
    }
}

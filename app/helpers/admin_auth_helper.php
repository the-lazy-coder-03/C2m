<?php

require_once __DIR__ . '/jwt_helper.php';

if (!function_exists('admin_auth_cookie_name')) {
    function admin_auth_cookie_name(): string
    {
        return (string) config_get('ADMIN_AUTH_COOKIE_NAME', 'localmarket_admin_auth');
    }
}

if (!function_exists('admin_auth_cookie_options')) {
    function admin_auth_cookie_options(int $expiresAt): array
    {
        return [
            'expires' => $expiresAt,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}

if (!function_exists('issue_admin_auth')) {
    function issue_admin_auth(string $adminName): void
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + jwt_lifetime();
        $token = create_jwt([
            'sub' => 1,
            'name' => $adminName,
            'role' => 'admin',
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ]);

        setcookie(admin_auth_cookie_name(), $token, admin_auth_cookie_options($expiresAt));
        $_COOKIE[admin_auth_cookie_name()] = $token;
    }
}

if (!function_exists('clear_admin_auth')) {
    function clear_admin_auth(): void
    {
        setcookie(admin_auth_cookie_name(), '', admin_auth_cookie_options(time() - 3600));
        unset($_COOKIE[admin_auth_cookie_name()]);
    }
}

if (!function_exists('current_admin_from_cookie')) {
    function current_admin_from_cookie(): ?array
    {
        $token = $_COOKIE[admin_auth_cookie_name()] ?? '';

        if ($token === '') {
            return null;
        }

        $payload = decode_jwt($token);

        if ($payload === null || ($payload['role'] ?? '') !== 'admin') {
            if (!headers_sent()) {
                clear_admin_auth();
            } else {
                unset($_COOKIE[admin_auth_cookie_name()]);
            }

            return null;
        }

        return [
            'admin_id' => (int) ($payload['sub'] ?? 1),
            'admin_name' => (string) ($payload['name'] ?? 'Admin'),
        ];
    }
}

if (!function_exists('current_admin_user')) {
    function current_admin_user(): ?array
    {
        $admin = current_admin_from_cookie();

        if ($admin !== null) {
            return $admin;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['admin_id'])) {
            return null;
        }

        return [
            'admin_id' => (int) $_SESSION['admin_id'],
            'admin_name' => (string) ($_SESSION['admin_name'] ?? 'Admin'),
        ];
    }
}

if (!function_exists('require_admin_user')) {
    function require_admin_user(string $loginPath = '/admin/login'): array
    {
        $admin = current_admin_user();

        if ($admin === null) {
            header('Location: ' . $loginPath);
            exit;
        }

        return $admin;
    }
}

<?php

require_once __DIR__ . '/../../config/config.php';

if (!function_exists('jwt_base64url_encode')) {
    function jwt_base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('jwt_base64url_decode')) {
    function jwt_base64url_decode(string $data): string|false
    {
        $remainder = strlen($data) % 4;

        if ($remainder > 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}

if (!function_exists('jwt_secret')) {
    function jwt_secret(): string
    {
        $secret = (string) config_get('JWT_SECRET', '');

        if ($secret === '') {
            throw new RuntimeException('JWT_SECRET is missing from config/.env.');
        }

        return $secret;
    }
}

if (!function_exists('jwt_cookie_name')) {
    function jwt_cookie_name(): string
    {
        return (string) config_get('JWT_COOKIE_NAME', 'localmarket_auth');
    }
}

if (!function_exists('jwt_lifetime')) {
    function jwt_lifetime(): int
    {
        return (int) config_get('JWT_LIFETIME', '604800');
    }
}

if (!function_exists('create_jwt')) {
    function create_jwt(array $claims): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $encodedHeader = jwt_base64url_encode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = jwt_base64url_encode(json_encode($claims, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, jwt_secret(), true);

        return $encodedHeader . '.' . $encodedPayload . '.' . jwt_base64url_encode($signature);
    }
}

if (!function_exists('decode_jwt')) {
    function decode_jwt(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $expectedSignature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, jwt_secret(), true);
        $actualSignature = jwt_base64url_decode($encodedSignature);

        if ($actualSignature === false || !hash_equals($expectedSignature, $actualSignature)) {
            return null;
        }

        $payloadJson = jwt_base64url_decode($encodedPayload);

        if ($payloadJson === false) {
            return null;
        }

        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            return null;
        }

        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }
}

if (!function_exists('issue_user_jwt')) {
    function issue_user_jwt(int $userId, string $name, string $email): void
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + jwt_lifetime();
        $token = create_jwt([
            'sub' => $userId,
            'name' => $name,
            'email' => $email,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ]);

        setcookie(jwt_cookie_name(), $token, [
            'expires' => $expiresAt,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $_COOKIE[jwt_cookie_name()] = $token;
    }
}

if (!function_exists('clear_user_jwt')) {
    function clear_user_jwt(): void
    {
        setcookie(jwt_cookie_name(), '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        unset($_COOKIE[jwt_cookie_name()]);
    }
}

if (!function_exists('current_user_from_jwt')) {
    function current_user_from_jwt(): ?array
    {
        $token = $_COOKIE[jwt_cookie_name()] ?? '';

        if ($token === '') {
            return null;
        }

        $payload = decode_jwt($token);

        if ($payload === null) {
            if (!headers_sent()) {
                clear_user_jwt();
            } else {
                unset($_COOKIE[jwt_cookie_name()]);
            }

            return null;
        }

        return [
            'user_id' => (int) $payload['sub'],
            'name' => (string) ($payload['name'] ?? ''),
            'email' => (string) ($payload['email'] ?? ''),
        ];
    }
}

if (!function_exists('require_user_from_jwt')) {
    function require_user_from_jwt(string $loginPath = '/login'): array
    {
        $user = current_user_from_jwt();

        if ($user === null) {
            header('Location: ' . $loginPath);
            exit;
        }

        return $user;
    }
}

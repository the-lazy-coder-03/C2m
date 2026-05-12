<?php

require_once __DIR__ . '/config.php';

if (!function_exists('getDbConnection')) {
    /**
     * Create a PostgreSQL PDO connection from config/.env.
     */
    function getDbConnection(): PDO
    {
        $databaseUrl = config_get('DATABASE_URL', '');
        $urlConfig = $databaseUrl !== '' ? parseDatabaseUrl($databaseUrl) : [];

        $host = config_get('DB_HOST', $urlConfig['host'] ?? 'localhost');
        $port = config_get('DB_PORT', $urlConfig['port'] ?? '5432');
        $dbname = config_get('DB_NAME', $urlConfig['dbname'] ?? 'c2m');
        $user = config_get('DB_USER', $urlConfig['user'] ?? 'postgres');
        $password = config_get('DB_PASS', $urlConfig['password'] ?? '');
        $isNeonHost = isNeonDatabaseHost($host);
        $sslmode = config_get('DB_SSLMODE', $urlConfig['sslmode'] ?? ($isNeonHost ? 'require' : 'prefer'));
        $endpoint = config_get('DB_ENDPOINT', $urlConfig['endpoint'] ?? '');

        if ($isNeonHost && in_array($sslmode, ['', 'allow', 'prefer'], true)) {
            $sslmode = 'require';
        }

        if ($endpoint === '' && $isNeonHost) {
            $endpoint = getNeonEndpointFromHost($host);
        }

        $dbnameOption = $dbname;

        if ($endpoint !== '') {
            $dbnameOption .= ' options=endpoint=' . $endpoint;
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            $host,
            $port,
            $dbnameOption,
            $sslmode
        );

        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $pdo->exec('SET search_path TO public');

        return $pdo;
    }
}

if (!function_exists('parseDatabaseUrl')) {
    /**
     * Convert a postgres:// URL from hosts like Neon into the DB_* shape.
     */
    function parseDatabaseUrl(string $databaseUrl): array
    {
        $parts = parse_url($databaseUrl);

        if ($parts === false) {
            return [];
        }

        $query = [];
        parse_str($parts['query'] ?? '', $query);

        return [
            'host' => $parts['host'] ?? null,
            'port' => isset($parts['port']) ? (string) $parts['port'] : '5432',
            'dbname' => isset($parts['path']) ? ltrim($parts['path'], '/') : null,
            'user' => isset($parts['user']) ? rawurldecode($parts['user']) : null,
            'password' => isset($parts['pass']) ? rawurldecode($parts['pass']) : null,
            'sslmode' => isset($query['sslmode']) ? (string) $query['sslmode'] : null,
            'endpoint' => getEndpointFromConnectionOptions($query['options'] ?? null),
        ];
    }
}

if (!function_exists('isNeonDatabaseHost')) {
    function isNeonDatabaseHost(?string $host): bool
    {
        return is_string($host) && strpos($host, '.neon.tech') !== false;
    }
}

if (!function_exists('getNeonEndpointFromHost')) {
    function getNeonEndpointFromHost(string $host): string
    {
        return explode('.', $host, 2)[0] ?? '';
    }
}

if (!function_exists('getEndpointFromConnectionOptions')) {
    function getEndpointFromConnectionOptions($options): ?string
    {
        if (!is_string($options) || $options === '') {
            return null;
        }

        if (preg_match('/(?:^|\s)endpoint=([^\s]+)/', $options, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

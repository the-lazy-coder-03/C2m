<?php

require_once __DIR__ . '/config.php';

if (!function_exists('getDbConnection')) {
    /**
     * Create a PostgreSQL PDO connection from config/.env.
     */
    function getDbConnection(): PDO
    {
        $host = config_get('DB_HOST', 'localhost');
        $port = config_get('DB_PORT', '5432');
        $dbname = config_get('DB_NAME', 'c2m');
        $user = config_get('DB_USER', 'postgres');
        $password = config_get('DB_PASS', '');
        $sslmode = config_get('DB_SSLMODE', 'prefer');

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            $host,
            $port,
            $dbname,
            $sslmode
        );

        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}

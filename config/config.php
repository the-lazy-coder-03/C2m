<?php

if (!function_exists('loadEnvFile')) {
    /**
     * Load key-value pairs from a simple .env file.
     */
    function loadEnvFile(string $envPath): array
    {
        $values = [];

        if (!is_readable($envPath)) {
            return $values;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === '' || str_starts_with($trimmedLine, '#')) {
                continue;
            }

            $separatorPosition = strpos($trimmedLine, '=');

            if ($separatorPosition === false) {
                continue;
            }

            $key = trim(substr($trimmedLine, 0, $separatorPosition));
            $value = trim(substr($trimmedLine, $separatorPosition + 1));
            $value = trim($value, "\"'");

            $values[$key] = $value;
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }

        return $values;
    }
}

if (!function_exists('config_get')) {
    /**
     * Read a config value from the loaded environment with a fallback.
     */
    function config_get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        return $value !== false && $value !== null ? $value : $default;
    }
}

loadEnvFile(__DIR__ . '/.env');

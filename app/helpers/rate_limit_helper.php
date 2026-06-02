<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

if (!function_exists('applyApiRateLimit')) {
    function applyApiRateLimit(string $method, string $path): void
    {
        if (!rateLimitConfigBool('RATE_LIMIT_ENABLED', true)) {
            return;
        }

        $maxRequests = rateLimitConfigInt('RATE_LIMIT_MAX_REQUESTS', 120);
        $windowSeconds = rateLimitConfigInt('RATE_LIMIT_WINDOW_SECONDS', 60);
        $clientIdentifier = rateLimitClientIdentifier();
        $rateLimitKey = implode('|', ['api', $clientIdentifier, strtoupper($method), $path]);

        $result = rateLimitConsume($rateLimitKey, $maxRequests, $windowSeconds);

        header('RateLimit-Limit: ' . $result['limit']);
        header('RateLimit-Remaining: ' . $result['remaining']);
        header('RateLimit-Reset: ' . $result['retry_after']);
        header('X-RateLimit-Limit: ' . $result['limit']);
        header('X-RateLimit-Remaining: ' . $result['remaining']);
        header('X-RateLimit-Reset: ' . $result['reset_at']);

        if ($result['allowed']) {
            return;
        }

        header('Retry-After: ' . $result['retry_after']);
        http_response_code(429);

        if (rateLimitRequestWantsJson()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'error' => 'rate_limit_exceeded',
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $result['retry_after'],
            ]);
        } else {
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Too many requests. Please try again later.';
        }

        exit;
    }
}

if (!function_exists('rateLimitConsume')) {
    function rateLimitConsume(string $key, int $maxRequests, int $windowSeconds): array
    {
        $maxRequests = max(1, $maxRequests);
        $windowSeconds = max(1, $windowSeconds);
        $now = time();
        $defaultResult = [
            'allowed' => true,
            'limit' => $maxRequests,
            'remaining' => $maxRequests - 1,
            'reset_at' => $now + $windowSeconds,
            'retry_after' => $windowSeconds,
        ];

        $storageDirectory = rateLimitStorageDirectory();

        if ($storageDirectory === null) {
            return $defaultResult;
        }

        $bucketPath = $storageDirectory . '/' . hash('sha256', $key) . '.json';
        $bucketFile = @fopen($bucketPath, 'c+');

        if ($bucketFile === false) {
            return $defaultResult;
        }

        try {
            if (!flock($bucketFile, LOCK_EX)) {
                return $defaultResult;
            }

            $contents = stream_get_contents($bucketFile);
            $decodedBucket = is_string($contents) && $contents !== '' ? json_decode($contents, true) : [];
            $bucket = is_array($decodedBucket) ? $decodedBucket : [];
            $resetAt = isset($bucket['reset_at']) ? (int) $bucket['reset_at'] : 0;
            $count = isset($bucket['count']) ? (int) $bucket['count'] : 0;

            if ($resetAt <= $now) {
                $resetAt = $now + $windowSeconds;
                $count = 0;
            }

            $allowed = $count < $maxRequests;

            if ($allowed) {
                $count++;
            }

            $remaining = max(0, $maxRequests - $count);
            $retryAfter = max(1, $resetAt - $now);

            rewind($bucketFile);
            ftruncate($bucketFile, 0);
            fwrite($bucketFile, json_encode([
                'count' => $count,
                'reset_at' => $resetAt,
            ]));
            fflush($bucketFile);

            return [
                'allowed' => $allowed,
                'limit' => $maxRequests,
                'remaining' => $remaining,
                'reset_at' => $resetAt,
                'retry_after' => $retryAfter,
            ];
        } finally {
            flock($bucketFile, LOCK_UN);
            fclose($bucketFile);
            rateLimitMaybePrune($storageDirectory, $now);
        }
    }
}

if (!function_exists('rateLimitStorageDirectory')) {
    function rateLimitStorageDirectory(): ?string
    {
        $configuredDirectory = config_get('RATE_LIMIT_STORAGE_DIR', '');
        $storageDirectory = $configuredDirectory !== ''
            ? $configuredDirectory
            : sys_get_temp_dir() . '/c2m-rate-limit';

        if (!is_dir($storageDirectory) && !@mkdir($storageDirectory, 0775, true) && !is_dir($storageDirectory)) {
            return null;
        }

        return is_writable($storageDirectory) ? $storageDirectory : null;
    }
}

if (!function_exists('rateLimitMaybePrune')) {
    function rateLimitMaybePrune(string $storageDirectory, int $now): void
    {
        if (mt_rand(1, 100) !== 1) {
            return;
        }

        $cutoff = $now - rateLimitConfigInt('RATE_LIMIT_WINDOW_SECONDS', 60) * 2;

        foreach (glob($storageDirectory . '/*.json') ?: [] as $bucketPath) {
            if (is_file($bucketPath) && (filemtime($bucketPath) ?: $now) < $cutoff) {
                @unlink($bucketPath);
            }
        }
    }
}

if (!function_exists('rateLimitClientIdentifier')) {
    function rateLimitClientIdentifier(): string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CF_CONNECTING_IP', 'REMOTE_ADDR'] as $serverKey) {
            $serverValue = $_SERVER[$serverKey] ?? '';

            foreach (explode(',', (string) $serverValue) as $candidate) {
                $candidate = trim($candidate);

                if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
                    return $candidate;
                }
            }
        }

        return 'unknown';
    }
}

if (!function_exists('rateLimitRequestWantsJson')) {
    function rateLimitRequestWantsJson(): bool
    {
        $acceptHeader = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

        return str_contains($acceptHeader, 'application/json') || $requestedWith === 'xmlhttprequest';
    }
}

if (!function_exists('rateLimitConfigInt')) {
    function rateLimitConfigInt(string $key, int $default): int
    {
        $value = config_get($key, (string) $default);

        return is_numeric($value) ? max(1, (int) $value) : $default;
    }
}

if (!function_exists('rateLimitConfigBool')) {
    function rateLimitConfigBool(string $key, bool $default): bool
    {
        $value = config_get($key, $default ? 'true' : 'false');

        if (!is_string($value)) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}

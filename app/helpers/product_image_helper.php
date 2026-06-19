<?php

require_once __DIR__ . '/../../config/config.php';

if (!function_exists('project_public_path')) {
    /**
     * Resolve a path inside the project's public folder without hard-coding a machine path.
     */
    function project_public_path(string $relativePath = ''): string
    {
        $publicRoot = dirname(__DIR__, 2) . '/public';
        $cleanPath = trim(str_replace('\\', '/', $relativePath), '/');

        return $cleanPath === '' ? $publicRoot : $publicRoot . '/' . $cleanPath;
    }
}

if (!function_exists('public_asset_url')) {
    /**
     * Convert a public-folder relative path into a browser URL.
     */
    function public_asset_url(?string $relativePath): string
    {
        $cleanPath = trim((string) $relativePath, '/');

        if ($cleanPath === '') {
            $cleanPath = 'assets/images/product-placeholder.svg';
        }

        if (is_product_image_s3_enabled() && str_starts_with($cleanPath, get_product_upload_relative_dir() . '/')) {
            return product_image_s3_browser_url($cleanPath);
        }

        $base = trim((string) config_get('PUBLIC_WEB_BASE', ''), '/');

        return '/' . ($base === '' ? $cleanPath : $base . '/' . $cleanPath);
    }
}

if (!function_exists('get_product_upload_relative_dir')) {
    function get_product_upload_relative_dir(): string
    {
        return trim((string) config_get('PRODUCT_UPLOAD_RELATIVE_DIR', 'uploads/products'), '/');
    }
}

if (!function_exists('get_product_image_max_count')) {
    function get_product_image_max_count(): int
    {
        return max(1, (int) config_get('PRODUCT_IMAGE_MAX_COUNT', '8'));
    }
}

if (!function_exists('get_product_image_s3_url')) {
    function get_product_image_s3_url(): string
    {
        $url = rtrim(trim((string) config_get('PRODUCT_IMAGE_S3_URL', '')), '/');

        if (str_starts_with($url, 's3://')) {
            $parts = parse_url($url);
            $bucket = $parts['host'] ?? '';
            $path = trim($parts['path'] ?? '', '/');
            $region = trim((string) config_get('PRODUCT_IMAGE_S3_REGION', '')) ?: 'us-east-1';

            if ($bucket !== '') {
                $url = 'https://' . $bucket . '.s3.' . $region . '.amazonaws.com' . ($path === '' ? '' : '/' . $path);
            }
        }

        return $url;
    }
}

if (!function_exists('is_product_image_s3_enabled')) {
    function is_product_image_s3_enabled(): bool
    {
        return get_product_image_s3_url() !== '';
    }
}

if (!function_exists('product_image_s3_object_url')) {
    function product_image_s3_object_url(string $relativePath): string
    {
        $baseUrl = get_product_image_s3_url();

        if ($baseUrl === '') {
            return $relativePath;
        }

        $cleanPath = trim(str_replace('\\', '/', $relativePath), '/');
        $uploadRelativeDir = get_product_upload_relative_dir();
        $basePath = trim((string) parse_url($baseUrl, PHP_URL_PATH), '/');

        if ($basePath === $uploadRelativeDir || str_ends_with($basePath, '/' . $uploadRelativeDir)) {
            $cleanPath = preg_replace('#^' . preg_quote($uploadRelativeDir, '#') . '/#', '', $cleanPath);
        }

        return rtrim($baseUrl, '/') . '/' . str_replace('%2F', '/', rawurlencode($cleanPath));
    }
}

if (!function_exists('get_product_image_s3_url_expires')) {
    function get_product_image_s3_url_expires(): int
    {
        return max(60, min(604800, (int) config_get('PRODUCT_IMAGE_S3_URL_EXPIRES', '3600')));
    }
}

if (!function_exists('has_product_image_s3_credentials')) {
    function has_product_image_s3_credentials(): bool
    {
        return trim((string) config_get('AWS_ACCESS_KEY_ID', '')) !== ''
            && (string) config_get('AWS_SECRET_ACCESS_KEY', '') !== '';
    }
}

if (!function_exists('product_image_s3_browser_url')) {
    function product_image_s3_browser_url(string $relativePath): string
    {
        $objectUrl = product_image_s3_object_url($relativePath);

        if (!has_product_image_s3_credentials()) {
            return $objectUrl;
        }

        return presign_product_image_s3_url($objectUrl, get_product_image_s3_url_expires());
    }
}

if (!function_exists('upload_size_to_bytes')) {
    function upload_size_to_bytes(string $size): int
    {
        $size = trim($size);

        if ($size === '') {
            return 0;
        }

        $unit = strtolower($size[strlen($size) - 1]);
        $bytes = (float) $size;

        return match ($unit) {
            'g' => (int) ($bytes * 1024 * 1024 * 1024),
            'm' => (int) ($bytes * 1024 * 1024),
            'k' => (int) ($bytes * 1024),
            default => (int) $bytes,
        };
    }
}

if (!function_exists('format_upload_size')) {
    function format_upload_size(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / 1024 / 1024, 1), '0'), '.') . 'MB';
        }

        if ($bytes >= 1024) {
            return rtrim(rtrim(number_format($bytes / 1024, 1), '0'), '.') . 'KB';
        }

        return $bytes . ' bytes';
    }
}

if (!function_exists('get_configured_product_image_max_bytes')) {
    function get_configured_product_image_max_bytes(): int
    {
        return max(1, (int) config_get('PRODUCT_IMAGE_MAX_BYTES', '10485760'));
    }
}

if (!function_exists('get_php_upload_max_bytes')) {
    function get_php_upload_max_bytes(): int
    {
        return upload_size_to_bytes((string) ini_get('upload_max_filesize'));
    }
}

if (!function_exists('get_effective_product_image_max_bytes')) {
    function get_effective_product_image_max_bytes(): int
    {
        $configuredMax = get_configured_product_image_max_bytes();
        $phpMax = get_php_upload_max_bytes();

        return $phpMax > 0 ? min($configuredMax, $phpMax) : $configuredMax;
    }
}

if (!function_exists('normalize_uploaded_files')) {
    /**
     * PHP groups multi-file upload fields by property; this reshapes them into one array per file.
     */
    function normalize_uploaded_files(array $files): array
    {
        if (!isset($files['name']) || !is_array($files['name'])) {
            return isset($files['name']) ? [$files] : [];
        }

        $normalized = [];
        $fileCount = count($files['name']);

        for ($index = 0; $index < $fileCount; $index++) {
            $normalized[] = [
                'name' => $files['name'][$index] ?? '',
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
        }

        return $normalized;
    }
}

if (!function_exists('upload_error_message')) {
    function upload_error_message(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => sprintf(
                'One selected image is larger than the PHP server upload limit (%s per file). Restart the local server with a higher upload_max_filesize, or choose smaller images.',
                format_upload_size(get_php_upload_max_bytes())
            ),
            UPLOAD_ERR_PARTIAL => 'The image only uploaded partially. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server is missing a temporary upload folder.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded image.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
            default => 'The image upload failed.',
        };
    }
}

if (!function_exists('hash_hmac_binary')) {
    function hash_hmac_binary(string $algorithm, string $data, string $key): string
    {
        return hash_hmac($algorithm, $data, $key, true);
    }
}

if (!function_exists('get_product_image_s3_region')) {
    function get_product_image_s3_region(string $host): string
    {
        $configuredRegion = trim((string) config_get('PRODUCT_IMAGE_S3_REGION', ''));

        if ($configuredRegion !== '') {
            return $configuredRegion;
        }

        if (preg_match('/\.s3[.-]([a-z0-9-]+)\.amazonaws\.com$/i', $host, $matches)) {
            return $matches[1];
        }

        return 'us-east-1';
    }
}

if (!function_exists('canonical_s3_uri')) {
    function canonical_s3_uri(string $path): string
    {
        $segments = array_map(
            fn (string $segment): string => rawurlencode(rawurldecode($segment)),
            explode('/', $path === '' ? '/' : $path)
        );

        return implode('/', $segments);
    }
}

if (!function_exists('aws_query_encode')) {
    function aws_query_encode(string $value): string
    {
        return str_replace('%7E', '~', rawurlencode($value));
    }
}

if (!function_exists('canonical_s3_query_string')) {
    function canonical_s3_query_string(array $queryParams): string
    {
        ksort($queryParams);

        $pairs = [];
        foreach ($queryParams as $key => $value) {
            $pairs[] = aws_query_encode((string) $key) . '=' . aws_query_encode((string) $value);
        }

        return implode('&', $pairs);
    }
}

if (!function_exists('get_product_image_s3_signing_key')) {
    function get_product_image_s3_signing_key(string $secretKey, string $dateStamp, string $region): string
    {
        $dateKey = hash_hmac_binary('sha256', $dateStamp, 'AWS4' . $secretKey);
        $dateRegionKey = hash_hmac_binary('sha256', $region, $dateKey);
        $dateRegionServiceKey = hash_hmac_binary('sha256', 's3', $dateRegionKey);

        return hash_hmac_binary('sha256', 'aws4_request', $dateRegionServiceKey);
    }
}

if (!function_exists('presign_product_image_s3_url')) {
    function presign_product_image_s3_url(string $objectUrl, int $expires): string
    {
        $accessKey = trim((string) config_get('AWS_ACCESS_KEY_ID', ''));
        $secretKey = (string) config_get('AWS_SECRET_ACCESS_KEY', '');
        $sessionToken = trim((string) config_get('AWS_SESSION_TOKEN', ''));
        $parts = parse_url($objectUrl);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '/';

        if ($accessKey === '' || $secretKey === '' || $host === '') {
            return $objectUrl;
        }

        $region = get_product_image_s3_region($host);
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $credentialScope = $dateStamp . '/' . $region . '/s3/aws4_request';
        $queryParams = [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => $accessKey . '/' . $credentialScope,
            'X-Amz-Date' => $amzDate,
            'X-Amz-Expires' => (string) $expires,
            'X-Amz-SignedHeaders' => 'host',
        ];

        if ($sessionToken !== '') {
            $queryParams['X-Amz-Security-Token'] = $sessionToken;
        }

        $canonicalQueryString = canonical_s3_query_string($queryParams);
        $canonicalRequest = implode("\n", [
            'GET',
            canonical_s3_uri($path),
            $canonicalQueryString,
            'host:' . $host . "\n",
            'host',
            'UNSIGNED-PAYLOAD',
        ]);
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);
        $signature = hash_hmac('sha256', $stringToSign, get_product_image_s3_signing_key($secretKey, $dateStamp, $region));

        return $scheme . '://' . $host . $path . '?' . $canonicalQueryString . '&X-Amz-Signature=' . $signature;
    }
}

if (!function_exists('send_product_image_s3_request')) {
    function send_product_image_s3_request(string $method, string $objectUrl, ?string $filePath = null, ?string $contentType = null): void
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('The PHP cURL extension is required for S3 image storage.');
        }

        $accessKey = trim((string) config_get('AWS_ACCESS_KEY_ID', ''));
        $secretKey = (string) config_get('AWS_SECRET_ACCESS_KEY', '');
        $sessionToken = trim((string) config_get('AWS_SESSION_TOKEN', ''));

        if ($accessKey === '' || $secretKey === '') {
            throw new RuntimeException('Set AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY in config/.env to upload product images to S3.');
        }

        $parts = parse_url($objectUrl);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '/';

        if ($host === '' || !in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException('PRODUCT_IMAGE_S3_URL must be an HTTP S3 bucket URL, for example https://your-bucket.s3.af-south-1.amazonaws.com.');
        }

        $region = get_product_image_s3_region($host);
        $payload = $filePath !== null ? file_get_contents($filePath) : '';

        if ($payload === false) {
            throw new RuntimeException('The uploaded image could not be read for S3 storage.');
        }

        $payloadHash = hash('sha256', $payload);
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $headers = [
            'host' => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $amzDate,
        ];

        if ($contentType !== null && $contentType !== '') {
            $headers['content-type'] = $contentType;
        }

        if ($sessionToken !== '') {
            $headers['x-amz-security-token'] = $sessionToken;
        }

        ksort($headers);

        $canonicalHeaders = '';
        foreach ($headers as $headerName => $headerValue) {
            $canonicalHeaders .= strtolower($headerName) . ':' . trim($headerValue) . "\n";
        }

        $signedHeaders = implode(';', array_keys($headers));
        $credentialScope = $dateStamp . '/' . $region . '/s3/aws4_request';
        $canonicalRequest = implode("\n", [
            $method,
            canonical_s3_uri($path),
            '',
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);
        $signingKey = get_product_image_s3_signing_key($secretKey, $dateStamp, $region);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);
        $authorization = sprintf(
            'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $accessKey,
            $credentialScope,
            $signedHeaders,
            $signature
        );
        $curlHeaders = ['Authorization: ' . $authorization];

        foreach ($headers as $headerName => $headerValue) {
            if ($headerName !== 'host') {
                $curlHeaders[] = $headerName . ': ' . $headerValue;
            }
        }

        $curl = curl_init($objectUrl);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $curlHeaders,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
        ]);

        if ($filePath !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        }

        $response = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        unset($curl);

        if ($response === false || $statusCode < 200 || $statusCode >= 300) {
            $detail = $curlError !== '' ? $curlError : trim(strip_tags((string) $response));
            throw new RuntimeException('S3 image request failed with HTTP ' . $statusCode . ($detail !== '' ? ': ' . $detail : '.'));
        }
    }
}

if (!function_exists('store_product_image_file')) {
    function store_product_image_file(string $tmpName, string $relativePath, string $mimeType): void
    {
        if (is_product_image_s3_enabled()) {
            send_product_image_s3_request('PUT', product_image_s3_object_url($relativePath), $tmpName, $mimeType);

            return;
        }

        $absolutePath = project_public_path($relativePath);
        $uploadAbsoluteDir = dirname($absolutePath);

        if (!is_dir($uploadAbsoluteDir) && !mkdir($uploadAbsoluteDir, 0755, true) && !is_dir($uploadAbsoluteDir)) {
            throw new RuntimeException('The product upload folder could not be created.');
        }

        if (!move_uploaded_file($tmpName, $absolutePath)) {
            throw new RuntimeException('The image could not be moved into the uploads folder.');
        }
    }
}

if (!function_exists('store_product_images')) {
    /**
     * Validate, move, and save multiple product images. The first successfully uploaded image is primary.
     */
    function store_product_images(PDO $pdo, int $productId, array $uploadedFiles): array
    {
        $files = array_values(array_filter(
            normalize_uploaded_files($uploadedFiles),
            fn (array $file): bool => ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        ));
        $uploadRelativeDir = get_product_upload_relative_dir();
        $maxBytes = get_effective_product_image_max_bytes();
        $maxImages = get_product_image_max_count();
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $savedPaths = [];

        if (count($files) > $maxImages) {
            throw new RuntimeException(sprintf('You can upload a maximum of %d product images per listing.', $maxImages));
        }

        try {
            foreach ($files as $file) {
                if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                    throw new RuntimeException(upload_error_message((int) $file['error']));
                }

                if ((int) $file['size'] > $maxBytes) {
                    throw new RuntimeException(sprintf('Each product image must be %s or smaller.', format_upload_size($maxBytes)));
                }

                $tmpName = (string) $file['tmp_name'];

                if (!is_uploaded_file($tmpName)) {
                    throw new RuntimeException('The uploaded file could not be verified.');
                }

                if (getimagesize($tmpName) === false) {
                    throw new RuntimeException('Only real image files are allowed.');
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($tmpName);

                if (!isset($allowedMimes[$mimeType])) {
                    throw new RuntimeException('Only JPG, JPEG, PNG, and WEBP images are allowed.');
                }

                $extension = $allowedMimes[$mimeType];
                $relativePath = '';

                do {
                    $filename = sprintf('product_%d_%s.%s', $productId, bin2hex(random_bytes(8)), $extension);
                    $relativePath = $uploadRelativeDir . '/' . $filename;
                    $absolutePath = project_public_path($relativePath);
                } while (!is_product_image_s3_enabled() && file_exists($absolutePath));

                store_product_image_file($tmpName, $relativePath, $mimeType);

                $savedPaths[] = $relativePath;
                $isPrimary = count($savedPaths) === 1;
                $stmt = $pdo->prepare(
                    'INSERT INTO product_images (product_id, image_path, is_primary)
                     VALUES (:product_id, :image_path, :is_primary)'
                );
                $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
                $stmt->bindValue(':image_path', $relativePath, PDO::PARAM_STR);
                $stmt->bindValue(':is_primary', $isPrimary, PDO::PARAM_BOOL);
                $stmt->execute();
            }
        } catch (Throwable $exception) {
            delete_uploaded_product_images($savedPaths);

            throw $exception;
        }

        return $savedPaths;
    }
}

if (!function_exists('delete_uploaded_product_images')) {
    function delete_uploaded_product_images(array $relativePaths): void
    {
        foreach ($relativePaths as $relativePath) {
            if (is_product_image_s3_enabled() && str_starts_with(trim((string) $relativePath, '/'), get_product_upload_relative_dir() . '/')) {
                try {
                    send_product_image_s3_request('DELETE', product_image_s3_object_url((string) $relativePath));
                } catch (Throwable) {
                    // Deleting a listing should not fail just because an object cleanup request failed.
                }

                continue;
            }

            $absolutePath = project_public_path($relativePath);

            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
        }
    }
}

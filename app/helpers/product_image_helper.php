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

        $base = trim((string) config_get('PUBLIC_WEB_BASE', '/public'), '/');

        return '/' . ($base === '' ? $cleanPath : $base . '/' . $cleanPath);
    }
}

if (!function_exists('product_image_placeholder_url')) {
    function product_image_placeholder_url(): string
    {
        return public_asset_url('assets/images/product-placeholder.svg');
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
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The image is larger than the allowed upload size.',
            UPLOAD_ERR_PARTIAL => 'The image only uploaded partially. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server is missing a temporary upload folder.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded image.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
            default => 'The image upload failed.',
        };
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
        $uploadAbsoluteDir = project_public_path($uploadRelativeDir);
        $maxBytes = (int) config_get('PRODUCT_IMAGE_MAX_BYTES', '5242880');
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

        if (!is_dir($uploadAbsoluteDir) && !mkdir($uploadAbsoluteDir, 0755, true) && !is_dir($uploadAbsoluteDir)) {
            throw new RuntimeException('The product upload folder could not be created.');
        }

        try {
            foreach ($files as $file) {
                if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                    throw new RuntimeException(upload_error_message((int) $file['error']));
                }

                if ((int) $file['size'] > $maxBytes) {
                    throw new RuntimeException('Each product image must be 5MB or smaller.');
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
                $absolutePath = '';

                do {
                    $filename = sprintf('product_%d_%s.%s', $productId, bin2hex(random_bytes(8)), $extension);
                    $relativePath = $uploadRelativeDir . '/' . $filename;
                    $absolutePath = project_public_path($relativePath);
                } while (file_exists($absolutePath));

                if (!move_uploaded_file($tmpName, $absolutePath)) {
                    throw new RuntimeException('The image could not be moved into the uploads folder.');
                }

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
            $absolutePath = project_public_path($relativePath);

            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
        }
    }
}

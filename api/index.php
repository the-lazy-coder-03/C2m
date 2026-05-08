<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestPath = '/' . ltrim(rawurldecode($requestPath), '/');

$candidatePaths = [];

if ($requestPath === '/') {
    $candidatePaths[] = 'index.php';
} else {
    $cleanPath = trim($requestPath, '/');

    if (!str_contains($cleanPath, '..')) {
        $candidatePaths[] = $cleanPath;

        if (!str_starts_with($cleanPath, 'public/') && !str_starts_with($cleanPath, 'admin/')) {
            $candidatePaths[] = 'public/' . $cleanPath;
        }

        if (!str_ends_with($cleanPath, '.php')) {
            $candidatePaths[] = $cleanPath . '.php';
            $candidatePaths[] = 'public/' . $cleanPath . '.php';
        }
    }
}

$targetFile = null;

foreach ($candidatePaths as $candidatePath) {
    $absolutePath = realpath($projectRoot . '/' . $candidatePath);

    if (
        $absolutePath !== false
        && str_starts_with($absolutePath, $projectRoot)
        && is_file($absolutePath)
        && pathinfo($absolutePath, PATHINFO_EXTENSION) === 'php'
    ) {
        $relativePath = ltrim(str_replace($projectRoot, '', $absolutePath), '/');

        if (
            $relativePath === 'index.php'
            || str_starts_with($relativePath, 'public/')
            || str_starts_with($relativePath, 'admin/')
        ) {
            $targetFile = $absolutePath;
            break;
        }
    }
}

if ($targetFile === null) {
    http_response_code(404);
    echo '404 Not Found';
    return;
}

chdir(dirname($targetFile));

$_SERVER['SCRIPT_FILENAME'] = $targetFile;
$_SERVER['SCRIPT_NAME'] = $requestPath === '/' ? '/index.php' : $requestPath;
$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];

require $targetFile;

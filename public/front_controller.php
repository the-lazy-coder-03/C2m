<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $staticPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $staticFile = realpath(__DIR__ . $staticPath);

    if ($staticPath !== '/' && $staticFile !== false && str_starts_with($staticFile, __DIR__) && is_file($staticFile)) {
        return false;
    }
}

require_once __DIR__ . '/../app/Router.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestPath = '/' . trim(rawurldecode($requestPath), '/');
$requestPath = $requestPath === '/' ? '/' : rtrim($requestPath, '/');

$router = new Router();
require __DIR__ . '/../app/routes.php';

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$isHeadRequest = $requestMethod === 'HEAD';

if ($isHeadRequest) {
    ob_start();
    $requestMethod = 'GET';
}

if (!$router->dispatch($requestMethod, $requestPath)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
}

if ($isHeadRequest) {
    ob_end_clean();
}

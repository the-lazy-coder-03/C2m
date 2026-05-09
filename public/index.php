<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Router.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$requestPath = '/' . trim(rawurldecode($requestPath), '/');
$requestPath = $requestPath === '/' ? '/' : rtrim($requestPath, '/');

$router = new Router();
require __DIR__ . '/../app/routes.php';

if (!$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $requestPath)) {
    http_response_code(404);
    require __DIR__ . '/404.php';
}

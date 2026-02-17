<?php
/**
 * CMS API - REST entry point
 * Routes: GET/POST /departments, GET/PUT/DELETE /departments/{id}
 *         GET/POST /categories, GET/PUT/DELETE /categories/{id}
 *         GET/POST /users, GET/PUT/DELETE /users/{id}
 *         GET/POST /complaints, GET/PUT/DELETE /complaints/{id}
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/Response.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = isset($_GET['path']) ? trim($_GET['path'], '/') : '';
if ($path === '' && !empty($_SERVER['REQUEST_URI'])) {
    $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $parts = explode('/', $uri);
    $base = defined('BASE_PATH') ? trim(BASE_PATH, '/') : '';
    $baseParts = $base ? explode('/', $base) : [];
    $skip = count($baseParts) + 1; // base + "cms_api"
    if (isset($parts[$skip - 1]) && $parts[$skip - 1] === 'cms_api') {
        $path = implode('/', array_slice($parts, $skip));
    }
}
$pathParts = $path ? explode('/', $path) : [];

$resource = $pathParts[0] ?? '';
$id = isset($pathParts[1]) && ctype_digit($pathParts[1]) ? (int) $pathParts[1] : null;

$allowed = ['departments', 'categories', 'users', 'complaints'];
if (!in_array($resource, $allowed, true)) {
    if ($resource === '') {
        Response::json([
            'name' => 'Complaint Management System API',
            'version' => '1.0',
            'endpoints' => [
                'GET/POST /departments',
                'GET/PUT/DELETE /departments/{id}',
                'GET/POST /categories',
                'GET/PUT/DELETE /categories/{id}',
                'GET/POST /users',
                'GET/PUT/DELETE /users/{id}',
                'GET/POST /complaints',
                'GET/PUT/DELETE /complaints/{id}',
            ],
        ], 200);
    }
    Response::error('Unknown resource: ' . $resource, 404);
}

$file = __DIR__ . '/endpoints/' . $resource . '.php';
if (!is_file($file)) {
    Response::error('Endpoint not implemented', 501);
}

require $file;

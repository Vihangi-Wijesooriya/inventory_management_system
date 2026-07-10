<?php
/**
 * API front controller.
 * Routes /api/{resource}[/{id}][/{action}] to api/resources/{resource}.php.
 * Session auth: everything except auth/login requires a logged-in user.
 */

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    // 'secure' => true, // enable when serving over HTTPS
]);
session_start();

require __DIR__ . '/../config/database.php';
require __DIR__ . '/helpers.php';

set_exception_handler(function (Throwable $e) {
    error_log('API error: ' . $e->getMessage());
    json_error('Server error.', 500);
});

$method = $_SERVER['REQUEST_METHOD'];

// Method override for multipart updates (PHP can't parse multipart PUT).
if ($method === 'POST' && ($_POST['_method'] ?? '') === 'PUT') {
    $method = 'PUT';
}

// CSRF-lite: state-changing requests must carry the custom header set by api.js.
// Browsers won't attach it cross-site, and SameSite=Lax blocks cookie-bearing
// cross-site POSTs anyway.
if (!in_array($method, ['GET', 'HEAD'], true)) {
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') {
        json_error('Missing request header.', 403);
    }
}

// Resolve path: /api/products/5 -> ['products', '5']
$uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = strpos($uri, '/api/');
$path = $base === false ? '' : substr($uri, $base + 5);
$segments = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));

$resource = $segments[0] ?? '';
$idOrAction = $segments[1] ?? null;
$id = ($idOrAction !== null && ctype_digit($idOrAction)) ? (int)$idOrAction : null;
$action = ($idOrAction !== null && !ctype_digit($idOrAction)) ? $idOrAction : ($segments[2] ?? null);

$allowed = ['auth', 'categories', 'products', 'suppliers', 'customers',
            'purchases', 'sales', 'dashboard', 'reports', 'users'];

if (!in_array($resource, $allowed, true)) {
    json_error('Unknown resource.', 404);
}

// Auth gate
$publicRoutes = $resource === 'auth' && in_array($action, ['login'], true);
if (!$publicRoutes && empty($_SESSION['user'])) {
    json_error('Unauthorized.', 401);
}

// Admin-only resources
if ($resource === 'users' && ($_SESSION['user']['role'] ?? '') !== 'admin') {
    json_error('Admin access required.', 403);
}

function current_user(): array
{
    return $_SESSION['user'] ?? [];
}

require __DIR__ . '/resources/' . $resource . '.php';
handle($method, $id, $action);

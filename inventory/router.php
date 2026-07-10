<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = __DIR__ . $uri;

if ($uri !== '/' && file_exists($path) && is_file($path)) {
    return false;
}

if (str_starts_with($uri, '/api/')) {
    require __DIR__ . '/api/index.php';
    return;
}

return false;

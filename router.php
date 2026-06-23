<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('#^/(auth|services|bookings|reviews|messages|admin)#', $uri)) {
    require __DIR__ . '/backend/index.php';
    return;
}

if ($uri === '/' || $uri === '') {
    readfile(__DIR__ . '/frontend/index.html');
    return;
}

$file = __DIR__ . '/frontend' . $uri;
if (file_exists($file)) {
    return false;
}

http_response_code(404);
echo '404 - Not found: ' . $uri;

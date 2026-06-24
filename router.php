<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('#^/(auth|services|bookings|reviews|messages|admin)#', $uri)) {
    $_SERVER['SCRIPT_NAME'] = '';
    require __DIR__ . '/backend/index.php';
    return;
}

if ($uri === '/' || $uri === '') {
    readfile(__DIR__ . '/frontend/index.html');
    return;
}

$file = __DIR__ . $uri;
if (file_exists($file)) {
    $mime = 'text/html';
    if (str_ends_with($uri, '.css')) $mime = 'text/css';
    elseif (str_ends_with($uri, '.js')) $mime = 'application/javascript';
    header("Content-Type: $mime");
    readfile($file);
    return;
}

http_response_code(404);
echo '404 - Not found: ' . $uri;

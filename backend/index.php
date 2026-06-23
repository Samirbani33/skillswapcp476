<?php
/**
 * index.php — SkillSwap API Front Controller
 * Routes incoming requests to the correct handler
 * 
 * Routes:
 *   /auth/{action}       — register, login, logout, me
 *   /services/{id?}      — service CRUD
 *   /bookings/{id?}      — booking management
 *   /reviews             — review submission and listing
 *   /messages/{id?}      — messaging
 *   /admin/{action}/{id?}— admin moderation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); exit;
}

session_start();

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Strip script directory prefix so routes work in any subfolder
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$cleanPath = substr($uri, strlen($scriptDir));
$segments  = array_values(array_filter(explode('/', $cleanPath)));

$resource = $segments[0] ?? '';
$id       = $segments[1] ?? null;

switch ($resource) {
    case 'auth':
        require __DIR__ . '/routes/auth.php';
        break;
    case 'services':
        require __DIR__ . '/routes/services.php';
        break;
    case 'bookings':
        require __DIR__ . '/routes/bookings.php';
        break;
    case 'reviews':
        require __DIR__ . '/routes/reviews.php';
        break;
    case 'messages':
        require __DIR__ . '/routes/messages.php';
        break;
    case 'admin':
        require __DIR__ . '/routes/admin.php';
        break;
    default:
        http_response_code(404);
        echo json_encode([
            'error'   => 'Route not found',
            'routes'  => ['/auth','/services','/bookings','/reviews','/messages','/admin']
        ]);
}
?>

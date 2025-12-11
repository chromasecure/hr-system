<?php
// Front controller for attendance API
require __DIR__ . '/../src/Autoload.php';

// If using composer for JWT: require vendor autoload if present
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

use App\Config\Database;
use App\Router\Router;
use App\Helpers\Response;
use App\Controllers\DeviceController;
use App\Controllers\AttendanceController;
use App\Controllers\WebAuthController;
use App\Controllers\WebBranchController;
use App\Controllers\WebDeviceController;
use App\Middleware\DeviceAuth;
use App\Helpers\JwtHelper;

$config = require __DIR__ . '/../config.php';

try {
    $db = new Database($config['db']);
    $pdo = $db->pdo();
    $jwt = new JwtHelper($config['jwt_secret'], $config['jwt_exp_minutes']);

    $router = new Router();

    // Device endpoints
    $router->add('POST', '/api/device/register-or-login', function() use ($pdo, $config) {
        (new DeviceController($pdo, $config))->registerOrLogin();
    });
    $router->add('POST', '/api/device/heartbeat', function() use ($pdo) {
        $dev = DeviceAuth::authenticate($pdo);
        (new DeviceController($pdo, []))->heartbeat($dev);
    });
    $router->add('GET', '/api/device/employees', function() use ($pdo) {
        $dev = DeviceAuth::authenticate($pdo);
        (new DeviceController($pdo, []))->employees($dev);
    });

    // Attendance endpoints
    $router->add('POST', '/api/attendance/mark', function() use ($pdo, $config) {
        $dev = DeviceAuth::authenticate($pdo);
        (new AttendanceController($pdo, $config))->mark($dev);
    });
    $router->add('POST', '/api/attendance/sync-offline', function() use ($pdo, $config) {
        $dev = DeviceAuth::authenticate($pdo);
        (new AttendanceController($pdo, $config))->syncOffline($dev);
    });

    // Web auth
    $router->add('POST', '/api/web/login', function() use ($pdo, $jwt) {
        (new WebAuthController($pdo, $jwt))->login();
    });

    // Web resources
    $router->add('GET', '/api/web/branches', function() use ($pdo, $jwt) {
        (new WebBranchController($pdo, $jwt))->branches();
    });
    $router->add('GET', '/api/web/branches/{id}/employees', function($params) use ($pdo, $jwt) {
        (new WebBranchController($pdo, $jwt))->branchEmployees($params);
    });
    $router->add('GET', '/api/web/branches/{id}/attendance', function($params) use ($pdo, $jwt) {
        (new WebBranchController($pdo, $jwt))->branchAttendance($params);
    });

    // Devices (web, admin)
    $router->add('GET', '/api/web/devices', function() use ($pdo, $jwt) {
        (new WebDeviceController($pdo, $jwt))->list();
    });
    $router->add('POST', '/api/web/devices/create', function() use ($pdo, $jwt) {
        (new WebDeviceController($pdo, $jwt))->create();
    });

    $router->get('/api/web/employees', function() use ($pdo, $jwt) {
    (new \App\Controllers\WebBranchController($pdo, $jwt))->myEmployees();
});


    // Normalize URI so routes don't include /api/public prefix
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove the base path (/api/public)
$basePath = '/api/public';
if (strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

// Dispatch using the cleaned path, e.g. /api/web/login
$router->dispatch($_SERVER['REQUEST_METHOD'], $uri ?: '/');

} catch (Throwable $e) {
    Response::error('Server error: ' . $e->getMessage(), 500);
}

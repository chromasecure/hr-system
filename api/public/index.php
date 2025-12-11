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
use App\Controllers\AttendanceController;
use App\Controllers\WebAuthController;
use App\Controllers\WebBranchController;
use App\Controllers\WebDeviceController;
use App\Controllers\WebEmployeeController;
use App\Helpers\JwtHelper;

$config = require __DIR__ . '/../config.php';

try {
    $db = new Database($config['db']);
    $pdo = $db->pdo();
    $jwt = new JwtHelper($config['jwt_secret'], $config['jwt_exp_minutes']);

    $router = new Router();

    // Attendance endpoints (branch JWT)
    $router->add('POST', '/api/attendance/mark', function() use ($pdo, $config, $jwt) {
        (new AttendanceController($pdo, $config, $jwt))->markForManager();
    });
    $router->add('POST', '/api/attendance/sync-offline', function() use ($pdo, $config, $jwt) {
        (new AttendanceController($pdo, $config, $jwt))->syncOfflineManager();
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

    // Employees for web
    $router->get('/api/web/employees', function() use ($pdo, $jwt) {
        (new \App\Controllers\WebBranchController($pdo, $jwt))->myEmployees();
    });
    $router->post('/api/web/employees/attach-face', function() use ($pdo, $jwt) {
        (new \App\Controllers\WebEmployeeController($pdo, $jwt))->attachFace();
    });
    $router->post('/api/web/employees/assign-face', function() use ($pdo, $jwt) {
        (new \App\Controllers\WebEmployeeController($pdo, $jwt))->attachFace();
    });

    // Normalize URI so routes don't include /api/public prefix
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $basePath = dirname($_SERVER['SCRIPT_NAME'] ?? '') ?: '';
    if ($basePath && $basePath !== '/' && strpos($uri, $basePath) === 0) {
        $uri = substr($uri, strlen($basePath));
    }
    if ($uri === '') $uri = '/';

    // Dispatch using the cleaned path, e.g. /api/web/login
    $router->dispatch($_SERVER['REQUEST_METHOD'], $uri);

} catch (Throwable $e) {
    Response::error('Server error: ' . $e->getMessage(), 500);
}

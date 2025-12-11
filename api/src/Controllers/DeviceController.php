<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Models\Branch;
use App\Models\Device;
use App\Models\Employee;
use PDO;

class DeviceController {
    public function __construct(private PDO $pdo, private array $config) {}

    public function registerOrLogin(): void {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $branchCode = $body['branch_code'] ?? '';
        $deviceName = $body['device_name'] ?? '';
        $regSecret  = $body['registration_secret'] ?? '';

        if (!$branchCode || !$deviceName || !$regSecret) {
            Response::error('Missing fields', 400);
        }
        if ($regSecret !== $this->config['device_registration_secret']) {
            Response::error('Invalid registration secret', 401);
        }

        $branch = (new Branch($this->pdo))->findByCode($branchCode);
        if (!$branch || !$branch['is_active']) {
            Response::error('Branch not found or inactive', 404);
        }

        $token = bin2hex(random_bytes(32));
        $secret = bin2hex(random_bytes(16));
        $deviceId = (new Device($this->pdo))->create((int)$branch['id'], $deviceName, $token, $secret);

        Response::ok([
            'device_token' => $token,
            'device_id' => $deviceId,
            'branch_id' => $branch['id'],
            'api_secret' => $secret
        ]);
    }

    public function heartbeat(array $device): void {
        (new Device($this->pdo))->updateHeartbeat((int)$device['id'], $_SERVER['REMOTE_ADDR'] ?? null);
        Response::ok(['server_time' => date('Y-m-d H:i:s')]);
    }

    public function employees(array $device): void {
        $emps = (new Employee($this->pdo))->allActiveByBranch((int)$device['branch_id']);
        Response::ok(['employees' => $emps]);
    }
}

<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\JwtHelper;
use App\Middleware\WebAuth;
use App\Models\Device;
use PDO;

class WebDeviceController {
    public function __construct(private PDO $pdo, private JwtHelper $jwt) {}

    public function list(): void {
        $user = WebAuth::authenticate($this->pdo, $this->jwt, ['super_admin']);
        $st = $this->pdo->query("SELECT d.*, b.name AS branch_name FROM devices d JOIN branches b ON b.id=d.branch_id ORDER BY d.id DESC");
        Response::ok(['devices' => $st->fetchAll()]);
    }

    public function create(): void {
        $user = WebAuth::authenticate($this->pdo, $this->jwt, ['super_admin']);
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $branchId = (int)($body['branch_id'] ?? 0);
        $name = trim($body['name'] ?? '');
        if (!$branchId || !$name) {
            Response::error('Missing branch_id or name');
        }
        $token = bin2hex(random_bytes(32));
        $secret = bin2hex(random_bytes(16));
        $id = (new Device($this->pdo))->create($branchId, $name, $token, $secret);
        Response::ok(['device_id' => $id, 'device_token' => $token, 'api_secret' => $secret]);
    }
}

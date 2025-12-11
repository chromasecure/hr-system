<?php
namespace App\Middleware;

use App\Helpers\Response;
use App\Models\Device;
use PDO;

class DeviceAuth {
    public static function authenticate(PDO $pdo): array {
        $token = $_SERVER['HTTP_X_DEVICE_TOKEN'] ?? null;
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $token = $token ?: ($body['device_token'] ?? null);

        if (!$token) {
            Response::error('Missing device token', 401);
        }

        $device = (new Device($pdo))->findByToken($token);
        if (!$device || !$device['is_active']) {
            Response::error('Invalid device', 401);
        }

        return $device;
    }
}

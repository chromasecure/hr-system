<?php
namespace App\Middleware;

use App\Helpers\Response;
use App\Helpers\JwtHelper;
use App\Models\User;
use PDO;

class WebAuth {
    public static function authenticate(PDO $pdo, JwtHelper $jwt, array $roles = []): array {
        $hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($hdr, 'Bearer ')) {
            Response::error('Missing token', 401);
        }
        $token = substr($hdr, 7);
        try {
            $payload = $jwt->decode($token);
        } catch (\Throwable $e) {
            Response::error('Invalid token', 401);
        }
        $user = (new User($pdo))->findById((int)$payload->uid);
        if (!$user || !$user['is_active']) {
            Response::error('User inactive', 401);
        }
        if (!empty($roles) && !in_array($user['role'], $roles, true)) {
            Response::error('Forbidden', 403);
        }
        return $user;
    }
}

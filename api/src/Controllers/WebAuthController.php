<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\JwtHelper;
use App\Models\User;
use PDO;

class WebAuthController {
    public function __construct(private PDO $pdo, private JwtHelper $jwt) {}

 public function login(): void
{
    // Read JSON body
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? [];

    $email    = isset($data['email']) ? trim($data['email']) : '';
    $password = $data['password'] ?? '';

    if ($email === '' || $password === '') {
        Response::error('Email and password are required', 400);
        return;
    }

    $userModel = new User($this->pdo);

    // Find user by email
    $user = $userModel->findByEmail($email);

    if (!$user) {
        Response::error('Invalid credentials', 401);
        return;
    }

    if ((int)$user['is_active'] !== 1) {
        Response::error('User inactive', 403);
        return;
    }

    if (!password_verify($password, $user['password_hash'])) {
        Response::error('Invalid credentials', 401);
        return;
    }

    $token = $this->jwt->issueToken([
        'uid'       => $user['id'],
        'role'      => $user['role'],
        'branch_id' => $user['branch_id'] ?? null,
    ]);

    Response::ok([
        'token'     => $token,
        'role'      => $user['role'],
        'branch_id' => $user['branch_id'] ?? null,
    ]);
}



}

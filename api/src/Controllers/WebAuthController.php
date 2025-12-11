<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\JwtHelper;
use App\Models\User;
use PDO;

class WebAuthController
{
    public function __construct(private PDO $pdo, private JwtHelper $jwt) {}

    public function login(): void
    {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = [];
        }

        // Allow username OR email (Flutter sends in "email" field)
        $identifier = trim($data['email'] ?? '');
        $password   = trim($data['password'] ?? '');

        if ($identifier === '' || $password === '') {
            Response::error('Email/username and password are required.', 400);
        }

        $userModel = new User($this->pdo);
        $user = $userModel->findByIdentifier($identifier);

        if (!$user) {
            Response::error('Invalid credentials.', 401);
        }

        if ((int)($user['is_active'] ?? 1) !== 1) {
            Response::error('User inactive.', 401);
        }

        if (!password_verify($password, $user['password_hash'])) {
            Response::error('Invalid credentials.', 401);
        }

        // Only allow branch role
        if ($user['role'] !== 'branch') {
            Response::error('Only branch managers can log in here.', 403);
        }

        $branchId = (int)($user['branch_id'] ?? 0);
        if ($branchId === 0) {
            Response::error('No branch assigned to this user.', 400);
        }

        $token = $this->jwt->issueToken([
            'uid'       => (int)$user['id'],
            'role'      => $user['role'],
            'branch_id' => $branchId,
        ]);

        Response::ok([
            'token'   => $token,
            'manager' => [
                'id'        => (int)$user['id'],
                'username'  => $user['username'],
                'branch_id' => $branchId,
                'role'      => $user['role'],
            ],
        ]);
    }
}

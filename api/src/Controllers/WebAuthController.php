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
        // Read JSON body
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = [];
        }

        // Flutter sends "email" field – we treat it as username.
        $identifier = trim($data['email'] ?? '');
        $password   = trim($data['password'] ?? '');

        if ($identifier === '' || $password === '') {
            Response::error('Email/username and password are required.', 400);
        }

        $userModel = new User($this->pdo);

        // Try username first (this is what your dashboard uses)
        $user = $userModel->findByUsername($identifier);

        // Optionally fall back to email if you later add an email column
        if (!$user) {
            $user = $userModel->findByEmail($identifier);
        }

        if (!$user) {
            Response::error('Invalid credentials.', 401);
        }

        if (isset($user['is_active']) && !$user['is_active']) {
            Response::error('User inactive.', 401);
        }

        if (!password_verify($password, $user['password_hash'])) {
            Response::error('Invalid credentials.', 401);
        }

        // Only allow branch users to log into the device app
        if ($user['role'] !== 'branch') {
            Response::error('Only branch users can log in here.', 403);
        }

        $branchId = $user['branch_id'] ?? null;
        if (!$branchId) {
            Response::error('No branch assigned to this user.', 400);
        }

        // Issue JWT with user id, role, branch id
        $token = $this->jwt->issueToken([
            'uid'       => (int)$user['id'],
            'role'      => $user['role'],
            'branch_id' => (int)$branchId,
        ]);

        Response::ok([
            'token'     => $token,
            'role'      => $user['role'],
            'branch_id' => (int)$branchId,
        ]);
    }
}

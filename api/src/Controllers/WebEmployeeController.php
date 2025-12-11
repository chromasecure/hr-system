<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\JwtHelper;
use App\Middleware\WebAuth;
use App\Models\Employee;
use PDO;

class WebEmployeeController
{
    public function __construct(private PDO $pdo, private JwtHelper $jwt) {}

    public function attachFace(): void
    {
        $user = WebAuth::authenticate($this->pdo, $this->jwt, ['branch', 'super_admin']);
        $branchId = (int)($user['branch_id'] ?? 0);
        if ($user['role'] !== 'super_admin' && $branchId === 0) {
            Response::error('No branch assigned to user.', 400);
        }

        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $code = trim($body['employee_code'] ?? '');
        $imageB64 = $body['image'] ?? '';

        if ($code === '' || $imageB64 === '') {
            Response::error('employee_code and image are required.', 400);
        }

        $empRepo = new Employee($this->pdo);
        $emp = $empRepo->findByCodeInBranch($code, $branchId);
        if (!$emp) {
            Response::error('Employee not found in this branch.', 404);
        }

        $bin = base64_decode($imageB64, true);
        if ($bin === false) {
            Response::error('Invalid image data.', 400);
        }

        $facesDir = realpath(__DIR__ . '/../../..') . '/uploads/faces';
        if (!is_dir($facesDir)) {
            mkdir($facesDir, 0777, true);
        }
        $filePath = $facesDir . '/' . $emp['id'] . '.jpg';
        file_put_contents($filePath, $bin);

        $relativePath = '/uploads/faces/' . $emp['id'] . '.jpg';
        $empRepo->updateFacePath((int)$emp['id'], $relativePath);

        Response::ok(['message' => 'Face attached', 'path' => $relativePath]);
    }
}

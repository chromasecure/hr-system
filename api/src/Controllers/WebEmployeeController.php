<?php
namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\JwtHelper;
use App\Middleware\WebAuth;
use App\Models\Employee;
use App\Models\PendingEmployee;
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
        $templateHash = $body['face_template'] ?? null;

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
        $empRepo->updateFaceData((int)$emp['id'], $relativePath, $templateHash);

        Response::ok(['message' => 'Face attached', 'path' => $relativePath]);
    }

    public function createPending(): void
    {
        $user = WebAuth::authenticate($this->pdo, $this->jwt, ['branch', 'super_admin']);
        $branchId = (int)($user['branch_id'] ?? 0);
        if ($branchId === 0 && !empty($body['branch_id'])) {
            $branchId = (int)$body['branch_id']; // allow super admin to target a branch
        }
        if ($branchId === 0) {
            Response::error('No branch assigned to user.', 400);
        }

        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $name = trim($body['name'] ?? '');
        $code = trim($body['employee_code'] ?? '');
        $imageB64 = $body['image'] ?? '';

        if ($name === '' || $code === '' || $imageB64 === '') {
            Response::error('name, employee_code and image are required.', 400);
        }

        $bin = base64_decode($imageB64, true);
        if ($bin === false) {
            Response::error('Invalid image data.', 400);
        }

        $pendingRepo = new PendingEmployee($this->pdo);
        $id = $pendingRepo->create([
            'employee_code' => $code,
            'name' => $name,
            'branch_id' => $branchId,
            'designation_id' => $body['designation_id'] ?? null,
            'contact' => $body['contact'] ?? null,
            'basic_salary' => $body['basic_salary'] ?? null,
            'commission' => $body['commission'] ?? null,
            'joining_date' => $body['joining_date'] ?? date('Y-m-d'),
            'face_image_path' => null,
            'created_by' => (int)($user['id'] ?? 0),
        ]);

        $facesDir = realpath(__DIR__ . '/../../..') . '/uploads/pending_faces';
        if (!is_dir($facesDir)) {
            mkdir($facesDir, 0777, true);
        }
        $filePath = $facesDir . '/' . $id . '.jpg';
        file_put_contents($filePath, $bin);
        $relativePath = '/uploads/pending_faces/' . $id . '.jpg';
        $pendingRepo->updateFacePath($id, $relativePath);

        Response::ok(['message' => 'Pending employee created', 'id' => $id]);
    }
}

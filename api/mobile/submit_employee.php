<?php
// api/mobile/submit_employee.php

require __DIR__ . '/../../bootstrap.php';

use App\Config\Database;
use PDO;

header('Content-Type: application/json; charset=utf-8');

try {
    $db  = new Database($config);
    $pdo = $db->getConnection();

    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (!is_array($data)) {
        throw new Exception('Invalid JSON');
    }

    $branchId   = (int)($data['branch_id'] ?? 0);
    $empCode    = trim($data['emp_code'] ?? '');
    $name       = trim($data['name'] ?? '');
    $contact    = trim($data['contact'] ?? '');
    $designationId = !empty($data['designation_id']) ? (int)$data['designation_id'] : null;
    $basicSalary   = !empty($data['basic_salary'])   ? (float)$data['basic_salary']   : null;
    $commission    = !empty($data['commission_percent']) ? (float)$data['commission_percent'] : null;
    $joiningDate   = $data['joining_date'] ?? date('Y-m-d');
    $faceBase64    = $data['face_image'] ?? null;

    if (!$branchId || !$empCode || !$name) {
        throw new Exception('branch_id, emp_code and name are required');
    }

    $facePath = null;

    if ($faceBase64) {
        $binary = base64_decode($faceBase64);
        if ($binary === false) {
            throw new Exception('Invalid face image');
        }

        // change path if your public folder is different
        $dir = __DIR__ . '/../../public/storage/faces/pending';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $fileName = 'face_' . $branchId . '_' . $empCode . '_' . time() . '.jpg';
        $fullPath = $dir . '/' . $fileName;

        file_put_contents($fullPath, $binary);

        // path stored in DB (relative to web root)
        $facePath = 'storage/faces/pending/' . $fileName;
    }

    $sql = "
        INSERT INTO pending_employees (
            branch_id,
            emp_code,
            name,
            contact,
            designation_id,
            basic_salary,
            commission_percent,
            joining_date,
            face_image_path
        ) VALUES (
            :branch_id,
            :emp_code,
            :name,
            :contact,
            :designation_id,
            :basic_salary,
            :commission_percent,
            :joining_date,
            :face_image_path
        )
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':branch_id'          => $branchId,
        ':emp_code'           => $empCode,
        ':name'               => $name,
        ':contact'            => $contact,
        ':designation_id'     => $designationId,
        ':basic_salary'       => $basicSalary,
        ':commission_percent' => $commission,
        ':joining_date'       => $joiningDate,
        ':face_image_path'    => $facePath,
    ]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}

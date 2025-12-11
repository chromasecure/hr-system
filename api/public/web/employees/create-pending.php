<?php
// TODO: copy your bootstrap / DB / auth includes from an existing web API file,
// e.g. api/web/login.php

use App\Config\Database;
use PDO;

header('Content-Type: application/json; charset=utf-8');

try {
    // if your bootstrap already gives you $pdo and $authUser, use that.
    // otherwise get connection similarly to your other endpoints.

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new Exception('Invalid JSON');
    }

    // accept branch from request OR from logged-in user
    $branchId = (int)($data['branch_id'] ?? ($authUser['branch_id'] ?? 0));
    $empCode  = trim($data['emp_code'] ?? $data['employee_code'] ?? '');
    $name     = trim($data['name'] ?? '');
    $contact  = trim($data['contact'] ?? '');
    $designationId =
        !empty($data['designation_id']) ? (int)$data['designation_id'] : null;
    $basicSalary =
        !empty($data['basic_salary']) ? (float)$data['basic_salary'] : null;
    $commission =
        !empty($data['commission'] ?? $data['commission_percent'] ?? null)
            ? (float)($data['commission'] ?? $data['commission_percent'])
            : null;
    $joiningDate = $data['joining_date'] ?? date('Y-m-d');
    $imageBase64 = $data['image'] ?? $data['face_image'] ?? null;

    if (!$branchId || !$empCode || !$name) {
        throw new Exception('branch_id, employee_code and name are required');
    }

    // save face image if provided
    $facePath = null;
    if ($imageBase64) {
        $binary = base64_decode($imageBase64);
        if ($binary === false) {
            throw new Exception('Invalid image');
        }
        $dir = __DIR__ . '/../../../storage/faces/pending';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $fileName = 'pending_' . $branchId . '_' . $empCode . '_' . time() . '.jpg';
        file_put_contents($dir . '/' . $fileName, $binary);
        $facePath = 'storage/faces/pending/' . $fileName;
    }

    $stmt = $pdo->prepare("
        INSERT INTO pending_employees
            (branch_id, emp_code, name, contact, designation_id,
             basic_salary, commission_percent, joining_date, face_image_path)
        VALUES
            (:branch_id, :emp_code, :name, :contact, :designation_id,
             :basic_salary, :commission_percent, :joining_date, :face_image_path)
    ");
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
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

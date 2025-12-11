<?php
// api/mobile/mark_attendance_by_face.php

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

    $branchId = (int)($data['branch_id'] ?? 0);
    $deviceId = trim($data['device_id'] ?? '');
    $date     = $data['date'] ?? date('Y-m-d');
    $imageB64 = $data['image'] ?? null;

    if (!$branchId || !$deviceId || !$imageB64) {
        throw new Exception('branch_id, device_id and image are required');
    }

    // resolve employee from device mapping
    $stmt = $pdo->prepare("
        SELECT employee_id
        FROM devices
        WHERE device_id = :device_id
          AND branch_id = :branch_id
        LIMIT 1
    ");
    $stmt->execute([
        ':device_id' => $deviceId,
        ':branch_id' => $branchId,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new Exception('Device not linked to any employee');
    }

    $employeeId = (int)$row['employee_id'];

    // save face image (optional)
    $binary = base64_decode($imageB64);
    if ($binary === false) {
        throw new Exception('Invalid image data');
    }

    $dir = __DIR__ . '/../../public/storage/attendance_faces/' . $date;
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $fileName = 'emp_' . $employeeId . '_' . time() . '.jpg';
    $fullPath = $dir . '/' . $fileName;
    file_put_contents($fullPath, $binary);

    $facePath = 'storage/attendance_faces/' . $date . '/' . $fileName;

    // upsert attendance row
    $sql = "
        INSERT INTO attendance (employee_id, date, status, face_image_path)
        VALUES (:employee_id, :date, 'Present', :face_path)
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            face_image_path = VALUES(face_image_path)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':employee_id' => $employeeId,
        ':date'        => $date,
        ':face_path'   => $facePath,
    ]);

    echo json_encode([
        'success'      => true,
        'employee_id'  => $employeeId,
        'face_image'   => $facePath,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}

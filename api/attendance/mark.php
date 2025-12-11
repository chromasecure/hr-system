<?php
// TODO: copy bootstrap / DB includes used by other /api/attendance/* files
// (if you don't have any yet, copy from a web API and adjust paths)

use App\Config\Database;
use PDO;

header('Content-Type: application/json; charset=utf-8');

try {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new Exception('Invalid JSON');
    }

    $employeeCode = trim($data['employee_code'] ?? '');
    $status       = trim($data['status'] ?? 'in');
    $timestamp    = $data['timestamp'] ?? date('c');
    $meta         = $data['meta'] ?? [];
    $imageBase64  = is_array($meta) ? ($meta['face_image_base64'] ?? null) : null;

    if ($employeeCode === '') {
        throw new Exception('employee_code is required');
    }

    // locate employee
    $stmt = $pdo->prepare("
        SELECT id, branch_id
        FROM employees
        WHERE emp_code = :code
        LIMIT 1
    ");
    $stmt->execute([':code' => $employeeCode]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$emp) {
        throw new Exception('Employee not found');
    }

    $employeeId = (int)$emp['id'];
    $date = substr($timestamp, 0, 10); // YYYY-MM-DD
    $facePath = null;

    if ($imageBase64) {
        $binary = base64_decode($imageBase64);
        if ($binary !== false) {
            $dir = __DIR__ . '/../../storage/attendance_faces/' . $date;
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $fileName = 'emp_' . $employeeId . '_' . time() . '.jpg';
            file_put_contents($dir . '/' . $fileName, $binary);
            $facePath = 'storage/attendance_faces/' . $date . '/' . $fileName;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO attendance (employee_id, date, status, face_image_path)
        VALUES (:employee_id, :date, :status, :face_path)
        ON DUPLICATE KEY UPDATE
          status = VALUES(status),
          face_image_path = COALESCE(VALUES(face_image_path), face_image_path)
    ");
    $stmt->execute([
        ':employee_id' => $employeeId,
        ':date'        => $date,
        ':status'      => $status,
        ':face_path'   => $facePath,
    ]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

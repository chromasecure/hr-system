<?php
// TODO: copy bootstrap / DB / auth includes from existing web API

use App\Config\Database;
use PDO;

header('Content-Type: application/json; charset=utf-8');

try {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new Exception('Invalid JSON');
    }

    $date    = $data['date'] ?? date('Y-m-d');
    $records = $data['records'] ?? [];
    if (!is_array($records)) {
        throw new Exception('records must be an array');
    }

    $branchId = (int)($authUser['branch_id'] ?? 0);
    if (!$branchId) {
        throw new Exception('Missing branch id');
    }

    $pdo->beginTransaction();

    foreach ($records as $rec) {
        $code   = trim($rec['employee_code'] ?? '');
        $status = trim($rec['status'] ?? 'absent');
        $remark = trim($rec['remark'] ?? '');

        if ($code === '') {
            continue;
        }

        // get employee id
        $stmt = $pdo->prepare("
            SELECT id FROM employees
            WHERE emp_code = :code AND branch_id = :branch_id
            LIMIT 1
        ");
        $stmt->execute([':code' => $code, ':branch_id' => $branchId]);
        $emp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$emp) {
            continue;
        }

        $employeeId = (int)$emp['id'];

        $stmt = $pdo->prepare("
            INSERT INTO attendance (employee_id, date, status, remark)
            VALUES (:employee_id, :date, :status, :remark)
            ON DUPLICATE KEY UPDATE
              status = VALUES(status),
              remark = VALUES(remark)
        ");
        $stmt->execute([
            ':employee_id' => $employeeId,
            ':date'        => $date,
            ':status'      => $status,
            ':remark'      => $remark,
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

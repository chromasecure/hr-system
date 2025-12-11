<?php
// api/mobile/today_attendance.php

require __DIR__ . '/../../bootstrap.php'; // adjust if your bootstrap/config path is different

use App\Config\Database;
use PDO;

header('Content-Type: application/json; charset=utf-8');

try {
    $db  = new Database($config);
    $pdo = $db->getConnection();

    $branchId = (int)($_GET['branch_id'] ?? $_POST['branch_id'] ?? 0);
    $date     = $_GET['date'] ?? $_POST['date'] ?? date('Y-m-d');

    if (!$branchId) {
        throw new Exception('branch_id is required');
    }

    $sql = "
        SELECT e.id AS employee_id,
               e.emp_code,
               e.name,
               COALESCE(a.status, 'Absent') AS status,
               a.remarks
        FROM employees e
        LEFT JOIN attendance a
               ON a.employee_id = e.id
              AND a.date = :date
        WHERE e.branch_id = :branch_id
          AND e.is_active = 1
        ORDER BY e.emp_code
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':date'      => $date,
        ':branch_id' => $branchId,
    ]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'date'    => $date,
        'data'    => $rows,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}

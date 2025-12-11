<?php
// TODO: copy bootstrap / DB / auth includes from existing web API (login)

use App\Config\Database;
use PDO;

header('Content-Type: application/json; charset=utf-8');

try {
    $date = $_GET['date'] ?? date('Y-m-d');

    // Manager's branch – usually comes from auth session / token.
    $branchId = (int)($authUser['branch_id'] ?? 0);
    if (!$branchId) {
        throw new Exception('Missing branch id');
    }

    $sql = "
        SELECT e.emp_code AS code,
               e.name,
               COALESCE(a.status, 'absent') AS status,
               COALESCE(a.remark, '') AS remark
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

    echo json_encode(['employees' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

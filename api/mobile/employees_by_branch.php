<?php
// api/mobile/employees_by_branch.php

require __DIR__ . '/../../bootstrap.php';

use App\Config\Database;
use PDO;

header('Content-Type: application/json; charset=utf-8');

try {
    $db  = new Database($config);
    $pdo = $db->getConnection();

    $branchId = (int)($_GET['branch_id'] ?? $_POST['branch_id'] ?? 0);

    if (!$branchId) {
        throw new Exception('branch_id is required');
    }

    $sql = "
        SELECT id,
               emp_code,
               name,
               contact,
               designation_id,
               basic_salary
        FROM employees
        WHERE branch_id = :branch_id
          AND is_active = 1
        ORDER BY emp_code
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':branch_id' => $branchId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data'    => $rows,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}

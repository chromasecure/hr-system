<?php
// api/admin/approve_pending_employee.php

require __DIR__ . '/../../bootstrap.php';

use App\Config\Database;
use PDO;

header('Content-Type: application/json; charset=utf-8');

try {
    $db  = new Database($config);
    $pdo = $db->getConnection();

    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        throw new Exception('id is required');
    }

    $pdo->beginTransaction();

    // lock row
    $stmt = $pdo->prepare("SELECT * FROM pending_employees WHERE id = :id FOR UPDATE");
    $stmt->execute([':id' => $id]);
    $pending = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pending) {
        $pdo->rollBack();
        throw new Exception('Pending employee not found');
    }

    // insert into employees
    $insert = $pdo->prepare("
        INSERT INTO employees (
            branch_id,
            emp_code,
            name,
            contact,
            designation_id,
            basic_salary,
            commission_percent,
            joining_date,
            face_image_path,
            is_active
        ) VALUES (
            :branch_id,
            :emp_code,
            :name,
            :contact,
            :designation_id,
            :basic_salary,
            :commission_percent,
            :joining_date,
            :face_image_path,
            1
        )
    ");
    $insert->execute([
        ':branch_id'          => $pending['branch_id'],
        ':emp_code'           => $pending['emp_code'],
        ':name'               => $pending['name'],
        ':contact'            => $pending['contact'],
        ':designation_id'     => $pending['designation_id'],
        ':basic_salary'       => $pending['basic_salary'],
        ':commission_percent' => $pending['commission_percent'],
        ':joining_date'       => $pending['joining_date'],
        ':face_image_path'    => $pending['face_image_path'],
    ]);

    // delete from pending
    $del = $pdo->prepare("DELETE FROM pending_employees WHERE id = :id");
    $del->execute([':id' => $id]);

    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}

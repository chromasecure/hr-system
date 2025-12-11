<?php
// api/admin/get_pending_employees.php

require __DIR__ . '/../../bootstrap.php';

use App\Config\Database;
use PDO;

header('Content-Type: application/json; charset=utf-8');

$db  = new Database($config);
$pdo = $db->getConnection();

$sql = "
    SELECT id,
           branch_id,
           emp_code,
           name,
           contact,
           joining_date,
           face_image_path,
           created_at
    FROM pending_employees
    ORDER BY created_at DESC
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data'    => $rows,
]);

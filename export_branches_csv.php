<?php
require_once "config.php";
require_login();
if (($_SESSION['role'] ?? '') === 'branch') {
    die("Access denied");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=branches.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['id','name','manager_name','manager_contact','status']);

$res = $mysqli->query("SELECT * FROM branches ORDER BY name ASC");
while($row = $res->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['manager_name'],
        $row['manager_contact'],
        $row['status']
    ]);
}
fclose($output);
exit;

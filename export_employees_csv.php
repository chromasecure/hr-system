<?php
require_once "config.php";
require_login();
if (($_SESSION['role'] ?? '') === 'branch') {
    die("Access denied");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=employees.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['id','emp_code','name','branch_name','designation','contact','basic_salary','commission','joining_date','status','image_path']);

$sql = "SELECT e.*, b.name AS branch_name FROM employees e LEFT JOIN branches b ON b.id=e.branch_id ORDER BY e.id DESC";
$res = $mysqli->query($sql);
while($row = $res->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['emp_code'],
        $row['name'],
        $row['branch_name'],
        $row['designation'],
        $row['contact_number'],
        $row['basic_salary'],
        $row['commission'],
        $row['joining_date'],
        $row['status'],
        $row['image_path']
    ]);
}
fclose($output);
exit;

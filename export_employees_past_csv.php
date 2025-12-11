<?php
require_once "config.php";
require_login();
if (($_SESSION['role'] ?? '') === 'branch') {
    die("Access denied");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=past_employees.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['emp_code','name','branch_name','designation','basic_salary','joining_date','status']);

$sql = "SELECT e.*, b.name AS branch_name FROM employees e LEFT JOIN branches b ON b.id=e.branch_id WHERE e.status='inactive' ORDER BY e.id DESC";
$res = $mysqli->query($sql);
while($row = $res->fetch_assoc()) {
    fputcsv($output, [
        $row['emp_code'],
        $row['name'],
        $row['branch_name'],
        $row['designation'],
        $row['basic_salary'],
        $row['joining_date'],
        $row['status']
    ]);
}
fclose($output);
exit;

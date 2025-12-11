<?php
require_once "config.php";
require_login();

$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));

$start = "{$year}-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-01";
$end = date("Y-m-t", strtotime($start));

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=attendance_'.$month.'_'.$year.'.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['emp_code','date','status','remarks']);

$where = "a.date BETWEEN '{$start}' AND '{$end}'";
if ($_SESSION['role'] === 'branch') {
    $bid = intval($_SESSION['branch_id']);
    $where .= " AND e.branch_id = {$bid}";
}

$sql = "SELECT a.date, a.status, a.remarks, e.emp_code FROM attendance a JOIN employees e ON e.id=a.emp_id WHERE {$where} ORDER BY a.date ASC";
$res = $mysqli->query($sql);
while($row = $res->fetch_assoc()) {
    fputcsv($output, [
        $row['emp_code'],
        $row['date'],
        $row['status'],
        $row['remarks']
    ]);
}
fclose($output);
exit;

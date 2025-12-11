<?php
require_once "config.php";
require_login();
if (($_SESSION['role'] ?? '') !== 'admin') {
    die("Access denied");
}

$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=targets_'.$month.'_'.$year.'.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['branch','designation','sales_target','bonus_amount']);

$sql = "SELECT bt.*, b.name AS branch_name
        FROM branch_targets bt
        JOIN branches b ON b.id=bt.branch_id
        WHERE bt.month={$month} AND bt.year={$year}
        ORDER BY b.name, bt.designation, bt.sales_target";
$res = $mysqli->query($sql);
while($row = $res->fetch_assoc()) {
    fputcsv($output, [
        $row['branch_name'],
        $row['designation'],
        $row['sales_target'],
        $row['bonus_amount']
    ]);
}
fclose($output);
exit;

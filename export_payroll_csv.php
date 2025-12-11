<?php
require_once "config.php";
require_login();
if (($_SESSION['role'] ?? '') === 'branch') {
    die("Access denied");
}

$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));
$filter_branch_id = intval($_GET['branch_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=payroll_'.$month.'_'.$year.'.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['branch_name','emp_code','name','basic_salary','total_days','earned_days','sales','commission_percent','bonus','pay_status','gross_salary','commission_amount','net_salary']);

$where = "p.month={$month} AND p.year={$year}";
if ($_SESSION['role'] === 'branch') {
    $bid = intval($_SESSION['branch_id']);
    $where .= " AND e.branch_id = {$bid}";
} elseif ($filter_branch_id > 0) {
    $where .= " AND e.branch_id = {$filter_branch_id}";
}
if ($search !== '') {
    $s = $mysqli->real_escape_string($search);
    $where .= " AND (e.emp_code LIKE '%{$s}%' OR e.name LIKE '%{$s}%' OR b.name LIKE '%{$s}%')";
}

$sql = "SELECT p.*, e.emp_code, e.name, e.basic_salary, b.name AS branch_name
        FROM payroll p
        JOIN employees e ON e.id=p.emp_id
        LEFT JOIN branches b ON b.id=e.branch_id
        WHERE {$where}
        ORDER BY b.name, e.name";
$res = $mysqli->query($sql);
$totals = [
    'basic_salary' => 0,
    'total_days' => 0,
    'earned_days' => 0,
    'sales' => 0,
    'commission_percent' => 0,
    'bonus' => 0,
    'gross_salary' => 0,
    'commission_amount' => 0,
    'net_salary' => 0,
];
while($row = $res->fetch_assoc()) {
    $totals['basic_salary'] += (float)$row['basic_salary'];
    $totals['total_days'] += (float)$row['total_days'];
    $totals['earned_days'] += (float)$row['earned_days'];
    $totals['sales'] += (float)$row['sales'];
    $totals['commission_percent'] += (float)$row['commission_percent'];
    $totals['bonus'] += (float)$row['bonus'];
    $totals['gross_salary'] += (float)$row['gross_salary'];
    $totals['commission_amount'] += (float)$row['commission_amount'];
    $totals['net_salary'] += (float)$row['net_salary'];
    $pay_status = !empty($row['salary_received']) ? 'Received' : 'Not yet';

    fputcsv($output, [
        $row['branch_name'],
        $row['emp_code'],
        $row['name'],
        $row['basic_salary'],
        $row['total_days'],
        $row['earned_days'],
        $row['sales'],
        $row['commission_percent'],
        $row['bonus'],
        $pay_status,
        $row['gross_salary'],
        $row['commission_amount'],
        $row['net_salary']
    ]);
}

// totals row
fputcsv($output, [
    'TOTAL',
    '',
    '',
    number_format($totals['basic_salary'], 2, '.', ''),
    number_format($totals['total_days'], 2, '.', ''),
    number_format($totals['earned_days'], 2, '.', ''),
    number_format($totals['sales'], 2, '.', ''),
    number_format($totals['commission_percent'], 2, '.', ''),
    number_format($totals['bonus'], 2, '.', ''),
    '',
    number_format($totals['gross_salary'], 2, '.', ''),
    number_format($totals['commission_amount'], 2, '.', ''),
    number_format($totals['net_salary'], 2, '.', ''),
]);
fclose($output);
exit;

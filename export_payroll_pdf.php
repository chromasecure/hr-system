<?php
require_once "config.php";
require_login();
if (($_SESSION['role'] ?? '') === 'branch') {
    die("Access denied");
}

$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));

$where = "p.month={$month} AND p.year={$year}";
if ($_SESSION['role'] === 'branch') {
    $bid = intval($_SESSION['branch_id']);
    $where .= " AND e.branch_id = {$bid}";
}

$sql = "SELECT p.*, e.emp_code, e.name, e.basic_salary, b.name AS branch_name
        FROM payroll p
        JOIN employees e ON e.id=p.emp_id
        LEFT JOIN branches b ON b.id=e.branch_id
        WHERE {$where}
        ORDER BY b.name, e.name";
$res = $mysqli->query($sql);
$rows = [];
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
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
    $totals['basic_salary'] += (float)$r['basic_salary'];
    $totals['total_days'] += (float)$r['total_days'];
    $totals['earned_days'] += (float)$r['earned_days'];
    $totals['sales'] += (float)$r['sales'];
    $totals['commission_percent'] += (float)$r['commission_percent'];
    $totals['bonus'] += (float)$r['bonus'];
    $totals['gross_salary'] += (float)$r['gross_salary'];
    $totals['commission_amount'] += (float)$r['commission_amount'];
    $totals['net_salary'] += (float)$r['net_salary'];
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payroll Export - Essentia HR</title>
  <style>
    @page {
      size: A4 landscape;
      margin: 10mm;
    }
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      font-size: 11px;
      color: #0f172a;
    }
    h4 {
      margin-bottom: 12px;
    }
    .table {
      width: 100%;
      border-collapse: collapse;
    }
    .table thead th {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      padding: 4px 6px;
      font-weight: 600;
      text-align: left;
      white-space: nowrap;
    }
    .table tbody td {
      border: 1px solid #e5e7eb;
      padding: 4px 6px;
      white-space: nowrap;
    }
    .table tfoot td {
      border: 1px solid #e5e7eb;
      font-weight: 700;
      padding: 4px 6px;
      background: #f1f5f9;
    }
  </style>
</head>
<body>
<div class="container mt-4">
  <h4>Payroll (<?php echo $month . "/" . $year; ?>)</h4>
  <table class="table">
    <thead>
      <tr>
        <th>Branch</th>
        <th>Emp Code</th>
        <th>Name</th>
        <th>Basic</th>
        <th>Total Days</th>
        <th>Earned Days</th>
        <th>Sales</th>
        <th>Comm %</th>
        <th>Target Bonus</th>
        <th>Bonus</th>
        <th>Pay Status</th>
        <th>Bonus Status</th>
        <th>Gross</th>
        <th>Comm Amt</th>
        <th>Net Salary</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($rows as $row): ?>
      <?php
        $target_bonus = (float)($row['bonus'] ?? 0);
        $bonus_status = $target_bonus > 0 ? 'Achieved' : 'No target';
        $pay_status = !empty($row['salary_received']) ? 'Received' : 'Not yet';
      ?>
      <tr>
        <td><?php echo h($row['branch_name']); ?></td>
        <td><?php echo h($row['emp_code']); ?></td>
        <td><?php echo h($row['name']); ?></td>
        <td><?php echo number_format($row['basic_salary'], 2); ?></td>
        <td><?php echo h($row['total_days']); ?></td>
        <td><?php echo h($row['earned_days']); ?></td>
        <td><?php echo h($row['sales']); ?></td>
        <td><?php echo number_format($row['commission_percent'], 2); ?></td>
        <td><?php echo number_format($target_bonus, 2); ?></td>
        <td><?php echo number_format($target_bonus, 2); ?></td>
        <td><?php echo h($pay_status); ?></td>
        <td><?php echo h($bonus_status); ?></td>
        <td><?php echo number_format($row['gross_salary'], 2); ?></td>
        <td><?php echo number_format($row['commission_amount'], 2); ?></td>
        <td><?php echo number_format($row['net_salary'], 2); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3">Totals</td>
        <td><?php echo number_format($totals['basic_salary'], 2); ?></td>
        <td><?php echo number_format($totals['total_days'], 2); ?></td>
        <td><?php echo number_format($totals['earned_days'], 2); ?></td>
        <td><?php echo number_format($totals['sales'], 2); ?></td>
        <td><?php echo number_format($totals['commission_percent'], 2); ?></td>
        <td><?php echo number_format($totals['bonus'], 2); ?></td>
        <td><?php echo number_format($totals['bonus'], 2); ?></td>
        <td></td>
        <td></td>
        <td><?php echo number_format($totals['gross_salary'], 2); ?></td>
        <td><?php echo number_format($totals['commission_amount'], 2); ?></td>
        <td><?php echo number_format($totals['net_salary'], 2); ?></td>
      </tr>
    </tfoot>
  </table>
</div>
</body>
</html>

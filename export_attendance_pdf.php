<?php
require_once "config.php";
require_login();

$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));

$start = "{$year}-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-01";
$end = date("Y-m-t", strtotime($start));

$where = "a.date BETWEEN '{$start}' AND '{$end}'";
if ($_SESSION['role'] === 'branch') {
    $bid = intval($_SESSION['branch_id']);
    $where .= " AND e.branch_id = {$bid}";
}

$sql = "SELECT a.date, a.status, a.remarks, e.emp_code, e.name
        FROM attendance a JOIN employees e ON e.id=a.emp_id
        WHERE {$where}
        ORDER BY a.date ASC";
$res = $mysqli->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Attendance Export - Essentia HR</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container mt-4">
  <h4>Attendance (<?php echo $month . "/" . $year; ?>)</h4>
  <table class="table table-bordered table-sm">
    <thead>
      <tr>
        <th>Emp Code</th>
        <th>Name</th>
        <th>Date</th>
        <th>Status</th>
        <th>Remarks</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $res->fetch_assoc()): ?>
      <tr>
        <td><?php echo h($row['emp_code']); ?></td>
        <td><?php echo h($row['name']); ?></td>
        <td><?php echo h($row['date']); ?></td>
        <td><?php echo h($row['status']); ?></td>
        <td><?php echo h($row['remarks']); ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>


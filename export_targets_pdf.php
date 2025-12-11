<?php
require_once "config.php";
require_login();
if (($_SESSION['role'] ?? '') !== 'admin') {
    die("Access denied");
}

$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));

$sql = "SELECT bt.*, b.name AS branch_name
        FROM branch_targets bt
        JOIN branches b ON b.id=bt.branch_id
        WHERE bt.month={$month} AND bt.year={$year}
        ORDER BY b.name, bt.designation, bt.sales_target";
$res = $mysqli->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Targets Export - Essentia HR</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container mt-4">
  <h4>Sales Targets (<?php echo $month . "/" . $year; ?>)</h4>
  <table class="table table-bordered table-sm">
    <thead>
      <tr>
        <th>Branch</th>
        <th>Designation</th>
        <th>Sales Target</th>
        <th>Bonus Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $res->fetch_assoc()): ?>
      <tr>
        <td><?php echo h($row['branch_name']); ?></td>
        <td><?php echo h($row['designation']); ?></td>
        <td><?php echo number_format($row['sales_target'], 2); ?></td>
        <td><?php echo number_format($row['bonus_amount'], 2); ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>

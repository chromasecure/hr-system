<?php
require_once "config.php";
require_login();
if (($_SESSION['role'] ?? '') === 'branch') {
    die("Access denied");
}

$sql = "SELECT e.*, b.name AS branch_name FROM employees e LEFT JOIN branches b ON b.id=e.branch_id WHERE e.status='inactive' ORDER BY e.id DESC";
$res = $mysqli->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Past Employees Export - Essentia HR</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container mt-4">
  <h4>Past / Deleted Employees</h4>
  <table class="table table-bordered table-sm">
    <thead>
      <tr>
        <th>Emp Code</th>
        <th>Name</th>
        <th>Branch</th>
        <th>Designation</th>
        <th>Basic Salary</th>
        <th>Joining Date</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $res->fetch_assoc()): ?>
      <tr>
        <td><?php echo h($row['emp_code']); ?></td>
        <td><?php echo h($row['name']); ?></td>
        <td><?php echo h($row['branch_name']); ?></td>
        <td><?php echo h($row['designation']); ?></td>
        <td><?php echo number_format($row['basic_salary'], 2); ?></td>
        <td><?php echo h($row['joining_date']); ?></td>
        <td><?php echo h($row['status']); ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>

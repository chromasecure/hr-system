<?php
require_once "config.php";
require_login();
if (($_SESSION['role'] ?? '') === 'branch') {
    die("Access denied");
}

$res = $mysqli->query("SELECT * FROM branches ORDER BY name ASC");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Branches Export - Essentia HR</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container mt-4">
  <h4>Branches</h4>
  <table class="table table-bordered table-sm">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Manager Name</th>
        <th>Manager Contact</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $res->fetch_assoc()): ?>
      <tr>
        <td><?php echo h($row['id']); ?></td>
        <td><?php echo h($row['name']); ?></td>
        <td><?php echo h($row['manager_name']); ?></td>
        <td><?php echo h($row['manager_contact']); ?></td>
        <td><?php echo h($row['status']); ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body>
</html>

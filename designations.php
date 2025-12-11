<?php
require_once "config.php";
require_login();

if (($_SESSION['role'] ?? '') !== 'admin') {
    die("Access denied");
}

// handle add designation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && !isset($_POST['delete_id'])) {
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        $stmt = $mysqli->prepare("INSERT IGNORE INTO designations (name, status) VALUES (?, 'active')");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: designations.php");
    exit;
}

// handle delete designation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    if ($del_id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM designations WHERE id=?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: designations.php");
    exit;
}

$designations = $mysqli->query("SELECT * FROM designations ORDER BY name ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Designations - Essentia HR</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app-shell">
<?php include "navbar.php"; ?>
<main class="container app-main">
  <div class="page-title">
    <span class="page-title-icon">DG</span>
    <span class="page-title-text">Designations</span>
  </div>

  <div class="card mb-4">
    <div class="card-header">Add Designation</div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Name</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-2 align-self-end">
          <button class="btn btn-primary" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-bordered table-sm mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($d = $designations->fetch_assoc()): ?>
          <tr>
            <td><?php echo h($d['id']); ?></td>
            <td><?php echo h($d['name']); ?></td>
            <td><?php echo h($d['status']); ?></td>
            <td>
              <form method="post" class="d-inline" onsubmit="return confirm('Delete this designation?');">
                <input type="hidden" name="delete_id" value="<?php echo (int)$d['id']; ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


<?php
require_once "config.php";
require_login();
if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

// search
$search = trim($_GET['search'] ?? '');

// edit
$edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$edit_branch = null;
if ($edit_id > 0) {
    $stmt = $mysqli->prepare("SELECT * FROM branches WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_branch = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// handle add / update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name']);
    $manager_name = trim($_POST['manager_name'] ?? '');
    $manager_contact = trim($_POST['manager_contact'] ?? '');
    $status = $_POST['status'] ?? 'active';
    if ($name !== '') {
        if ($id > 0) {
            $stmt = $mysqli->prepare("UPDATE branches SET name=?, manager_name=?, manager_contact=?, status=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $manager_name, $manager_contact, $status, $id);
        } else {
            $stmt = $mysqli->prepare("INSERT INTO branches (name, manager_name, manager_contact, status) VALUES (?, ?, ?, 'active')");
            $stmt->bind_param("sss", $name, $manager_name, $manager_contact);
        }
        $stmt->execute();
        $stmt->close();
        header("Location: branches.php");
        exit;
    }
}
$where = "1=1";
if ($search !== '') {
    $s = $mysqli->real_escape_string($search);
    $where .= " AND (name LIKE '%{$s}%' OR manager_name LIKE '%{$s}%' OR manager_contact LIKE '%{$s}%')";
}
$branches = $mysqli->query("SELECT * FROM branches WHERE {$where} ORDER BY name ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Branches - Essentia HR</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app-shell">
<?php include "navbar.php"; ?>
<main class="container app-main">
  <div class="page-title">
    <span class="page-title-icon">BR</span>
    <span class="page-title-text">Branches</span>
  </div>
  <div class="d-flex justify-content-end mb-2">
    <a class="btn btn-sm btn-outline-primary me-2" href="export_branches_csv.php">Export CSV</a>
    <a class="btn btn-sm btn-outline-secondary" href="export_branches_pdf.php" target="_blank">Export PDF</a>
  </div>
  <form class="row g-3 mb-4 card p-3" method="post">
    <input type="hidden" name="id" value="<?php echo h($edit_branch['id'] ?? ''); ?>">
    <div class="col-auto">
      <input type="text" name="name" class="form-control" placeholder="Branch name" required value="<?php echo h($edit_branch['name'] ?? ''); ?>">
    </div>
    <div class="col-auto">
      <input type="text" name="manager_name" class="form-control" placeholder="Branch manager name" value="<?php echo h($edit_branch['manager_name'] ?? ''); ?>">
    </div>
    <div class="col-auto">
      <input type="text" name="manager_contact" class="form-control" placeholder="Branch manager contact" value="<?php echo h($edit_branch['manager_contact'] ?? ''); ?>">
    </div>
    <div class="col-auto">
      <select name="status" class="form-select">
        <option value="active" <?php echo (isset($edit_branch['status']) && $edit_branch['status'] === 'inactive') ? '' : 'selected'; ?>>Active</option>
        <option value="inactive" <?php echo (isset($edit_branch['status']) && $edit_branch['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
      </select>
    </div>
    <div class="col-auto">
      <button class="btn btn-primary" type="submit"><?php echo $edit_branch ? 'Update Branch' : 'Save Branch'; ?></button>
      <?php if ($edit_branch): ?>
        <a href="branches.php" class="btn btn-secondary ms-2">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
  <form class="row g-3 mb-3 filter-bar" method="get">
    <div class="col-auto">
      <input type="text" name="search" class="form-control" placeholder="Search branches" value="<?php echo h($search); ?>">
    </div>
    <div class="col-auto">
      <button class="btn btn-secondary" type="submit">Search</button>
      <?php if ($search !== ''): ?>
        <a class="btn btn-outline-secondary" href="branches.php">Clear</a>
      <?php endif; ?>
    </div>
  </form>
  <div class="card">
  <div class="table-responsive">
  <table class="table table-sm mb-0 list-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Branch Name</th>
        <th>Manager Name</th>
        <th>Manager Contact</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while($b = $branches->fetch_assoc()): ?>
      <tr>
        <td><?php echo h($b['id']); ?></td>
        <td><?php echo h($b['name']); ?></td>
        <td><?php echo h($b['manager_name']); ?></td>
        <td><?php echo h($b['manager_contact']); ?></td>
        <td><?php echo h($b['status']); ?></td>
        <td>
          <a class="btn btn-sm btn-outline-primary" href="branches.php?edit_id=<?php echo $b['id']; ?>">Edit</a>
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

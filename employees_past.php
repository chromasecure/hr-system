<?php
require_once "config.php";
require_login();
if (($_SESSION['role'] ?? '') === 'branch') {
    die("Access denied");
}

// load branches
$branches = [];
$res = $mysqli->query("SELECT id, name FROM branches ORDER BY name ASC");
while($row = $res->fetch_assoc()) { $branches[$row['id']] = $row['name']; }

$search = trim($_GET['search'] ?? '');
$filter_branch_id = intval($_GET['branch_id'] ?? 0);

$where = "status='inactive'";
if ($_SESSION['role'] === 'branch') {
    $bid = intval($_SESSION['branch_id']);
    $where .= " AND branch_id = {$bid}";
} elseif ($filter_branch_id > 0) {
    $where .= " AND branch_id = {$filter_branch_id}";
}
if ($search !== '') {
    $s = $mysqli->real_escape_string($search);
    $where .= " AND (emp_code LIKE '%{$s}%' OR name LIKE '%{$s}%' OR designation LIKE '%{$s}%')";
}

$employees = $mysqli->query("SELECT * FROM employees WHERE {$where} ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Past Employees - Essentia HR</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app-shell">
<?php include "navbar.php"; ?>
<main class="container app-main">
  <div class="page-title">
    <span class="page-title-icon">PE</span>
    <span class="page-title-text">Past / Deleted Employees</span>
  </div>

  <div class="d-flex justify-content-end mb-2">
    <a class="btn btn-sm btn-outline-primary me-2" href="export_employees_past_csv.php">Export CSV</a>
    <a class="btn btn-sm btn-outline-secondary" href="export_employees_past_pdf.php" target="_blank">Export PDF</a>
  </div>

  <form class="row g-3 mb-3 filter-bar" method="get">
    <div class="col-auto">
      <label class="form-label subtle-label">Branch</label>
      <select name="branch_id" class="form-select" onchange="this.form.submit()">
        <option value="">All branches</option>
        <?php foreach($branches as $id=>$nm): ?>
          <option value="<?php echo $id; ?>" <?php echo $filter_branch_id == $id ? 'selected' : ''; ?>><?php echo h($nm); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label subtle-label">Search</label>
      <input type="text" name="search" class="form-control" placeholder="Search by code, name, designation" value="<?php echo h($search); ?>">
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-secondary" type="submit">Apply</button>
      <?php if ($search !== '' || $filter_branch_id > 0): ?>
        <a class="btn btn-outline-secondary" href="employees_past.php">Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-sm mb-0 list-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Emp Code</th>
            <th>Name</th>
            <th>Branch</th>
            <th>Designation</th>
            <th>Contact</th>
            <th>Basic Salary</th>
            <th>Commission</th>
            <th>Joining Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php while($e = $employees->fetch_assoc()): ?>
          <tr>
            <td><?php echo h($e['id']); ?></td>
            <td><?php echo h($e['emp_code']); ?></td>
            <td><?php echo h($e['name']); ?></td>
            <td><?php echo h($branches[$e['branch_id']] ?? ''); ?></td>
            <td><?php echo h($e['designation']); ?></td>
            <td><?php echo h($e['contact_number']); ?></td>
            <td><?php echo number_format($e['basic_salary'], 2); ?></td>
            <td><?php echo number_format($e['commission'], 2); ?></td>
            <td><?php echo h($e['joining_date']); ?></td>
            <td><?php echo h($e['status']); ?></td>
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

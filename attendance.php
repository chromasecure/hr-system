<?php
require_once "config.php";
require_login();

// determine current role and optional branch filter for admin
$role = $_SESSION['role'] ?? '';
$branch_id_filter = 0;
if ($role === 'branch') {
    $branch_id_filter = intval($_SESSION['branch_id']);
} else {
    $branch_id_filter = intval($_GET['branch_id'] ?? 0);
}

// employees for dropdown (respect branch filter, only active)
$where = "e.status='active'";
// search (used only for listing employees below)
$search = trim($_GET['search'] ?? '');
if ($role === 'branch') {
    $where .= " AND e.branch_id = {$branch_id_filter}";
} elseif ($branch_id_filter > 0) {
    $where .= " AND e.branch_id = {$branch_id_filter}";
}

$emps_res = $mysqli->query("SELECT e.id, e.emp_code, e.name FROM employees e WHERE {$where} ORDER BY e.name ASC");
$employees = [];
while($row = $emps_res->fetch_assoc()) { $employees[$row['id']] = $row; }

// filtered employees for listing (do not affect dropdown)
$employees_filtered = $employees;
if ($search !== '') {
    $employees_filtered = [];
    $needle = strtolower($search);
    foreach ($employees as $id => $e) {
        $haystack = strtolower(($e['emp_code'] ?? '') . ' ' . ($e['name'] ?? ''));
        if (strpos($haystack, $needle) !== false) {
            $employees_filtered[$id] = $e;
        }
    }
}

// handle add attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['emp_id'])) {
    $emp_id = intval($_POST['emp_id']);
    $date = $_POST['date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'P';
    $remarks = $_POST['remarks'] ?? '';

    if ($emp_id > 0 && $date) {
        $stmt = $mysqli->prepare("REPLACE INTO attendance (emp_id, date, status, remarks) VALUES (?,?,?,?)");
        $stmt->bind_param("isss", $emp_id, $date, $status, $remarks);
        $stmt->execute();
        $stmt->close();
        $msg = "Attendance saved.";
    }
}

// date range
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-t');
$from_ts = strtotime($from_date);
$to_ts = strtotime($to_date);
if ($from_ts === false) {
    $from_ts = strtotime(date('Y-m-01'));
    $from_date = date('Y-m-01');
}
if ($to_ts === false || $to_ts < $from_ts) {
    $to_ts = $from_ts;
    $to_date = date('Y-m-d', $to_ts);
}

$start = date('Y-m-d', $from_ts);
$end = date('Y-m-d', $to_ts);

$att = [];
$att_where = "a.date BETWEEN '{$start}' AND '{$end}' AND e.status='active'";
if ($role === 'branch') {
    $att_where .= " AND e.branch_id = {$branch_id_filter}";
} elseif ($branch_id_filter > 0) {
    $att_where .= " AND e.branch_id = {$branch_id_filter}";
}
if ($search !== '') {
    $s = $mysqli->real_escape_string($search);
    $att_where .= " AND (e.emp_code LIKE '%{$s}%' OR e.name LIKE '%{$s}%')";
}
$q = $mysqli->query("SELECT a.*, e.emp_code, e.name FROM attendance a JOIN employees e ON e.id=a.emp_id WHERE {$att_where}");
while($row = $q->fetch_assoc()) {
    $att[$row['emp_id']][$row['date']] = $row;
}
?>
<?php
// preload branches for admin branch filter
$branches = [];
if ($role === 'admin') {
    $bres = $mysqli->query("SELECT id, name FROM branches ORDER BY name ASC");
    while ($bres && $row = $bres->fetch_assoc()) {
        $branches[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance - Essentia HR</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
      .att-cell { width: 28px; text-align:center; font-size: 0.75rem; }
    </style>
</head>
<body class="app-shell">
<?php include "navbar.php"; ?>
<main class="container app-main">
  <div class="page-title">
    <span class="page-title-icon">AT</span>
    <span class="page-title-text">Attendance</span>
  </div>

  <div class="d-flex justify-content-end mb-2">
    <a class="btn btn-sm btn-outline-primary me-2" href="export_attendance_csv.php">Export CSV</a>
    <a class="btn btn-sm btn-outline-secondary" href="export_attendance_pdf.php" target="_blank">Export PDF</a>
  </div>
  <?php if (!empty($msg)): ?><div class="alert alert-success"><?php echo h($msg); ?></div><?php endif; ?>

  <div class="card mb-4">
    <div class="card-header">Mark Attendance</div>
    <div class="card-body">
      <?php if ($role === 'admin'): ?>
      <form method="get" class="row g-3 mb-3">
        <div class="col-md-3">
          <label class="form-label">Branch</label>
          <select name="branch_id" class="form-select" onchange="this.form.submit()">
            <option value="">All branches</option>
            <?php foreach($branches as $b): ?>
              <option value="<?php echo (int)$b['id']; ?>" <?php echo $branch_id_filter === (int)$b['id'] ? 'selected' : ''; ?>>
                <?php echo h($b['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <input type="hidden" name="from_date" value="<?php echo h($from_date); ?>">
        <input type="hidden" name="to_date" value="<?php echo h($to_date); ?>">
        <input type="hidden" name="search" value="<?php echo h($search); ?>">
      </form>
      <?php endif; ?>
      <form method="post" class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Employee</label>
          <select name="emp_id" class="form-select" required>
            <option value="">Select</option>
            <?php foreach($employees as $id=>$e): ?>
              <option value="<?php echo $id; ?>"><?php echo h($e['emp_code'] . " - " . $e['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Date</label>
          <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="P">Present</option>
            <option value="A">Absent</option>
            <option value="L">Leave</option>
            <option value="H">Half Day</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Remarks</label>
          <input type="text" name="remarks" class="form-control">
        </div>
        <div class="col-md-2 align-self-end">
          <button class="btn btn-primary" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>

  <form class="row g-3 mb-3 filter-bar" method="get">
    <div class="col-auto">
      <label class="form-label subtle-label">From date</label>
      <input type="date" name="from_date" value="<?php echo h($from_date); ?>" class="form-control">
    </div>
    <div class="col-auto">
      <label class="form-label subtle-label">To date</label>
      <input type="date" name="to_date" value="<?php echo h($to_date); ?>" class="form-control">
    </div>
    <div class="col-auto">
      <label class="form-label subtle-label">Search</label>
      <input type="text" name="search" value="<?php echo h($search); ?>" class="form-control" placeholder="Emp code or name">
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-secondary" type="submit">Go</button>
    </div>
  </form>

  <div class="card">
    <div class="table-responsive">
    <table class="table table-sm mb-0 list-table">
      <thead>
        <tr>
          <th>Emp Code</th>
          <th>Name</th>
          <?php
          $dates = [];
          for ($ts = $from_ts; $ts <= $to_ts; $ts += 86400):
              $dates[] = date('Y-m-d', $ts);
          ?>
            <th class="att-cell"><?php echo date('d', $ts); ?></th>
          <?php endfor; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach($employees_filtered as $id=>$e): ?>
          <tr>
            <td><?php echo h($e['emp_code']); ?></td>
            <td><?php echo h($e['name']); ?></td>
            <?php foreach ($dates as $date):
                $status = $att[$id][$date]['status'] ?? '';
            ?>
              <td class="att-cell"><?php echo h($status); ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>

</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

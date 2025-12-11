<?php
require_once "config.php";
require_login();

$role = $_SESSION['role'] ?? '';
$branch_filter = 0;
if ($role === 'branch') {
    $branch_filter = intval($_SESSION['branch_id']);
}

$month = intval(date('n'));
$year = intval(date('Y'));
$today = date('Y-m-d');

// employees (active only)
$emp_sql = "SELECT COUNT(*) AS c FROM employees WHERE status='active'";
if ($branch_filter > 0) {
    $emp_sql .= " AND branch_id={$branch_filter}";
}
$emp_res = $mysqli->query($emp_sql);
$emp_count = $emp_res && ($row = $emp_res->fetch_assoc()) ? intval($row['c']) : 0;

// branches
$branch_count = 0;
if ($role === 'branch') {
    $branch_count = 1;
} else {
    $branch_res = $mysqli->query("SELECT COUNT(*) AS c FROM branches");
    $branch_count = $branch_res && ($row = $branch_res->fetch_assoc()) ? intval($row['c']) : 0;
}

// payroll summary for current month
$payroll_sql = "SELECT COUNT(*) AS c, COALESCE(SUM(net_salary),0) AS total_paid FROM payroll p JOIN employees e ON e.id=p.emp_id WHERE p.month={$month} AND p.year={$year}";
if ($branch_filter > 0) {
    $payroll_sql .= " AND e.branch_id={$branch_filter}";
}
$payroll_res = $mysqli->query($payroll_sql);
$payroll_count = 0;
$payroll_paid = 0;
if ($payroll_res && ($row = $payroll_res->fetch_assoc())) {
    $payroll_count = intval($row['c']);
    $payroll_paid = floatval($row['total_paid']);
}

// attendance today: present (P) and absent (A)
$att_base = "FROM attendance a JOIN employees e ON e.id=a.emp_id WHERE a.date='{$today}'";
if ($branch_filter > 0) {
    $att_base .= " AND e.branch_id={$branch_filter}";
}
$present = 0;
$absent = 0;
$att_res = $mysqli->query("SELECT SUM(CASE WHEN a.status='P' THEN 1 ELSE 0 END) AS present,
                                  SUM(CASE WHEN a.status='A' THEN 1 ELSE 0 END) AS absent
                           {$att_base}");
if ($att_res && ($row = $att_res->fetch_assoc())) {
    $present = intval($row['present']);
    $absent = intval($row['absent']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard - Essentia HR</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app-shell">
<?php include "navbar.php"; ?>
<main class="container app-main">
  <div class="page-title">
    <span class="page-title-icon">DB</span>
    <span class="page-title-text">Dashboard</span>
  </div>
  <div class="row g-4">
    <div class="col-md-3">
      <div class="card mb-3">
        <div class="card-body">
          <div class="subtle-label mb-1">Employees</div>
          <div class="display-6"><?php echo number_format($emp_count); ?></div>
          <div class="text-muted small">Active<?php echo $branch_filter ? ' in branch' : ''; ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card mb-3">
        <div class="card-body">
          <div class="subtle-label mb-1">Branches</div>
          <div class="display-6"><?php echo number_format($branch_count); ?></div>
          <div class="text-muted small">Total branches</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card mb-3">
        <div class="card-body">
          <div class="subtle-label mb-1">Payroll (<?php echo date('M Y'); ?>)</div>
          <div class="display-6"><?php echo number_format($payroll_count); ?></div>
          <div class="text-muted small">Employees processed</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card mb-3">
        <div class="card-body">
          <div class="subtle-label mb-1">Paid this month</div>
          <div class="display-6"><?php echo number_format($payroll_paid, 2); ?></div>
          <div class="text-muted small">Net salaries</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="subtle-label mb-1">Today</div>
            <div class="h5 mb-0">Present: <?php echo number_format($present); ?></div>
            <div class="h6 text-muted mb-0">Absent: <?php echo number_format($absent); ?></div>
          </div>
          <div class="text-muted small"><?php echo h($today); ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-body">
          <div class="subtle-label mb-2">Quick exports</div>
          <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary btn-sm" href="export_payroll_csv.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>">Payroll CSV</a>
            <a class="btn btn-outline-secondary btn-sm" href="export_payroll_pdf.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>" target="_blank">Payroll PDF</a>
            <a class="btn btn-outline-primary btn-sm" href="export_attendance_csv.php">Attendance CSV</a>
            <a class="btn btn-outline-primary btn-sm" href="export_employees_csv.php">Employees CSV</a>
            <a class="btn btn-outline-secondary btn-sm" href="export_targets_pdf.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>" target="_blank">Targets PDF</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

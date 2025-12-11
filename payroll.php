<?php
require_once "config.php";
require_login();
if (($_SESSION['role'] ?? '') === 'branch') {
    die("Access denied");
}

// determine payroll period (month/year) from dropdowns
// build list of months that have payroll entries
$available_periods = [];
$resPeriods = $mysqli->query("SELECT DISTINCT year, month FROM payroll ORDER BY year DESC, month DESC");
while ($resPeriods && $row = $resPeriods->fetch_assoc()) {
    $y = intval($row['year']);
    $m = intval($row['month']);
    $available_periods[] = [
        'year' => $y,
        'month' => $m,
        'label' => date('F Y', strtotime(sprintf('%04d-%02d-01', $y, $m))),
    ];
}

$month = intval($_GET['month'] ?? 0);
$year = intval($_GET['year'] ?? 0);

// ensure status columns exist for payment tracking
@$mysqli->query("ALTER TABLE payroll ADD COLUMN IF NOT EXISTS paid TINYINT(1) NOT NULL DEFAULT 0");
@$mysqli->query("ALTER TABLE payroll ADD COLUMN IF NOT EXISTS salary_released TINYINT(1) NOT NULL DEFAULT 0");
@$mysqli->query("ALTER TABLE payroll ADD COLUMN IF NOT EXISTS salary_received TINYINT(1) NOT NULL DEFAULT 0");

// if no valid month/year selected, default to latest available or current month
if ($month <= 0 || $year <= 0) {
    if (!empty($available_periods)) {
        $year = $available_periods[0]['year'];
        $month = $available_periods[0]['month'];
    } else {
        $year = intval(date('Y'));
        $month = intval(date('n'));
    }
}

// period boundaries for filtering by joining date
$period_start = sprintf('%04d-%02d-01', $year, $month);
$period_end = date('Y-m-t', strtotime($period_start));

// filters
$search = trim($_GET['search'] ?? '');
$filter_branch_id = intval($_GET['branch_id'] ?? 0);

// bulk mark received / not yet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status_action']) && !empty($_POST['pay_ids'])) {
    $ids = array_filter(array_map('intval', $_POST['pay_ids']));
    if (!empty($ids)) {
        $idList = implode(',', $ids);
        if ($_POST['status_action'] === 'received') {
            $mysqli->query("UPDATE payroll SET salary_received=1 WHERE emp_id IN ({$idList}) AND month={$month} AND year={$year}");
            $msg = "Marked selected employees as received.";
        } elseif ($_POST['status_action'] === 'notyet') {
            $mysqli->query("UPDATE payroll SET salary_received=0 WHERE emp_id IN ({$idList}) AND month={$month} AND year={$year}");
            $msg = "Reset status to not yet for selected employees.";
        }
    }
}

// handle manual payroll entry/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['emp_id'])) {
    $emp_id = intval($_POST['emp_id']);
    $total_days = intval($_POST['total_days'] ?? 0);
    $earned_days = intval($_POST['earned_days'] ?? 0);
    $sales = floatval($_POST['sales'] ?? 0);
    $bonus_manual_raw = $_POST['bonus'] ?? null;

    // get basic salary, commission, designation and hold info from employee master
    $basic = 0;
    $commission_percent = 0;
    $hold_salary = 0;
    $hold_balance = 0;
    $designation = '';
    $emp_branch_id = 0;
    $res = $mysqli->query("SELECT basic_salary, commission, hold_salary, hold_balance, designation, branch_id FROM employees WHERE id={$emp_id}");
    if ($res && $row = $res->fetch_assoc()) {
        $basic = floatval($row['basic_salary']);
        $commission_percent = floatval($row['commission']);
        $hold_salary = intval($row['hold_salary']);
        $hold_balance = floatval($row['hold_balance']);
        $designation = (string)$row['designation'];
        $emp_branch_id = intval($row['branch_id']);
    }

    // Total branch sale is defined as Branch Manager's sale for this branch/month/year.
    // Do not sum employee sales here. For the Branch Manager row we use the
    // entered sales value; for other employees we read the existing Branch
    // Manager payroll row (if any) to obtain branch_sale.
    $branch_sale = 0;
    if ($emp_branch_id > 0) {
        if ($designation === 'Branch Manager') {
            $branch_sale = $sales;
        } else {
            $bmRes = $mysqli->query("SELECT p.sales AS bm_sales
                                     FROM payroll p
                                     JOIN employees e ON e.id=p.emp_id
                                     WHERE p.month={$month} AND p.year={$year}
                                       AND e.branch_id={$emp_branch_id}
                                       AND e.designation='Branch Manager'
                                     LIMIT 1");
            if ($bmRes && $bmRow = $bmRes->fetch_assoc()) {
                $branch_sale = floatval($bmRow['bm_sales']);
            }
        }
    }

    // for Branch Manager, treat sales as total branch sales for calculations
    $sales_for_calc = $sales;
    if ($designation === 'Branch Manager') {
        $sales_for_calc = $sales;
    }

    if ($total_days > 0) {
        $gross = ($basic / $total_days) * $earned_days;
    } else {
        $gross = $basic;
    }
    $commission_amount = ($sales_for_calc * $commission_percent) / 100.0;

    // bonus from branch-level monthly targets (two-level, branch-based)
    // - Branch sale is the Branch Manager's sale (see $branch_sale).
    // - Determine which branch target level is met for this branch/month/year:
    //      level 2 if branch_sale >= Target2
    //      level 1 if branch_sale >= Target1
    //      else 0 (no target met)
    // - For this employee's designation, apply the corresponding bonus amount
    //   for the achieved level, if a row exists.
    $bonus_target = 0;
    if ($branch_sale > 0 && $emp_branch_id > 0 && $designation !== '') {
        // determine achieved branch target level based on Branch Manager sale
        $achieved_target = null;
        $tstmt = $mysqli->prepare("SELECT MAX(sales_target) AS max_target
                                   FROM branch_targets
                                   WHERE branch_id=? AND month=? AND year=? AND sales_target <= ?");
        $tstmt->bind_param("iiid", $emp_branch_id, $month, $year, $branch_sale);
        $tstmt->execute();
        $tstmt->bind_result($max_target);
        if ($tstmt->fetch() && $max_target !== null) {
            $achieved_target = floatval($max_target);
        }
        $tstmt->close();

        // apply bonus for this designation at the achieved target level
        if ($achieved_target !== null && $achieved_target > 0) {
            $stmt = $mysqli->prepare("SELECT bonus_amount
                                      FROM branch_targets
                                      WHERE branch_id=? AND designation=? AND month=? AND year=? AND sales_target=?
                                      LIMIT 1");
            $stmt->bind_param("isidd", $emp_branch_id, $designation, $month, $year, $achieved_target);
            $stmt->execute();
            $stmt->bind_result($bonus_amount);
            if ($stmt->fetch()) {
                $bonus_target = floatval($bonus_amount);
            }
            $stmt->close();
        }
    }

    // if user entered a manual bonus value, use it; otherwise use
    // the auto-calculated target bonus
    if ($bonus_manual_raw !== null && $bonus_manual_raw !== '') {
        $bonus = floatval($bonus_manual_raw);
    } else {
        $bonus = $bonus_target;
    }
    $net_salary = $gross + $commission_amount + $bonus;

    // apply hold salary logic
    if ($hold_salary === 1) {
        $hold_balance += $net_salary;
        $net_to_store = 0;
    } else {
        $net_to_store = $net_salary + $hold_balance;
        $hold_balance = 0;
    }

    $stmt = $mysqli->prepare("REPLACE INTO payroll (emp_id, month, year, total_days, earned_days, sales, commission_percent, bonus, gross_salary, commission_amount, net_salary) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("iiiiididddd", $emp_id, $month, $year, $total_days, $earned_days, $sales_for_calc, $commission_percent, $bonus, $gross, $commission_amount, $net_to_store);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("UPDATE employees SET hold_balance=? WHERE id=?");
    $stmt->bind_param("di", $hold_balance, $emp_id);
    $stmt->execute();
    $stmt->close();
    $msg = "Payroll saved for employee.";
}

// preload branches list
$branch_list = [];
$bres = $mysqli->query("SELECT id, name FROM branches ORDER BY name ASC");
while ($bres && $row = $bres->fetch_assoc()) {
    $branch_list[] = $row;
}

// get employees (optionally branch-filter, and not before joining date)
$where = "1=1";
if ($_SESSION['role'] === 'branch') {
    $bid = intval($_SESSION['branch_id']);
    $where .= " AND e.branch_id = {$bid}";
} elseif ($filter_branch_id > 0) {
    $where .= " AND e.branch_id = {$filter_branch_id}";
}
// only show employees whose joining_date is on or before the end of the selected month,
// or where joining_date is not set / legacy zero date.
$where .= " AND (e.joining_date IS NULL OR e.joining_date = '0000-00-00' OR e.joining_date <= '{$period_end}')";
if ($search !== '') {
    $s = $mysqli->real_escape_string($search);
    $where .= " AND (e.emp_code LIKE '%{$s}%' OR e.name LIKE '%{$s}%' OR b.name LIKE '%{$s}%')";
}
$emps = $mysqli->query("SELECT e.id, e.emp_code, e.name, e.basic_salary, e.branch_id, e.commission AS emp_commission, e.designation, b.name AS branch_name
                        FROM employees e
                        LEFT JOIN branches b ON b.id=e.branch_id
                        WHERE {$where}
                        ORDER BY b.name, e.name");

$payroll = [];
$q = $mysqli->query("SELECT * FROM payroll WHERE month={$month} AND year={$year}");
while($row = $q->fetch_assoc()) {
    $payroll[$row['emp_id']] = $row;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll - Essentia HR</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app-shell">
<?php include "navbar.php"; ?>
<main class="container app-main">
  <div class="page-header mb-3">
    <div class="page-title mb-0">
      <span class="page-title-icon">PR</span>
      <span class="page-title-text">Payroll (<?php echo "{$month}/{$year}"; ?>)</span>
    </div>
    <div class="page-actions">
      <a class="btn btn-sm btn-outline-primary" href="export_payroll_csv.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>">Export CSV</a>
      <a class="btn btn-sm btn-outline-secondary" href="export_payroll_pdf.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>" target="_blank">Export PDF</a>
    </div>
  </div>
  <?php if (!empty($msg)): ?><div class="alert alert-success"><?php echo h($msg); ?></div><?php endif; ?>

  <form class="row g-3 align-items-end mb-3 filter-bar" method="get">
    <div class="col-md-3">
      <label class="form-label subtle-label">Month</label>
      <select name="month" class="form-select">
        <?php for ($m = 1; $m <= 12; $m++): ?>
          <option value="<?php echo $m; ?>" <?php echo $month === $m ? 'selected' : ''; ?>>
            <?php echo h(date('F', strtotime(sprintf('2024-%02d-01', $m)))); ?>
          </option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label subtle-label">Year</label>
      <input type="number" name="year" class="form-control" value="<?php echo $year; ?>" min="2000" max="2100">
    </div>
    <div class="col-md-3">
      <label class="form-label subtle-label">Branch</label>
      <select name="branch_id" class="form-select">
        <option value="">All branches</option>
        <?php foreach($branch_list as $b): ?>
          <option value="<?php echo (int)$b['id']; ?>" <?php echo $filter_branch_id === (int)$b['id'] ? 'selected' : ''; ?>>
            <?php echo h($b['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label subtle-label">Search</label>
      <input type="text" name="search" value="<?php echo h($search); ?>" class="form-control" placeholder="Branch, code or name">
    </div>
    <div class="col-md-2">
      <button class="btn btn-secondary" type="submit">Go</button>
    </div>
  </form>

  <div class="card mb-3">
    <div class="card-header">Import Payroll CSV</div>
    <div class="card-body">
      <form class="row g-3 align-items-end" method="post" action="import_sales_csv.php" enctype="multipart/form-data">
        <div class="col-md-4">
          <label class="form-label">Sales CSV File</label>
          <input type="file" name="csv_file" class="form-control" accept=".csv" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Month</label>
          <input type="number" name="month" min="1" max="12" value="<?php echo $month; ?>" class="form-control" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Year</label>
          <input type="number" name="year" value="<?php echo $year; ?>" class="form-control" required>
        </div>
        <div class="col-md-3">
          <button class="btn btn-primary" type="submit">Import Sales</button>
        </div>
        <div class="col-md-5">
          <a class="btn btn-outline-secondary mb-2" href="export_employees_csv.php">Download Employee List CSV</a>
          <div><small>Columns in CSV: emp_code, sales, total_days, earned_days</small></div>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
    <form method="post">
    <input type="hidden" name="month" value="<?php echo $month; ?>">
    <input type="hidden" name="year" value="<?php echo $year; ?>">
    <input type="hidden" name="branch_id" value="<?php echo $filter_branch_id; ?>">
    <input type="hidden" name="search" value="<?php echo h($search); ?>">
    <div class="d-flex align-items-center gap-2 p-3">
      <select name="status_action" class="form-select form-select-sm" style="max-width: 200px;">
        <option value="received">Mark as Received</option>
        <option value="notyet">Not yet</option>
      </select>
      <button class="btn btn-primary btn-sm" type="submit">Apply</button>
    </div>
    <table class="table table-sm align-middle mb-0 list-table" id="payrollTable">
      <thead>
        <tr>
          <th><input type="checkbox" id="selectAllPayroll"></th>
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
          <th>Edit</th>
        </tr>
      </thead>
      <tbody>
        <?php while($e = $emps->fetch_assoc()):
            $p = $payroll[$e['id']] ?? null;
            $target_bonus = isset($p['bonus']) ? floatval($p['bonus']) : 0.0;
            $bonus_status = $target_bonus > 0 ? 'Achieved' : 'No target';
            $pay_status_label = 'Not yet';
            $pay_status_class = 'text-muted';
            if (!empty($p['salary_received'])) {
                $pay_status_label = 'Received';
                $pay_status_class = 'text-success fw-semibold';
            }
        ?>
        <tr>
            <td><input type="checkbox" name="pay_ids[]" value="<?php echo (int)$e['id']; ?>" class="pay-select"></td>
            <td>
              <?php if (!empty($e['branch_name'])): ?>
                <a href="payroll.php?branch_id=<?php echo intval($e['branch_id'] ?? 0); ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>">
                  <?php echo h($e['branch_name']); ?>
                </a>
              <?php else: ?>
                <?php echo h($e['branch_name']); ?>
              <?php endif; ?>
            </td>
            <td><?php echo h($e['emp_code']); ?></td>
            <td><?php echo h($e['name']); ?></td>
            <td><?php echo number_format($e['basic_salary'], 2); ?></td>
            <td><?php echo h($p['total_days'] ?? 30); ?></td>
            <td><?php echo h($p['earned_days'] ?? 0); ?></td>
            <td><?php echo h($p['sales'] ?? 0); ?></td>
            <td><?php echo number_format($e['emp_commission'] ?? 0, 2); ?></td>
            <td><?php echo number_format($target_bonus, 2); ?></td>
            <td><?php echo number_format($target_bonus, 2); ?></td>
            <td><span class="<?php echo $pay_status_class; ?>"><?php echo h($pay_status_label); ?></span></td>
            <td><?php echo h($bonus_status); ?></td>
            <td><?php echo isset($p) ? number_format($p['gross_salary'], 2) : ''; ?></td>
            <td><?php echo isset($p) ? number_format($p['commission_amount'], 2) : ''; ?></td>
            <td><?php echo isset($p) ? number_format($p['net_salary'], 2) : ''; ?></td>
            <td>
              <button type="button"
                      class="btn btn-sm btn-outline-primary payroll-edit-btn"
                      data-bs-toggle="offcanvas"
                      data-bs-target="#payrollEditSidebar"
                      data-emp-id="<?php echo $e['id']; ?>"
                      data-emp-name="<?php echo h($e['name']); ?>"
                      data-branch-name="<?php echo h($e['branch_name']); ?>"
                      data-total-days="<?php echo h($p['total_days'] ?? 30); ?>"
                      data-earned-days="<?php echo h($p['earned_days'] ?? 0); ?>"
                      data-sales="<?php echo h($p['sales'] ?? 0); ?>"
                      data-bonus="<?php echo h($p['bonus'] ?? 0); ?>">
                Edit
              </button>
            </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
      <tfoot>
        <tr id="payrollSummary">
          <td></td>
          <td></td>
          <td></td>
          <td class="fw-bold">Totals</td>
          <td data-col="basic"></td>
          <td data-col="total_days"></td>
          <td data-col="earned_days"></td>
          <td data-col="sales"></td>
          <td data-col="comm_percent"></td>
          <td data-col="target_bonus"></td>
          <td data-col="bonus"></td>
          <td></td>
          <td></td>
          <td data-col="gross"></td>
          <td data-col="comm_amt"></td>
          <td data-col="net_salary"></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
    </form>
    </div>
  </div>

  <div class="offcanvas offcanvas-end" tabindex="-1" id="payrollEditSidebar">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="payrollEditLabel">Edit Payroll</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <form method="post" id="payrollEditForm" class="row g-3">
        <input type="hidden" name="emp_id" id="pe_emp_id">
        <div class="col-12">
          <div class="subtle-label" id="pe_emp_info"></div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Total Days</label>
          <input type="number" name="total_days" id="pe_total_days" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Earned Days</label>
          <input type="number" name="earned_days" id="pe_earned_days" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Sales</label>
          <input type="number" step="0.01" name="sales" id="pe_sales" class="form-control">
        </div>
        <div class="col-md-12">
          <label class="form-label">Bonus (auto from targets, editable)</label>
          <input type="number" step="0.01" name="bonus" id="pe_bonus" class="form-control">
        </div>
        <div class="col-12">
          <button class="btn btn-primary" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>

</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click', function (e) {
  var btn = e.target.closest('.payroll-edit-btn');
  if (!btn) return;
  var empId = btn.getAttribute('data-emp-id');
  var empName = btn.getAttribute('data-emp-name') || '';
  var branchName = btn.getAttribute('data-branch-name') || '';
  document.getElementById('pe_emp_id').value = empId;
  document.getElementById('pe_total_days').value = btn.getAttribute('data-total-days') || '';
  document.getElementById('pe_earned_days').value = btn.getAttribute('data-earned-days') || '';
  document.getElementById('pe_sales').value = btn.getAttribute('data-sales') || '';
  document.getElementById('pe_bonus').value = btn.getAttribute('data-bonus') || '';
  document.getElementById('pe_emp_info').textContent = branchName + ' - ' + empName;
});

function formatNumber(val) {
  if (!isFinite(val)) return '0.00';
  return val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updatePayrollSummary() {
  var table = document.getElementById('payrollTable');
  if (!table || !table.tBodies.length) return;
  var tbody = table.tBodies[0];
  var rows = Array.prototype.slice.call(tbody.rows);
  if (rows.length === 0) return;

  var cols = {
    basic: 4,
    total_days: 5,
    earned_days: 6,
    sales: 7,
    comm_percent: 8,
    target_bonus: 9,
    bonus: 10,
    gross: 13,
    comm_amt: 14,
    net_salary: 15
  };

  var totals = {};
  Object.keys(cols).forEach(function (k) { totals[k] = 0; });

  rows.forEach(function (row) {
    Object.keys(cols).forEach(function (key) {
      var idx = cols[key];
      var cell = row.cells[idx];
      if (!cell) return;
      var text = (cell.textContent || '').replace(/,/g, '').trim();
      var val = parseFloat(text);
      if (!isNaN(val)) {
        totals[key] += val;
      }
    });
  });

  Object.keys(cols).forEach(function (key) {
    var cell = document.querySelector('#payrollSummary td[data-col="' + key + '"]');
    if (!cell) return;
    var total = totals[key];
    cell.textContent = 'Total: ' + formatNumber(total);
  });
}

document.addEventListener('DOMContentLoaded', updatePayrollSummary);

document.addEventListener('DOMContentLoaded', function () {
  var selectAll = document.getElementById('selectAllPayroll');
  if (!selectAll) return;
  selectAll.addEventListener('change', function () {
    document.querySelectorAll('.pay-select').forEach(function (cb) {
      cb.checked = selectAll.checked;
    });
  });
});

</script>
</body>
</html>

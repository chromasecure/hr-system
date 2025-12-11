<?php
require_once "config.php";
require_login();

// load branches
$branches = [];
$res = $mysqli->query("SELECT id, name FROM branches ORDER BY name ASC");
while($row = $res->fetch_assoc()) { $branches[$row['id']] = $row['name']; }

// load designations
$designation_options = [];
$dres = $mysqli->query("SELECT name FROM designations WHERE status='active' ORDER BY name ASC");
if ($dres) {
    while($row = $dres->fetch_assoc()) { $designation_options[] = $row['name']; }
}

// search
$search = trim($_GET['search'] ?? '');
$filter_branch_id = intval($_GET['branch_id'] ?? 0);
$filter_status = trim($_GET['status'] ?? '');

// edit state handled on client via sidebar
$edit_employee = null;

// handle add / update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['emp_code'])) {
    $id = intval($_POST['id'] ?? 0);
    $emp_code = trim($_POST['emp_code']);
    $name = trim($_POST['name']);
    $branch_id = intval($_POST['branch_id'] ?? 0);
    $designation = trim($_POST['designation']);
    $contact_number = trim($_POST['contact_number'] ?? '');
    $basic_salary = floatval($_POST['basic_salary'] ?? 0);
    $commission = floatval($_POST['commission'] ?? 0);
    $joining_date = $_POST['joining_date'] ?? null;
    $status = $_POST['status'] ?? 'active';
    $hold_salary = isset($_POST['hold_salary']) ? intval($_POST['hold_salary']) : 0;
    $left_date = ($status === 'inactive') ? date('Y-m-d') : null;

    // image upload
    $image_path = null;
    if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];
        if (in_array($ext, $allowed, true)) {
            $new_name = 'emp_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
            $dest = __DIR__ . '/uploads/' . $new_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $image_path = 'uploads/' . $new_name;
            }
        }
    }

    if ($emp_code !== '' && $name !== '' && $branch_id > 0) {
        if ($id > 0) {
            if ($image_path !== null) {
                $stmt = $mysqli->prepare("UPDATE employees SET emp_code=?, name=?, branch_id=?, designation=?, contact_number=?, basic_salary=?, commission=?, joining_date=?, status=?, hold_salary=?, image_path=?, left_date=? WHERE id=?");
                $stmt->bind_param("ssissddssissi", $emp_code, $name, $branch_id, $designation, $contact_number, $basic_salary, $commission, $joining_date, $status, $hold_salary, $image_path, $left_date, $id);
            } else {
                $stmt = $mysqli->prepare("UPDATE employees SET emp_code=?, name=?, branch_id=?, designation=?, contact_number=?, basic_salary=?, commission=?, joining_date=?, status=?, hold_salary=?, left_date=? WHERE id=?");
                $stmt->bind_param("ssissddsssii", $emp_code, $name, $branch_id, $designation, $contact_number, $basic_salary, $commission, $joining_date, $status, $hold_salary, $left_date, $id);
            }
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $mysqli->prepare("INSERT INTO employees (emp_code, name, branch_id, designation, contact_number, basic_salary, commission, joining_date, status, hold_salary, image_path, left_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssissddssisss", $emp_code, $name, $branch_id, $designation, $contact_number, $basic_salary, $commission, $joining_date, $status, $hold_salary, $image_path, $left_date);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: employees.php");
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    if ($del_id > 0) {
        // capture previous status for undo
        $prev = null;
        $resPrev = $mysqli->prepare("SELECT status FROM employees WHERE id=?");
        $resPrev->bind_param("i", $del_id);
        $resPrev->execute();
        $resPrev->bind_result($prev_status);
        if ($resPrev->fetch()) {
            $prev = $prev_status;
        }
        $resPrev->close();

        $stmt = $mysqli->prepare("UPDATE employees SET status='inactive', left_date=CURDATE() WHERE id=?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $stmt->close();

        if ($prev !== null && $prev !== 'inactive') {
            log_activity(
                $mysqli,
                'delete_employee',
                "Deleted employee ID {$del_id}",
                ['employee_id' => $del_id, 'previous_status' => $prev],
                true
            );
        }
    }
    header("Location: employees.php");
    exit;
}

$where = "1=1";
if ($_SESSION['role'] === 'branch') {
    $bid = intval($_SESSION['branch_id']);
    $where .= " AND branch_id = {$bid}";
} elseif ($filter_branch_id > 0) {
    $where .= " AND branch_id = {$filter_branch_id}";
}

$searchSql = '';
if ($search !== '') {
    $s = $mysqli->real_escape_string($search);
    $where .= " AND (emp_code LIKE '%{$s}%' OR name LIKE '%{$s}%' OR designation LIKE '%{$s}%')";
}
if ($filter_status === 'active') {
    $where .= " AND status='active'";
} elseif ($filter_status === 'inactive') {
    $where .= " AND status='inactive'";
}

$employees = $mysqli->query("SELECT * FROM employees WHERE {$where} ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employees - Essentia HR</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app-shell">
<?php include "navbar.php"; ?>
<main class="container app-main">
  <div class="page-title">
    <span class="page-title-icon">EM</span>
    <span class="page-title-text">Employees</span>
  </div>

  <div class="d-flex justify-content-end mb-2">
    <a class="btn btn-sm btn-outline-primary me-2" href="export_employees_csv.php">Export CSV</a>
    <a class="btn btn-sm btn-outline-secondary" href="export_employees_pdf.php" target="_blank">Export PDF</a>
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
    <div class="col-auto">
      <label class="form-label subtle-label">Status</label>
      <select name="status" class="form-select" onchange="this.form.submit()">
        <option value="">All</option>
        <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
        <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
      </select>
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-secondary" type="submit">Apply</button>
      <?php if ($search !== '' || $filter_branch_id > 0 || $filter_status !== ''): ?>
        <a class="btn btn-outline-secondary" href="employees.php">Clear</a>
      <?php endif; ?>
    </div>
  </form>

  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="subtle-label">Employees</div>
    <button type="button"
            class="btn btn-sm btn-primary employee-add-btn"
            data-bs-toggle="offcanvas"
            data-bs-target="#employeeSidebar">
      Add new employee
    </button>
  </div>

  <div class="card mb-3">
    <div class="card-header">Import Employees (CSV)</div>
    <div class="card-body">
      <form method="post" action="import_employees_csv.php" enctype="multipart/form-data" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Employees CSV File</label>
          <input type="file" name="csv_file" class="form-control" accept=".csv" required>
        </div>
        <div class="col-md-3">
          <button class="btn btn-primary" type="submit">Import</button>
        </div>
        <div class="col-md-5">
          <small>Columns: emp_code, name, branch_name, designation, basic_salary, joining_date (YYYY-MM-DD). You can export from Excel as CSV.</small>
        </div>
      </form>
    </div>
  </div>

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
        <th>Image</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while($e = $employees->fetch_assoc()): ?>
      <tr>
        <td><?php echo h($e['id']); ?></td>
        <td><?php echo h($e['emp_code']); ?></td>
        <td><?php echo h($e['name']); ?></td>
        <td>
          <?php if (!empty($branches[$e['branch_id']] ?? '')): ?>
            <a href="employees.php?branch_id=<?php echo (int)$e['branch_id']; ?>" class="text-decoration-none">
              <?php echo h($branches[$e['branch_id']] ?? ''); ?>
            </a>
          <?php endif; ?>
        </td>
        <td><?php echo h($e['designation']); ?></td>
        <td><?php echo h($e['contact_number']); ?></td>
        <td><?php echo number_format($e['basic_salary'], 2); ?></td>
        <td><?php echo number_format($e['commission'], 2); ?></td>
        <td><?php echo h($e['joining_date']); ?></td>
        <td><?php echo h($e['status']); ?></td>
        <td>
          <?php if (!empty($e['image_path'])): ?>
            <img src="<?php echo h($e['image_path']); ?>" alt="Photo" style="width:32px;height:32px;object-fit:cover;border-radius:50%;">
          <?php endif; ?>
        </td>
        <td class="text-nowrap">
          <button type="button"
                  class="btn btn-sm btn-outline-primary me-1 employee-edit-btn"
                  data-bs-toggle="offcanvas"
                  data-bs-target="#employeeSidebar"
                  data-id="<?php echo $e['id']; ?>"
                  data-emp-code="<?php echo h($e['emp_code']); ?>"
                  data-name="<?php echo h($e['name']); ?>"
                  data-branch-id="<?php echo (int)$e['branch_id']; ?>"
                  data-designation="<?php echo h($e['designation']); ?>"
                  data-contact="<?php echo h($e['contact_number']); ?>"
                  data-basic-salary="<?php echo h($e['basic_salary']); ?>"
                  data-commission="<?php echo h($e['commission']); ?>"
                  data-joining-date="<?php echo h($e['joining_date']); ?>"
                  data-status="<?php echo h($e['status']); ?>"
                  data-hold-salary="<?php echo (int)$e['hold_salary']; ?>">
            Edit
          </button>
          <a class="btn btn-sm btn-outline-secondary me-1" href="attendance.php?search=<?php echo urlencode($e['emp_code']); ?>">Attendance</a>
          <a class="btn btn-sm btn-outline-secondary" href="payroll.php?search=<?php echo urlencode($e['emp_code']); ?>">Payroll</a>
          <form method="post" class="d-inline" onsubmit="return confirm('Mark this employee as deleted?');">
            <input type="hidden" name="delete_id" value="<?php echo $e['id']; ?>">
            <button class="btn btn-sm btn-outline-danger ms-1" type="submit">Delete</button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  </div>
  </div>

  <div class="offcanvas offcanvas-end" tabindex="-1" id="employeeSidebar">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="employeeSidebarLabel">Add Employee</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <form method="post" enctype="multipart/form-data" id="employeeForm" class="row g-3">
        <input type="hidden" name="id" id="emp_id">
        <div class="col-md-12">
          <label class="form-label">Employee Code / ID</label>
          <input type="text" name="emp_code" id="emp_code" class="form-control" required>
        </div>
        <div class="col-md-12">
          <label class="form-label">Name</label>
          <input type="text" name="name" id="emp_name" class="form-control" required>
        </div>
        <div class="col-md-12">
          <label class="form-label">Branch</label>
          <select name="branch_id" id="emp_branch" class="form-select" required>
            <option value="">Select</option>
            <?php foreach($branches as $id=>$nm): ?>
              <option value="<?php echo $id; ?>"><?php echo h($nm); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label">Designation</label>
          <select name="designation" id="emp_designation" class="form-select">
            <?php foreach ($designation_options as $d): ?>
              <option value="<?php echo h($d); ?>"><?php echo h($d); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label">Contact Number</label>
          <input type="text" name="contact_number" id="emp_contact" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Basic Salary</label>
          <input type="number" step="0.01" name="basic_salary" id="emp_basic_salary" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Commission</label>
          <input type="number" step="0.01" name="commission" id="emp_commission" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Joining Date</label>
          <input type="date" name="joining_date" id="emp_joining_date" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Status</label>
          <select name="status" id="emp_status" class="form-select">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Hold Salary</label>
          <select name="hold_salary" id="emp_hold_salary" class="form-select">
            <option value="0">No</option>
            <option value="1">Yes</option>
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label">Image</label>
          <input type="file" name="image" class="form-control" accept="image/*">
        </div>
        <div class="col-md-12">
          <button class="btn btn-primary" type="submit" id="emp_submit_btn">Save</button>
        </div>
      </form>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click', function (e) {
  var editBtn = e.target.closest('.employee-edit-btn');
  if (editBtn) {
    document.getElementById('employeeSidebarLabel').textContent = 'Edit Employee';
    document.getElementById('emp_submit_btn').textContent = 'Update';
    document.getElementById('emp_id').value = editBtn.getAttribute('data-id') || '';
    document.getElementById('emp_code').value = editBtn.getAttribute('data-emp-code') || '';
    document.getElementById('emp_name').value = editBtn.getAttribute('data-name') || '';
    document.getElementById('emp_branch').value = editBtn.getAttribute('data-branch-id') || '';
    document.getElementById('emp_contact').value = editBtn.getAttribute('data-contact') || '';
    document.getElementById('emp_basic_salary').value = editBtn.getAttribute('data-basic-salary') || '';
    document.getElementById('emp_commission').value = editBtn.getAttribute('data-commission') || '';
    document.getElementById('emp_joining_date').value = editBtn.getAttribute('data-joining-date') || '';
    document.getElementById('emp_status').value = editBtn.getAttribute('data-status') || 'active';
    document.getElementById('emp_hold_salary').value = editBtn.getAttribute('data-hold-salary') || '0';
    var desig = editBtn.getAttribute('data-designation') || '';
    var desigSelect = document.getElementById('emp_designation');
    var found = false;
    for (var i = 0; i < desigSelect.options.length; i++) {
      if (desigSelect.options[i].value === desig) {
        desigSelect.selectedIndex = i;
        found = true;
        break;
      }
    }
    if (!found && desigSelect.options.length > 0) {
      desigSelect.selectedIndex = 0;
    }
    return;
  }
  var addBtn = e.target.closest('.employee-add-btn');
  if (addBtn) {
    document.getElementById('employeeSidebarLabel').textContent = 'Add Employee';
    document.getElementById('emp_submit_btn').textContent = 'Save';
    document.getElementById('emp_id').value = '';
    document.getElementById('employeeForm').reset();
    document.getElementById('emp_hold_salary').value = '0';
  }
});
</script>
</body>
</html>

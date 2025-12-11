<?php
require_once "config.php";
require_login();
if (($_SESSION['role'] ?? '') === 'branch') {
    die("Access denied");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CSV Import / Export - Essentia HR</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app-shell">
<?php include "navbar.php"; ?>
<main class="container app-main">
  <div class="page-title">
    <span class="page-title-icon">CSV</span>
    <span class="page-title-text">CSV Import / Export</span>
  </div>

  <div class="row g-4">
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header">Employees</div>
        <div class="card-body">
          <a class="btn btn-sm btn-outline-primary" href="export_employees_csv.php">Export Employees CSV</a>
          <hr>
          <form method="post" action="import_employees_csv.php" enctype="multipart/form-data">
            <div class="mb-3">
              <label class="form-label">Import Employees CSV</label>
              <input type="file" name="csv_file" class="form-control" accept=".csv" required>
            </div>
            <button class="btn btn-sm btn-primary" type="submit">Import</button>
          </form>
          <p class="mt-2"><small>Employee CSV columns (header names, missing ones are skipped): id (optional), emp_code, name, branch or branch_name, designation, contact or contact_number, basic_salary, commission, joining_date, status, image_path or image.</small></p>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header">Attendance</div>
        <div class="card-body">
          <a class="btn btn-sm btn-outline-primary" href="export_attendance_csv.php">Export Attendance CSV (This Month)</a>
          <hr>
          <form method="post" action="import_attendance_csv.php" enctype="multipart/form-data">
            <div class="mb-3">
              <label class="form-label">Import Attendance CSV</label>
              <input type="file" name="csv_file" class="form-control" accept=".csv" required>
            </div>
            <button class="btn btn-sm btn-primary" type="submit">Import</button>
          </form>
          <p class="mt-2"><small>Attendance CSV columns: emp_code, date (YYYY-MM-DD), status (P/A/L/H), remarks</small></p>
        </div>
      </div>

    </div>
  </div>

</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

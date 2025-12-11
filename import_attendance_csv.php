<?php
require_once "config.php";
require_login();

if (!isset($_FILES['csv_file'])) {
    die("No file uploaded");
}
$filename = $_FILES['csv_file']['tmp_name'];
if (!is_uploaded_file($filename)) {
    die("Upload error");
}

$handle = fopen($filename, "r");
if ($handle === false) {
    die("Cannot open file");
}

// skip header
$header = fgetcsv($handle, 1000, ",");

$changes = [];

while (($data = fgetcsv($handle, 1000, ",")) !== false) {
    list($emp_code, $date, $status, $remarks) = $data;
    $emp_code = trim($emp_code);
    if ($emp_code === '') continue;

    // find employee
    $stmt = $mysqli->prepare("SELECT id FROM employees WHERE emp_code=?");
    $stmt->bind_param("s", $emp_code);
    $stmt->execute();
    $stmt->bind_result($emp_id);
    if ($stmt->fetch()) {
        $stmt->close();

        // read previous attendance row (if any) for undo
        $prevStmt = $mysqli->prepare("SELECT status, remarks FROM attendance WHERE emp_id=? AND date=?");
        $prevStmt->bind_param("is", $emp_id, $date);
        $prevStmt->execute();
        $prevStmt->bind_result($prev_status, $prev_remarks);
        $had_prev = $prevStmt->fetch();
        $prevStmt->close();

        $stmt2 = $mysqli->prepare("REPLACE INTO attendance (emp_id, date, status, remarks) VALUES (?,?,?,?)");
        $stmt2->bind_param("isss", $emp_id, $date, $status, $remarks);
        $stmt2->execute();
        $stmt2->close();

        $changes[] = [
            'emp_id' => (int)$emp_id,
            'date' => $date,
            'had_previous' => $had_prev ? 1 : 0,
            'previous_status' => $had_prev ? $prev_status : null,
            'previous_remarks' => $had_prev ? $prev_remarks : null,
        ];
    } else {
        $stmt->close();
        // unknown employee code, skip
    }
}

fclose($handle);

log_activity(
    $mysqli,
    'import_attendance_csv',
    'Imported attendance CSV',
    ['changes' => $changes],
    true
);

header("Location: csv_tools.php");
exit;

<?php
require_once "config.php";
require_login();
if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

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

// read header and map columns (case-insensitive)
$header = fgetcsv($handle, 2000, ",");
if ($header === false) {
    die("Empty file");
}
$map = [];
foreach ($header as $idx => $col) {
    $key = strtolower(trim($col));
    $map[$key] = $idx;
}

// helper to safely get a column value
$get = function(array $row, array $map, string $key, $default = '') {
    return isset($map[$key]) && isset($row[$map[$key]]) ? trim($row[$map[$key]]) : $default;
};

$changes = [];

while (($data = fgetcsv($handle, 2000, ",")) !== false) {
    $emp_code = $get($data, $map, 'emp_code');
    if ($emp_code === '') continue;

    $name = $get($data, $map, 'name', null);
    $branch_name = $get($data, $map, 'branch', $get($data, $map, 'branch_name', null));
    $designation = $get($data, $map, 'designation', null);
    $contact = $get($data, $map, 'contact', $get($data, $map, 'contact_number', null));
    $basic_salary = $get($data, $map, 'basic_salary', null);
    $commission = $get($data, $map, 'commission', null);
    $joining_date = $get($data, $map, 'joining_date', null);
    $status = $get($data, $map, 'status', null);
    $image_path = $get($data, $map, 'image_path', $get($data, $map, 'image', null));

    $branch_id = null;
    if ($branch_name !== null && $branch_name !== '') {
        $stmt = $mysqli->prepare("SELECT id FROM branches WHERE name=?");
        $stmt->bind_param("s", $branch_name);
        $stmt->execute();
        $stmt->bind_result($bid);
        if ($stmt->fetch()) {
            $branch_id = $bid;
        }
        $stmt->close();
        if ($branch_id === null) {
            $stmt = $mysqli->prepare("INSERT INTO branches (name, manager_name, manager_contact, status) VALUES (?, NULL, NULL, 'active')");
            $stmt->bind_param("s", $branch_name);
            $stmt->execute();
            $branch_id = $stmt->insert_id;
            $stmt->close();
        }
    }

    // upsert employee by emp_code
    $stmt = $mysqli->prepare("SELECT id, name, branch_id, designation, contact_number, basic_salary, commission, joining_date, status, image_path FROM employees WHERE emp_code=?");
    $stmt->bind_param("s", $emp_code);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $eid = intval($row['id']);
        $new_name = $name !== null && $name !== '' ? $name : $row['name'];
        $new_branch_id = $branch_id !== null ? $branch_id : $row['branch_id'];
        $new_designation = $designation !== null && $designation !== '' ? $designation : $row['designation'];
        $new_contact = $contact !== null ? $contact : $row['contact_number'];
        $new_basic = $basic_salary !== null && $basic_salary !== '' ? floatval($basic_salary) : $row['basic_salary'];
        $new_comm = $commission !== null && $commission !== '' ? floatval($commission) : $row['commission'];
        $new_joining = $joining_date !== null && $joining_date !== '' ? $joining_date : $row['joining_date'];
        $new_status = $status !== null && $status !== '' ? $status : $row['status'];
        $new_image = $image_path !== null && $image_path !== '' ? $image_path : $row['image_path'];

        $stmt->close();

        $changes[] = [
            'type' => 'update',
            'id' => $eid,
            'previous' => $row,
        ];
        $stmt = $mysqli->prepare("UPDATE employees SET name=?, branch_id=?, designation=?, contact_number=?, basic_salary=?, commission=?, joining_date=?, status=?, image_path=? WHERE id=?");
        $stmt->bind_param("sissddsssi", $new_name, $new_branch_id, $new_designation, $new_contact, $new_basic, $new_comm, $new_joining, $new_status, $new_image, $eid);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt->close();
        // for new employees, require at least name and branch_id
        if ($name === null || $name === '' || $branch_id === null) {
            continue;
        }
        $insert_basic = $basic_salary !== null && $basic_salary !== '' ? floatval($basic_salary) : 0.0;
        $insert_comm = $commission !== null && $commission !== '' ? floatval($commission) : 0.0;
        $insert_joining = $joining_date !== null && $joining_date !== '' ? $joining_date : null;
        $insert_status = $status !== null && $status !== '' ? $status : 'active';
        $insert_contact = $contact ?? '';
        $insert_image = $image_path ?? null;

        $stmt = $mysqli->prepare("INSERT INTO employees (emp_code, name, branch_id, designation, contact_number, image_path, basic_salary, commission, joining_date, status, hold_salary, hold_balance) VALUES (?,?,?,?,?,?,?,?,?,?,0,0)");
        // types: emp_code(s), name(s), branch_id(i), designation(s), contact(s), image_path(s), basic_salary(d), commission(d), joining_date(s), status(s)
        $stmt->bind_param("ssisssddss", $emp_code, $name, $branch_id, $designation, $insert_contact, $insert_image, $insert_basic, $insert_comm, $insert_joining, $insert_status);
        $stmt->execute();
        $new_id = $stmt->insert_id;
        $stmt->close();

        if ($new_id > 0) {
            $changes[] = [
                'type' => 'insert',
                'id' => $new_id,
            ];
        }
    }
}

fclose($handle);

log_activity(
    $mysqli,
    'import_employees_csv',
    'Imported employees CSV',
    ['changes' => $changes],
    true
);

header("Location: csv_tools.php");
exit;

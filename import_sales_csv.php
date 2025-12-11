<?php
require_once "config.php";
require_login();

if (!isset($_FILES['csv_file'])) {
    die("No file uploaded");
}

$month = intval($_POST['month'] ?? date('n'));
$year = intval($_POST['year'] ?? date('Y'));

$tmpName = $_FILES['csv_file']['tmp_name'];
if (!is_uploaded_file($tmpName)) {
    die("Upload error");
}

$handle = fopen($tmpName, "r");
if ($handle === false) {
    die("Cannot open file");
}

// read header and map columns (case-insensitive)
$header = fgetcsv($handle, 1000, ",");
if ($header === false) {
    die("Empty file");
}
$map = [];
foreach ($header as $idx => $col) {
    $key = strtolower(trim($col));
    $map[$key] = $idx;
}

$get = function(array $row, array $map, string $key, $default = 0) {
    return isset($map[$key]) && isset($row[$map[$key]]) ? $row[$map[$key]] : $default;
};

// first pass: load rows into memory and accumulate branch totals.
// Total branch sale for a month is defined as the sum of all
// employees' sales for that branch. Later, this total will be
// assigned to the Branch Manager's own sale in payroll so that
// "Branch Manager sale = total branch sale".
$rows = [];
$branch_totals = [];          // branch_id => total sales
while (($data = fgetcsv($handle, 1000, ",")) !== false) {
    $emp_code = trim($get($data, $map, 'emp_code', ''));
    if ($emp_code === '') {
        continue;
    }

    $sales = floatval($get($data, $map, 'sales', 0));
    $total_days = intval($get($data, $map, 'total_days', 0));
    $earned_days = intval($get($data, $map, 'earned_days', 0));
    $rows[] = [$emp_code, $sales, $total_days, $earned_days];

    // look up branch (designation not needed in this pass)
    $stmt = $mysqli->prepare("SELECT branch_id FROM employees WHERE emp_code=?");
    $stmt->bind_param("s", $emp_code);
    $stmt->execute();
    $stmt->bind_result($emp_branch_id);
    if ($stmt->fetch()) {
        $branch_id = intval($emp_branch_id);
        if (!isset($branch_totals[$branch_id])) {
            $branch_totals[$branch_id] = 0;
        }
        $branch_totals[$branch_id] += $sales;
    }
    $stmt->close();
}

// second pass: apply payroll using branch totals and targets
$activity_changes = [];
foreach ($rows as $row) {
    list($emp_code, $sales, $total_days, $earned_days) = $row;
    $bonus_manual = 0.0;

    // find employee
    $stmt = $mysqli->prepare("SELECT id, basic_salary, commission, hold_salary, hold_balance, branch_id, designation, joining_date FROM employees WHERE emp_code=?");
    $stmt->bind_param("s", $emp_code);
    $stmt->execute();
    $stmt->bind_result($emp_id, $basic_salary, $emp_commission, $hold_salary, $hold_balance, $emp_branch_id, $designation, $joining_date);
    if (!$stmt->fetch()) {
        $stmt->close();
        continue;
    }
    $stmt->close();

    // skip employees who join after this payroll month
    if (!empty($joining_date) && $joining_date !== '0000-00-00') {
        $join_ts = strtotime($joining_date);
        if ($join_ts !== false) {
            $join_year = (int)date('Y', $join_ts);
            $join_month = (int)date('n', $join_ts);
            if ($join_year > $year || ($join_year === $year && $join_month > $month)) {
                // joining is in a later month than this payroll period; skip
                continue;
            }
        }
    }

    $orig_hold_balance = $hold_balance;

    // branch role restriction
    if ($_SESSION['role'] === 'branch') {
        $allowed_bid = intval($_SESSION['branch_id']);
        if (intval($emp_branch_id) !== $allowed_bid) {
            continue;
        }
    }

    // derive commission percent from employee master
    $commission_percent = floatval($emp_commission);

    // load existing payroll row (for undo + default days)
    $existing_payroll = null;
    $daysRes = $mysqli->query("SELECT * FROM payroll WHERE emp_id={$emp_id} AND month={$month} AND year={$year}");
    if ($daysRes && $drow = $daysRes->fetch_assoc()) {
        $existing_payroll = $drow;
        if ($total_days <= 0) {
            $total_days = intval($drow['total_days']);
        }
        if ($earned_days <= 0) {
            $earned_days = intval($drow['earned_days']);
        }
    }

    // determine branch total sale for this month
    $branch_sales = $branch_totals[$emp_branch_id] ?? 0;

    // sales used for calculations:
    //  - Branch Manager: branch total sale (so manager sale == total branch sale)
    //  - Others: their individual sales from CSV.
    $sales_for_calc = ($designation === 'Branch Manager') ? $branch_sales : $sales;

    // gross
    if ($total_days > 0) {
        $gross_salary = ($basic_salary / $total_days) * $earned_days;
    } else {
        $gross_salary = $basic_salary;
    }

    $commission_amount = ($sales_for_calc * $commission_percent) / 100.0;

    // bonus from branch-level targets (two-level, branch-based)
    // Branch sale is the total branch sale for this branch/month/year.
    $bonus_target = 0;
    if ($branch_sales > 0) {
        // step 1: determine achieved target level for this branch
        $achieved_target = null;
        $tstmt = $mysqli->prepare("SELECT MAX(sales_target) AS max_target
                                   FROM branch_targets
                                   WHERE branch_id=? AND month=? AND year=? AND sales_target <= ?");
        $tstmt->bind_param("iiid", $emp_branch_id, $month, $year, $branch_sales);
        $tstmt->execute();
        $tstmt->bind_result($max_target);
        if ($tstmt->fetch() && $max_target !== null) {
            $achieved_target = floatval($max_target);
        }
        $tstmt->close();

        // step 2: designation-specific bonus at that target level
        if ($achieved_target !== null && $achieved_target > 0 && $designation !== '') {
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

    $bonus = $bonus_manual + $bonus_target;
    $net_salary = $gross_salary + $commission_amount + $bonus;

    if (intval($hold_salary) === 1) {
        $hold_balance += $net_salary;
        $net_to_store = 0;
    } else {
        $net_to_store = $net_salary + $hold_balance;
        $hold_balance = 0;
    }

    $stmt = $mysqli->prepare("REPLACE INTO payroll (emp_id, month, year, total_days, earned_days, sales, commission_percent, bonus, gross_salary, commission_amount, net_salary) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("iiiiididddd", $emp_id, $month, $year, $total_days, $earned_days, $sales_for_calc, $commission_percent, $bonus, $gross_salary, $commission_amount, $net_to_store);
    $stmt->execute();
    $stmt->close();

    $stmt = $mysqli->prepare("UPDATE employees SET hold_balance=? WHERE id=?");
    $stmt->bind_param("di", $hold_balance, $emp_id);
    $stmt->execute();
    $stmt->close();

    // record change for activity log / undo
    $activity_changes[] = [
        'emp_id' => $emp_id,
        'month' => $month,
        'year' => $year,
        'previous_payroll' => $existing_payroll,
        'previous_hold_balance' => $orig_hold_balance,
    ];
}

fclose($handle);

$periodMonth = $month;
$periodYear = $year;

// log activity with undo data
log_activity(
    $mysqli,
    'import_sales_csv',
    "Imported sales CSV for {$periodMonth}/{$periodYear}",
    ['month' => $periodMonth, 'year' => $periodYear, 'changes' => $activity_changes],
    true
);

header("Location: payroll.php?month=" . $periodMonth . "&year=" . $periodYear);
exit;

<?php
require_once "config.php";
require_login();

// only admin can view activity log
if (($_SESSION['role'] ?? '') !== 'admin') {
    die("Access denied");
}

// purge activity older than 7 days (keep only last week)
$mysqli->query("DELETE FROM activity_log WHERE created_at < (NOW() - INTERVAL 7 DAY)");

// handle undo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['undo_id'])) {
    $undo_id = intval($_POST['undo_id']);
    if ($undo_id > 0) {
        $stmt = $mysqli->prepare("SELECT * FROM activity_log WHERE id=? AND can_undo=1 AND undone_at IS NULL");
        $stmt->bind_param("i", $undo_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $activity = $res->fetch_assoc();
        $stmt->close();

        if ($activity) {
            $details = [];
            if (!empty($activity['details'])) {
                $decoded = json_decode($activity['details'], true);
                if (is_array($decoded)) {
                    $details = $decoded;
                }
            }

            $action_type = $activity['action_type'];

            // basic safety: wrap undo in transaction
            $mysqli->begin_transaction();
            $success = true;

            try {
                if ($action_type === 'import_attendance_csv') {
                    // restore or delete attendance rows that were affected
                    foreach ($details['changes'] ?? [] as $chg) {
                        $emp_id = (int)($chg['emp_id'] ?? 0);
                        $date = $chg['date'] ?? null;
                        if ($emp_id <= 0 || !$date) continue;
                        if (!empty($chg['had_previous'])) {
                            $status = $chg['previous_status'] ?? '';
                            $remarks = $chg['previous_remarks'] ?? '';
                            $st = $mysqli->prepare("REPLACE INTO attendance (emp_id, date, status, remarks) VALUES (?,?,?,?)");
                            $st->bind_param("isss", $emp_id, $date, $status, $remarks);
                            $st->execute();
                            $st->close();
                        } else {
                            $st = $mysqli->prepare("DELETE FROM attendance WHERE emp_id=? AND date=?");
                            $st->bind_param("is", $emp_id, $date);
                            $st->execute();
                            $st->close();
                        }
                    }
                } elseif ($action_type === 'import_employees_csv') {
                    // revert employee inserts/updates
                    foreach ($details['changes'] ?? [] as $chg) {
                        $type = $chg['type'] ?? '';
                        $id = intval($chg['id'] ?? 0);
                        if ($id <= 0) continue;
                        if ($type === 'insert') {
                            $st = $mysqli->prepare("DELETE FROM employees WHERE id=?");
                            $st->bind_param("i", $id);
                            $st->execute();
                            $st->close();
                        } elseif ($type === 'update' && !empty($chg['previous']) && is_array($chg['previous'])) {
                            $p = $chg['previous'];
                            $st = $mysqli->prepare("UPDATE employees SET name=?, branch_id=?, designation=?, contact_number=?, basic_salary=?, commission=?, joining_date=?, status=?, image_path=? WHERE id=?");
                            $st->bind_param(
                                "sissddsssi",
                                $p['name'],
                                $p['branch_id'],
                                $p['designation'],
                                $p['contact_number'],
                                $p['basic_salary'],
                                $p['commission'],
                                $p['joining_date'],
                                $p['status'],
                                $p['image_path'],
                                $p['id']
                            );
                            $st->execute();
                            $st->close();
                        }
                    }
                } elseif ($action_type === 'import_sales_csv') {
                    // revert payroll rows and hold balances
                    foreach ($details['changes'] ?? [] as $chg) {
                        $emp_id = intval($chg['emp_id'] ?? 0);
                        $month = intval($chg['month'] ?? 0);
                        $year = intval($chg['year'] ?? 0);
                        if ($emp_id <= 0 || $month <= 0 || $year <= 0) continue;

                        $prev = $chg['previous_payroll'] ?? null;
                        if ($prev && is_array($prev)) {
                            $st = $mysqli->prepare("REPLACE INTO payroll (emp_id, month, year, total_days, earned_days, sales, commission_percent, bonus, gross_salary, commission_amount, net_salary) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                            $st->bind_param(
                                "iiiiididddd",
                                $prev['emp_id'],
                                $prev['month'],
                                $prev['year'],
                                $prev['total_days'],
                                $prev['earned_days'],
                                $prev['sales'],
                                $prev['commission_percent'],
                                $prev['bonus'],
                                $prev['gross_salary'],
                                $prev['commission_amount'],
                                $prev['net_salary']
                            );
                            $st->execute();
                            $st->close();
                        } else {
                            $st = $mysqli->prepare("DELETE FROM payroll WHERE emp_id=? AND month=? AND year=?");
                            $st->bind_param("iii", $emp_id, $month, $year);
                            $st->execute();
                            $st->close();
                        }

                        if (array_key_exists('previous_hold_balance', $chg) && $chg['previous_hold_balance'] !== null) {
                            $prev_hold = (float)$chg['previous_hold_balance'];
                            $st = $mysqli->prepare("UPDATE employees SET hold_balance=? WHERE id=?");
                            $st->bind_param("di", $prev_hold, $emp_id);
                            $st->execute();
                            $st->close();
                        }
                    }
                } elseif ($action_type === 'delete_employee') {
                    // restore previous status
                    $emp_id = intval($details['employee_id'] ?? 0);
                    $prev_status = $details['previous_status'] ?? null;
                    if ($emp_id > 0 && $prev_status !== null) {
                        $st = $mysqli->prepare("UPDATE employees SET status=? WHERE id=?");
                        $st->bind_param("si", $prev_status, $emp_id);
                        $st->execute();
                        $st->close();
                    }
                } elseif ($action_type === 'delete_targets_group') {
                    // reinsert deleted target rows
                    foreach ($details['rows'] ?? [] as $r) {
                        $st = $mysqli->prepare("INSERT INTO branch_targets (id, branch_id, designation, month, year, sales_target, bonus_amount, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
                        $st->bind_param(
                            "iisiiid",
                            $r['id'],
                            $r['branch_id'],
                            $r['designation'],
                            $r['month'],
                            $r['year'],
                            $r['sales_target'],
                            $r['bonus_amount']
                        );
                        $st->execute();
                        $st->close();
                    }
                }

                // remove activity entry after successful undo
                $del = $mysqli->prepare("DELETE FROM activity_log WHERE id=?");
                $del->bind_param("i", $undo_id);
                $del->execute();
                $del->close();

                $mysqli->commit();
            } catch (Throwable $e) {
                $mysqli->rollback();
                $success = false;
            }

            if ($success) {
                $msg = "Action has been undone.";
            } else {
                $msg = "Failed to undo action.";
            }
        }
    }
}

// load recent activity
$rows = [];
$res = $mysqli->query("SELECT a.*, u.username
                       FROM activity_log a
                       LEFT JOIN users u ON u.id = a.user_id
                       ORDER BY a.id DESC
                       LIMIT 200");
while ($res && $r = $res->fetch_assoc()) {
    $rows[] = $r;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Activity Log - Essentia HR</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body class="app-shell">
<?php include "navbar.php"; ?>
<main class="container app-main">
  <div class="page-title">
    <span class="page-title-icon">AC</span>
    <span class="page-title-text">Activity</span>
  </div>
  <?php if (!empty($msg)): ?>
    <div class="alert alert-info"><?php echo h($msg); ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-sm mb-0 list-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Time</th>
            <th>User</th>
            <th>Action</th>
            <th>Description</th>
            <th>Undo</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $a): ?>
          <tr>
            <td><?php echo (int)$a['id']; ?></td>
            <td><?php echo h($a['created_at']); ?></td>
            <td><?php echo h($a['username'] ?? ''); ?></td>
            <td><?php echo h($a['action_type']); ?></td>
            <td><?php echo h($a['description']); ?></td>
            <td>
              <?php if ((int)$a['can_undo'] === 1 && empty($a['undone_at'])): ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Undo this action?');">
                  <input type="hidden" name="undo_id" value="<?php echo (int)$a['id']; ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit">Undo</button>
                </form>
              <?php elseif (!empty($a['undone_at'])): ?>
                <span class="badge bg-secondary">Undone</span>
              <?php else: ?>
                <span class="text-muted">N/A</span>
              <?php endif; ?>
            </td>
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

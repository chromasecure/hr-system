<?php
require_once "config.php";
require_login();

if (($_SESSION['role'] ?? '') !== 'admin') {
    die("Access denied");
}

// filters for viewing / adding targets
$month = intval($_GET['month'] ?? date('n'));
$year = intval($_GET['year'] ?? date('Y'));
$branch_id_filter = intval($_GET['branch_id'] ?? 0);

// load branches
$branches = [];
$res = $mysqli->query("SELECT id, name FROM branches ORDER BY name ASC");
while($row = $res->fetch_assoc()) { $branches[$row['id']] = $row['name']; }

// designations eligible for targets
$allowed_designations = ["Branch Manager","Cashier","Sales Man"];

// handle delete / add / update target (by branch + designation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    $expired_period_post = $_POST['expired_period'] ?? '';
    if ($del_id > 0) {
        // delete all thresholds for this branch+designation+month+year group
        $stmt = $mysqli->prepare("SELECT branch_id, designation, month, year FROM branch_targets WHERE id=?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $stmt->bind_result($db_branch_id, $db_designation, $db_month, $db_year);
        if ($stmt->fetch()) {
            $stmt->close();
            // capture all rows to be deleted for undo
            $rows = [];
            $sel = $mysqli->prepare("SELECT * FROM branch_targets WHERE branch_id=? AND designation=? AND month=? AND year=?");
            $sel->bind_param("isii", $db_branch_id, $db_designation, $db_month, $db_year);
            $sel->execute();
            $result = $sel->get_result();
            while ($r = $result->fetch_assoc()) {
                $rows[] = $r;
            }
            $sel->close();

            $stmt2 = $mysqli->prepare("DELETE FROM branch_targets WHERE branch_id=? AND designation=? AND month=? AND year=?");
            $stmt2->bind_param("isii", $db_branch_id, $db_designation, $db_month, $db_year);
            $stmt2->execute();
            $stmt2->close();

            if (!empty($rows)) {
                $branch_name = $branches[$db_branch_id] ?? ('Branch ID ' . $db_branch_id);
                log_activity(
                    $mysqli,
                    'delete_targets_group',
                    "Deleted targets for {$branch_name} ({$db_designation}) {$db_month}/{$db_year}",
                    ['rows' => $rows],
                    true
                );
            }
        } else {
            $stmt->close();
        }
    }
    $extra = $expired_period_post !== '' ? '&expired_period=' . urlencode($expired_period_post) : '';
    header("Location: targets.php?month={$month}&year={$year}&branch_id={$branch_id_filter}{$extra}");
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_targets'])) {
    // new sidebar form submission: branch-level targets with designation-wise bonuses
    $t_month = intval($_POST['t_month'] ?? $month);
    $t_year = intval($_POST['t_year'] ?? $year);
    $target_branch_id = intval($_POST['target_branch_id'] ?? 0);
    $sales_target1 = floatval($_POST['sales_target1'] ?? 0);
    $sales_target2 = floatval($_POST['sales_target2'] ?? 0);
    $designations = $_POST['designation'] ?? [];
    $bonus1_list = $_POST['bonus1'] ?? [];
    $bonus2_list = $_POST['bonus2'] ?? [];

    // optional: editing existing group by id (branch + designation + month + year)
    $group_edit_id = intval($_POST['group_edit_id'] ?? 0);

    if ($t_month > 0 && $t_year > 0 && (!empty($sales_target1) || !empty($sales_target2))) {
        // determine branches / designation when editing
        if ($group_edit_id > 0) {
            // look up original branch + designation + period
            $stmt = $mysqli->prepare("SELECT branch_id, designation, month, year FROM branch_targets WHERE id=?");
            $stmt->bind_param("i", $group_edit_id);
            $stmt->execute();
            $stmt->bind_result($db_branch_id, $db_designation, $db_month, $db_year);
            if ($stmt->fetch()) {
                $t_month = (int)$db_month;
                $t_year = (int)$db_year;
                $target_branch_id = (int)$db_branch_id;
                $designations = [$db_designation];
            }
            $stmt->close();
        }

        // determine branches to apply to
        $branch_ids = [];
        if ($target_branch_id > 0) {
            $branch_ids[] = $target_branch_id;
        } else {
            $bres_all = $mysqli->query("SELECT id FROM branches");
            while ($bres_all && $brow = $bres_all->fetch_assoc()) {
                $branch_ids[] = intval($brow['id']);
            }
        }

        foreach ($branch_ids as $bid) {
            // if editing, clear existing targets only for this branch+designation+month+year
            if ($group_edit_id > 0 && !empty($designations)) {
                $des = $designations[0];
                $del = $mysqli->prepare("DELETE FROM branch_targets WHERE branch_id=? AND designation=? AND month=? AND year=?");
                $del->bind_param("isii", $bid, $des, $t_month, $t_year);
                $del->execute();
                $del->close();
            }

            // insert designation-wise bonuses for each target level
            foreach ($designations as $idx => $des) {
                $des = trim($des);
                if ($des === '' || !in_array($des, $allowed_designations, true)) {
                    continue;
                }
                $b1 = isset($bonus1_list[$idx]) ? floatval($bonus1_list[$idx]) : 0.0;
                $b2 = isset($bonus2_list[$idx]) ? floatval($bonus2_list[$idx]) : 0.0;

                if ($sales_target1 > 0 && $b1 > 0) {
                    $stmt = $mysqli->prepare("INSERT INTO branch_targets (branch_id, designation, month, year, sales_target, bonus_amount)
                                              VALUES (?,?,?,?,?,?)");
                    $stmt->bind_param("isiiid", $bid, $des, $t_month, $t_year, $sales_target1, $b1);
                    $stmt->execute();
                    $stmt->close();
                }
                if ($sales_target2 > 0 && $b2 > 0) {
                    $stmt = $mysqli->prepare("INSERT INTO branch_targets (branch_id, designation, month, year, sales_target, bonus_amount)
                                              VALUES (?,?,?,?,?,?)");
                    $stmt->bind_param("isiiid", $bid, $des, $t_month, $t_year, $sales_target2, $b2);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    header("Location: targets.php?month={$t_month}&year={$t_year}&branch_id={$branch_id_filter}");
    exit;
}

// load current month targets
$where_current = "bt.month={$month} AND bt.year={$year}";
if ($branch_id_filter > 0) {
    $where_current .= " AND bt.branch_id={$branch_id_filter}";
}
$targets_current = [];
$res_cur = $mysqli->query("SELECT bt.*, b.name AS branch_name
                           FROM branch_targets bt
                           JOIN branches b ON b.id=bt.branch_id
                           WHERE {$where_current}
                           ORDER BY b.name, bt.designation, bt.sales_target");
while($res_cur && $row = $res_cur->fetch_assoc()) {
    $targets_current[] = $row;
}
// group current targets by branch + designation into up to two thresholds
$grouped_current = [];
foreach ($targets_current as $t) {
    $key = $t['branch_id'] . '|' . $t['designation'];
    if (!isset($grouped_current[$key])) {
        $grouped_current[$key] = [
            'branch_id' => $t['branch_id'],
            'branch_name' => $t['branch_name'],
            'designation' => $t['designation'],
            'month' => $t['month'],
            'year' => $t['year'],
            'rows' => []
        ];
    }
    $grouped_current[$key]['rows'][] = $t;
}
// reduce each group to at most two ordered thresholds
foreach ($grouped_current as &$g) {
    usort($g['rows'], function ($a, $b) {
        return $a['sales_target'] <=> $b['sales_target'];
    });
    $g['id1'] = null;
    $g['target1'] = null;
    $g['bonus1'] = null;
    $g['id2'] = null;
    $g['target2'] = null;
    $g['bonus2'] = null;

    if (!empty($g['rows'][0])) {
        $r1 = $g['rows'][0];
        $g['id1'] = $r1['id'];
        $g['target1'] = (float)$r1['sales_target'];
        $g['bonus1'] = (float)$r1['bonus_amount'];
    }
    if (!empty($g['rows'][1])) {
        $r2 = $g['rows'][1];
        $g['id2'] = $r2['id'];
        $g['target2'] = (float)$r2['sales_target'];
        $g['bonus2'] = (float)$r2['bonus_amount'];
    }
    unset($g['rows']);
}
unset($g);

// load expired targets by explicit month/year selection (dropdown)
// expired months are months before the current calendar month
$expired_period = $_GET['expired_period'] ?? ($_POST['expired_period'] ?? '');
$expired_month = 0;
$expired_year = 0;
if ($expired_period && preg_match('/^(\\d{4})-(\\d{2})$/', $expired_period, $m)) {
    $expired_year = intval($m[1]);
    $expired_month = intval($m[2]);
}

// build list of expired months available in branch_targets
$currentYear = intval(date('Y'));
$currentMonth = intval(date('n'));
$expired_months = [];
$expRes = $mysqli->query("SELECT DISTINCT year, month
                          FROM branch_targets
                          WHERE (year < {$currentYear} OR (year = {$currentYear} AND month < {$currentMonth}))
                          ORDER BY year DESC, month DESC");
while ($expRes && $row = $expRes->fetch_assoc()) {
    $ey = intval($row['year']);
    $em = intval($row['month']);
    $expired_months[] = [
        'year' => $ey,
        'month' => $em,
        'label' => date('F Y', strtotime(sprintf('%04d-%02d-01', $ey, $em))),
    ];
}

$targets_expired = [];
if ($expired_month > 0 && $expired_year > 0) {
    $where_exp = "bt.year = {$expired_year} AND bt.month = {$expired_month}";
    if ($branch_id_filter > 0) {
        $where_exp .= " AND bt.branch_id={$branch_id_filter}";
    }
    $res_exp = $mysqli->query("SELECT bt.*, b.name AS branch_name
                               FROM branch_targets bt
                               JOIN branches b ON b.id=bt.branch_id
                               WHERE {$where_exp}
                               ORDER BY b.name, bt.designation, bt.sales_target");
    while($res_exp && $row = $res_exp->fetch_assoc()) {
        $targets_expired[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Targets - Essentia HR</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app-shell">
<?php include "navbar.php"; ?>
<main class="container app-main">
  <div class="page-header mb-3">
    <div class="page-title mb-0">
      <span class="page-title-icon">TG</span>
      <span class="page-title-text">Monthly Sales Targets</span>
    </div>
    <div class="page-actions">
      <a class="btn btn-sm btn-outline-primary" href="export_targets_csv.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>">Export CSV</a>
      <a class="btn btn-sm btn-outline-secondary" href="export_targets_pdf.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>" target="_blank">Export PDF</a>
    </div>
  </div>

<?php $month_names = [1=>"January",2=>"February",3=>"March",4=>"April",5=>"May",6=>"June",7=>"July",8=>"August",9=>"September",10=>"October",11=>"November",12=>"December"]; ?>

  <div class="filter-bar mb-3">
    <form class="row g-3 align-items-end" method="get">
      <div class="col-md-3">
        <label class="form-label subtle-label">Month</label>
        <select name="month" class="form-select">
          <?php foreach($month_names as $mNum=>$mName): ?>
            <option value="<?php echo $mNum; ?>" <?php echo $month === $mNum ? 'selected' : ''; ?>>
              <?php echo h($mName); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label subtle-label">Year</label>
        <input type="number" name="year" value="<?php echo $year; ?>" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label subtle-label">Branch</label>
        <select name="branch_id" class="form-select">
          <option value="">All branches</option>
          <?php foreach($branches as $id=>$nm): ?>
            <option value="<?php echo $id; ?>" <?php echo $branch_id_filter == $id ? 'selected' : ''; ?>>
              <?php echo h($nm); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <button class="btn btn-secondary mt-3" type="submit">Apply</button>
      </div>
    </form>
  </div>

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>Current Month Targets (<?php echo "{$month}/{$year}"; ?>)</span>
      <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm" id="addTargetsBtn" type="button" data-bs-toggle="offcanvas" data-bs-target="#targetsSidebar">
          Add Targets
        </button>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-sm mb-0 list-table">
        <thead>
          <tr>
            <th>Branch</th>
            <th>Designation</th>
            <th>Target 1 Sales</th>
            <th>Target 1 Bonus</th>
            <th>Target 2 Sales</th>
            <th>Target 2 Bonus</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($grouped_current as $g): ?>
          <tr>
            <td><?php echo h($g['branch_name']); ?></td>
            <td><?php echo h($g['designation']); ?></td>
            <td><?php echo $g['target1'] !== null ? number_format($g['target1'], 2) : ''; ?></td>
            <td><?php echo $g['bonus1'] !== null ? number_format($g['bonus1'], 2) : ''; ?></td>
            <td><?php echo $g['target2'] !== null ? number_format($g['target2'], 2) : ''; ?></td>
            <td><?php echo $g['bonus2'] !== null ? number_format($g['bonus2'], 2) : ''; ?></td>
            <td>
              <button type="button"
                      class="btn btn-sm btn-outline-primary target-edit-btn mb-1"
                      data-id="<?php echo (int)$g['id1']; ?>"
                      data-designation="<?php echo h($g['designation']); ?>"
                      data-branch-id="<?php echo (int)$g['branch_id']; ?>"
                      data-month="<?php echo (int)$g['month']; ?>"
                      data-year="<?php echo (int)$g['year']; ?>"
                      data-target1="<?php echo h($g['target1']); ?>"
                      data-bonus1="<?php echo h($g['bonus1']); ?>"
                      data-target2="<?php echo h($g['target2']); ?>"
                      data-bonus2="<?php echo h($g['bonus2']); ?>">
                Edit
              </button>
              <?php if ($g['id1']): ?>
              <form method="post" class="d-inline" onsubmit="return confirm('Delete all targets for this branch and designation?');">
                <input type="hidden" name="delete_id" value="<?php echo (int)$g['id1']; ?>">
                <button class="btn btn-sm btn-outline-danger mb-1" type="submit">Delete</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Targets sidebar -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="targetsSidebar">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title">Add Targets</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <form method="post" class="row g-3">
        <input type="hidden" name="save_targets" value="1">
        <input type="hidden" name="group_edit_id" id="group_edit_id" value="0">
        <div class="col-md-4">
          <label class="form-label">Month</label>
          <select name="t_month" id="t_month" class="form-select" required>
            <?php foreach($month_names as $mNum=>$mName): ?>
              <option value="<?php echo $mNum; ?>" <?php echo $month === $mNum ? 'selected' : ''; ?>>
                <?php echo h($mName); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Year</label>
          <input type="number" name="t_year" id="t_year" value="<?php echo $year; ?>" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Branch</label>
          <select name="target_branch_id" id="target_branch_id" class="form-select">
            <option value="">All branches</option>
            <?php foreach($branches as $id=>$nm): ?>
              <option value="<?php echo $id; ?>" <?php echo $branch_id_filter == $id ? 'selected' : ''; ?>>
                <?php echo h($nm); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Branch Target 1 Sales</label>
          <input type="number" step="0.01" name="sales_target1" id="sales_target1" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Branch Target 2 Sales</label>
          <input type="number" step="0.01" name="sales_target2" id="sales_target2" class="form-control">
        </div>

        <div class="col-12 mt-3">
          <h6>Designation bonuses</h6>
        </div>
        <div class="col-12" id="designationRows"></div>
        <div class="col-12">
          <button type="button" class="btn btn-outline-primary btn-sm" id="addDesignationRow">+ Add designation bonus</button>
        </div>

        <div class="col-12 mt-3">
          <button class="btn btn-primary" type="submit">Save</button>
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Expired Targets</div>
    <div class="card-body border-bottom">
      <form class="row g-3 align-items-end" method="get">
        <input type="hidden" name="month" value="<?php echo $month; ?>">
        <input type="hidden" name="year" value="<?php echo $year; ?>">
        <input type="hidden" name="branch_id" value="<?php echo $branch_id_filter ?: ''; ?>">
        <div class="col-md-4">
          <label class="form-label subtle-label">Month</label>
          <select name="expired_period" class="form-select">
            <option value="">Select month</option>
            <?php foreach($expired_months as $p): ?>
              <?php $val = sprintf('%04d-%02d', $p['year'], $p['month']); ?>
              <option value="<?php echo h($val); ?>" <?php echo $expired_period === $val ? 'selected' : ''; ?>>
                <?php echo h($p['label']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-secondary" type="submit">Apply</button>
        </div>
      </form>
    </div>
    <?php if (!empty($targets_expired)): ?>
    <div class="table-responsive">
      <table class="table table-sm mb-0 list-table">
        <thead>
          <tr>
            <th>Branch</th>
            <th>Designation</th>
            <th>Month</th>
            <th>Year</th>
            <th>Sales Target</th>
            <th>Bonus Amount</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($targets_expired as $t): ?>
          <tr>
            <td><?php echo h($t['branch_name']); ?></td>
            <td><?php echo h($t['designation']); ?></td>
            <td><?php echo h($month_names[(int)$t['month']] ?? $t['month']); ?></td>
            <td><?php echo h($t['year']); ?></td>
            <td><?php echo number_format($t['sales_target'], 2); ?></td>
            <td><?php echo number_format($t['bonus_amount'], 2); ?></td>
            <td>
              <form method="post" class="d-inline" onsubmit="return confirm('Delete this target?');">
                <input type="hidden" name="delete_id" value="<?php echo (int)$t['id']; ?>">
                <input type="hidden" name="expired_period" value="<?php echo h($expired_period); ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <div class="card-body">
        <p class="mb-0 text-muted">Please select a month to view expired targets.</p>
      </div>
    <?php endif; ?>
  </div>

</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// dynamic designation rows in targets sidebar
function addDesignationRow(values) {
  var container = document.getElementById('designationRows');
  if (!container) return;
  var row = document.createElement('div');
  row.className = 'row g-2 align-items-end mb-2 designation-row';
  row.innerHTML = ''
    + '<div class="col-md-4">'
    + '  <select name="designation[]" class="form-select">'
    + '    <option value="">Select designation</option>'
    + '    <?php foreach($allowed_designations as $d): ?>'
    + '      <option value="<?php echo h($d); ?>"><?php echo h($d); ?></option>'
    + '    <?php endforeach; ?>'
    + '  </select>'
    + '</div>'
    + '<div class="col-md-3">'
    + '  <input type="number" step="0.01" name="bonus1[]" class="form-control" placeholder="Bonus 1">'
    + '</div>'
    + '<div class="col-md-3">'
    + '  <input type="number" step="0.01" name="bonus2[]" class="form-control" placeholder="Bonus 2">'
    + '</div>'
    + '<div class="col-md-2 text-end">'
    + '  <button type="button" class="btn btn-outline-danger btn-sm designation-remove">Remove</button>'
    + '</div>';
  container.appendChild(row);
  if (values) {
    if (values.designation) {
      row.querySelector('select[name="designation[]"]').value = values.designation;
    }
    if (values.bonus1 !== undefined) {
      row.querySelector('input[name="bonus1[]"]').value = values.bonus1;
    }
    if (values.bonus2 !== undefined) {
      row.querySelector('input[name="bonus2[]"]').value = values.bonus2;
    }
  }
}

document.addEventListener('click', function (e) {
  if (e.target.closest('#addDesignationRow')) {
    addDesignationRow();
    return;
  }
  var removeBtn = e.target.closest('.designation-remove');
  if (removeBtn) {
    var row = removeBtn.closest('.designation-row');
    if (row) row.remove();
    return;
  }

  // open sidebar for "Add Targets"
  var addBtn = e.target.closest('#addTargetsBtn');
  if (addBtn) {
    var form = document.querySelector('#targetsSidebar form');
    if (form) form.reset();
    document.getElementById('group_edit_id').value = '0';
    document.querySelector('#targetsSidebar .offcanvas-title').textContent = 'Add Targets';
    document.getElementById('t_month').value = '<?php echo $month; ?>';
    document.getElementById('t_year').value = '<?php echo $year; ?>';
    document.getElementById('target_branch_id').value = '<?php echo $branch_id_filter ?: ''; ?>';
    var container = document.getElementById('designationRows');
    if (container) {
      container.innerHTML = '';
      addDesignationRow();
    }
    return;
  }

  // edit existing targets row
  var editBtn = e.target.closest('.target-edit-btn');
  if (editBtn) {
    var form = document.querySelector('#targetsSidebar form');
    if (form) form.reset();
    document.getElementById('group_edit_id').value = editBtn.getAttribute('data-id') || '0';
    document.querySelector('#targetsSidebar .offcanvas-title').textContent = 'Edit Targets';
    document.getElementById('t_month').value = editBtn.getAttribute('data-month') || '';
    document.getElementById('t_year').value = editBtn.getAttribute('data-year') || '';
    document.getElementById('target_branch_id').value = editBtn.getAttribute('data-branch-id') || '';
    document.getElementById('sales_target1').value = editBtn.getAttribute('data-target1') || '';
    document.getElementById('sales_target2').value = editBtn.getAttribute('data-target2') || '';

    var container2 = document.getElementById('designationRows');
    if (container2) {
      container2.innerHTML = '';
      addDesignationRow({
        designation: editBtn.getAttribute('data-designation') || '',
        bonus1: editBtn.getAttribute('data-bonus1') || '',
        bonus2: editBtn.getAttribute('data-bonus2') || ''
      });
    }

    var offcanvasEl = document.getElementById('targetsSidebar');
    if (offcanvasEl && window.bootstrap) {
      var oc = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
      oc.show();
    }
  }
});

// initialise with one empty row
document.addEventListener('DOMContentLoaded', function () {
  if (document.getElementById('designationRows') && !document.querySelector('.designation-row')) {
    addDesignationRow();
  }
});
</script>
</body>
</html>

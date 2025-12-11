<?php
require_once "config.php";
require_login();

if (($_SESSION['role'] ?? '') !== 'admin') {
    die("Access denied");
}

$BACKUP_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($BACKUP_DIR)) {
    mkdir($BACKUP_DIR, 0777, true);
}

function db_backup_path(string $type, string $timestamp): string {
    global $BACKUP_DIR;
    return $BACKUP_DIR . DIRECTORY_SEPARATOR . "{$type}_{$timestamp}.sql";
}

function run_backup(string $type): ?string {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;
    $timestamp = date('Ymd_His');
    $filepath = db_backup_path($type, $timestamp);

    $mysqldump = 'C:/xampp/mysql/bin/mysqldump.exe';
    if (!file_exists($mysqldump)) {
        $mysqldump = 'mysqldump';
    }

    $passPart = $DB_PASS !== '' ? " -p\"{$DB_PASS}\"" : '';
    $cmd = "\"{$mysqldump}\" -h\"{$DB_HOST}\" -u\"{$DB_USER}\"{$passPart} \"{$DB_NAME}\" > \"{$filepath}\"";

    // suppress output; shell_exec returns null on failure on some setups, but file existence is our check
    shell_exec($cmd);

    if (file_exists($filepath) && filesize($filepath) > 0) {
        log_activity(
            $GLOBALS['mysqli'],
            'db_backup',
            ucfirst($type) . " backup created ({$timestamp})",
            ['type' => $type, 'file' => basename($filepath)],
            false
        );
        return basename($filepath);
    }
    return null;
}

function run_restore(string $filename): bool {
    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $BACKUP_DIR;
    $filepath = realpath($BACKUP_DIR . DIRECTORY_SEPARATOR . $filename);
    if ($filepath === false || !is_file($filepath)) {
        return false;
    }

    $mysql = 'C:/xampp/mysql/bin/mysql.exe';
    if (!file_exists($mysql)) {
        $mysql = 'mysql';
    }

    $passPart = $DB_PASS !== '' ? " -p\"{$DB_PASS}\"" : '';
    $cmd = "\"{$mysql}\" -h\"{$DB_HOST}\" -u\"{$DB_USER}\"{$passPart} \"{$DB_NAME}\" < \"{$filepath}\"";
    shell_exec($cmd);

    log_activity(
        $GLOBALS['mysqli'],
        'db_restore',
        "Database restored from {$filename}",
        ['file' => $filename],
        false
    );
    return true;
}

// auto daily / weekly backups when visiting this page
function ensure_scheduled_backups(): void {
    global $BACKUP_DIR;
    $files = glob($BACKUP_DIR . DIRECTORY_SEPARATOR . '*.sql') ?: [];
    $lastDaily = 0;
    $lastWeekly = 0;
    $dailyCutoff = strtotime('-2 months');
    $weeklyCutoff = strtotime('-3 months');
    foreach ($files as $file) {
        $name = basename($file);
        if (preg_match('/^(daily|weekly)_(\d{8})_(\d{6})\.sql$/', $name, $m)) {
            $ts = strtotime($m[2] . $m[3]);
            if ($m[1] === 'daily') {
                // delete daily backups older than 2 months
                if ($ts < $dailyCutoff) {
                    @unlink($file);
                    continue;
                }
                if ($ts > $lastDaily) {
                    $lastDaily = $ts;
                }
            } elseif ($m[1] === 'weekly') {
                // delete weekly backups older than 3 months
                if ($ts < $weeklyCutoff) {
                    @unlink($file);
                    continue;
                }
                if ($ts > $lastWeekly) {
                    $lastWeekly = $ts;
                }
            }
        }
    }

    $today = strtotime(date('Y-m-d'));
    if ($lastDaily < $today) {
        run_backup('daily');
    }

    $oneWeekAgo = strtotime('-7 days');
    if ($lastWeekly < $oneWeekAgo) {
        run_backup('weekly');
    }
}

ensure_scheduled_backups();

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['manual_backup'])) {
        $file = run_backup('manual');
        if ($file) {
            $msg = "Manual backup created: {$file}";
        } else {
            $error = "Failed to create backup. Please check mysqldump configuration.";
        }
    } elseif (isset($_POST['restore']) && !empty($_POST['backup_file'])) {
        $file = basename($_POST['backup_file']);
        if (run_restore($file)) {
            $msg = "Database restored from backup: {$file}";
        } else {
            $error = "Failed to restore from backup file.";
        }
    }
}

// list backups
$backup_files = [];
foreach (glob($BACKUP_DIR . DIRECTORY_SEPARATOR . '*.sql') ?: [] as $path) {
    $name = basename($path);
    $type = 'manual';
    $created = null;
    if (preg_match('/^(daily|weekly|manual)_(\d{8})_(\d{6})\.sql$/', $name, $m)) {
        $type = $m[1];
        $created = DateTime::createFromFormat('Ymd_His', $m[2] . '_' . $m[3]);
    }
    $backup_files[] = [
        'name' => $name,
        'type' => ucfirst($type),
        'created_at' => $created ? $created->format('Y-m-d H:i:s') : '',
        'size' => filesize($path),
    ];
}
usort($backup_files, function ($a, $b) {
    return strcmp($b['name'], $a['name']);
});
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Backups - Essentia HR</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body class="app-shell">
<?php include "navbar.php"; ?>
<main class="container app-main">
  <div class="page-title">
    <span class="page-title-icon">BK</span>
    <span class="page-title-text">Backups</span>
  </div>

  <?php if ($msg): ?><div class="alert alert-success"><?php echo h($msg); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php endif; ?>

  <div class="card mb-4">
    <div class="card-header">Backup Controls</div>
    <div class="card-body">
      <form method="post" class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" type="submit" name="manual_backup" value="1">Take manual backup</button>
        <span class="text-muted small align-self-center">Daily and weekly backups are created automatically when this page is opened.</span>
      </form>
    </div>
  </div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm mb-0 list-table">
        <thead>
          <tr>
            <th>Type</th>
            <th>File</th>
            <th>Created</th>
            <th>Size</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($backup_files)): ?>
          <tr>
            <td colspan="5" class="text-center text-muted">No backups yet.</td>
          </tr>
          <?php else: ?>
          <?php foreach ($backup_files as $b): ?>
          <tr>
            <td><?php echo h($b['type']); ?></td>
            <td><?php echo h($b['name']); ?></td>
            <td><?php echo h($b['created_at']); ?></td>
            <td><?php echo number_format($b['size'] / 1024, 1); ?> KB</td>
            <td class="text-nowrap">
              <a class="btn btn-sm btn-outline-primary me-1" href="<?php echo 'backups/' . rawurlencode($b['name']); ?>" download>Download</a>
              <form method="post" class="d-inline" onsubmit="return confirm('Restore database from this backup? This will overwrite current data.');">
                <input type="hidden" name="backup_file" value="<?php echo h($b['name']); ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit" name="restore" value="1">Restore</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
  </div>
</div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // auto dismiss success alerts after 5 seconds
  window.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert-success');
    if (!alerts.length) return;
    setTimeout(() => {
      alerts.forEach(el => el.classList.add('fade'));
      setTimeout(() => alerts.forEach(el => el.remove()), 400);
    }, 5000);
  });
</script>
</body>
</html>

<?php
require_once 'config.php';

// Reuse the root config values (uppercase) for PDO access
$pdo = new PDO(
    "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

function fetchPending($pdo) {
    $sql = "SELECT p.*, b.name AS branch_name FROM pending_employees p LEFT JOIN branches b ON b.id=p.branch_id WHERE p.status='pending' ORDER BY p.created_at DESC";
    return $pdo->query($sql)->fetchAll();
}

function approvePending($pdo, $id) {
    $pdo->beginTransaction();
    $st = $pdo->prepare("SELECT * FROM pending_employees WHERE id=?");
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { $pdo->rollBack(); return 'Not found'; }
    $st = $pdo->prepare("INSERT INTO employees (employee_code,name,branch_id,designation_id,contact,basic_salary,commission,joining_date,face_image_path,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,'active',NOW())");
    $st->execute([
        $row['employee_code'],$row['name'],$row['branch_id'],$row['designation_id'],$row['contact'],$row['basic_salary'],$row['commission'],$row['joining_date'],$row['face_image_path']
    ]);
    $pdo->prepare("DELETE FROM pending_employees WHERE id=?")->execute([$id]);
    $pdo->commit();
    return null;
}

function deletePending($pdo, $id) {
    $pdo->prepare("DELETE FROM pending_employees WHERE id=?")->execute([$id]);
}

if (isset($_POST['approve'])) {
    $err = approvePending($pdo, (int)$_POST['approve']);
    $msg = $err ? $err : 'Approved';
}
if (isset($_POST['delete'])) {
    deletePending($pdo, (int)$_POST['delete']);
    $msg = 'Deleted';
}
$rows = fetchPending($pdo);
?>
<!DOCTYPE html>
<html><head><title>Pending Employees</title></head>
<body>
<h1>Pending Employees</h1>
<?php if (!empty($msg)) echo "<p>$msg</p>"; ?>
<table border="1" cellpadding="6">
<tr><th>Code</th><th>Name</th><th>Branch</th><th>Designation</th><th>Contact</th><th>Basic Salary</th><th>Commission</th><th>Joining</th><th>Status</th><th>Image</th><th>Actions</th></tr>
<?php foreach ($rows as $r): ?>
<tr>
<td><?=htmlspecialchars($r['employee_code'])?></td>
<td><?=htmlspecialchars($r['name'])?></td>
<td><?=htmlspecialchars($r['branch_name'])?></td>
<td><?=htmlspecialchars($r['designation_id'])?></td>
<td><?=htmlspecialchars($r['contact'])?></td>
<td><?=htmlspecialchars($r['basic_salary'])?></td>
<td><?=htmlspecialchars($r['commission'])?></td>
<td><?=htmlspecialchars($r['joining_date'])?></td>
<td><?=htmlspecialchars($r['status'])?></td>
<td><?php if ($r['face_image_path']): ?><img src="<?=$r['face_image_path']?>" width="80"><?php endif;?></td>
<td>
<form method="post" style="display:inline"><button name="approve" value="<?=$r['id']?>">Approve</button></form>
<form method="post" style="display:inline"><button name="delete" value="<?=$r['id']?>">Delete</button></form>
</td>
</tr>
<?php endforeach;?>
</table>
</body></html>

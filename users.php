<?php
require_once "config.php";
require_login();
if (($_SESSION['role'] ?? '') !== 'admin') {
    die("Access denied");
}

// load branches for branch users
$branches = [];
$res = $mysqli->query("SELECT id, name FROM branches ORDER BY name ASC");
while ($row = $res->fetch_assoc()) {
    $branches[$row['id']] = $row['name'];
}

$edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$edit_user = null;
if ($edit_id > 0) {
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// handle add / update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $id = intval($_POST['id'] ?? 0);
    $username = trim($_POST['username']);
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'admin';
    $branch_id = isset($_POST['branch_id']) && $_POST['branch_id'] !== '' ? intval($_POST['branch_id']) : null;

    if ($username !== '' && in_array($role, ['admin', 'branch'], true)) {
        if ($id > 0) {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE users SET username=?, role=?, branch_id=?, password_hash=? WHERE id=?");
                $stmt->bind_param("ssisi", $username, $role, $branch_id, $hash, $id);
            } else {
                $stmt = $mysqli->prepare("UPDATE users SET username=?, role=?, branch_id=? WHERE id=?");
                $stmt->bind_param("ssii", $username, $role, $branch_id, $id);
            }
            $stmt->execute();
            $stmt->close();
        } else {
            if ($password === '') {
                $error = "Password is required for new users.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("INSERT INTO users (username, password_hash, role, branch_id) VALUES (?,?,?,?)");
                $stmt->bind_param("sssi", $username, $hash, $role, $branch_id);
                if (!$stmt->execute()) {
                    $error = "Error creating user. Username may already exist.";
                }
                $stmt->close();
            }
        }
        if (empty($error)) {
            header("Location: users.php");
            exit;
        }
    } else {
        $error = "Username and valid role are required.";
    }
}

$users = $mysqli->query("SELECT u.*, b.name AS branch_name
                         FROM users u
                         LEFT JOIN branches b ON b.id=u.branch_id
                         ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Users - Essentia HR</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app-shell">
<?php include "navbar.php"; ?>
<main class="container app-main">
  <div class="page-title">
    <span class="page-title-icon">US</span>
    <span class="page-title-text">Users</span>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo h($error); ?></div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-header"><?php echo $edit_user ? 'Edit User' : 'Add User'; ?></div>
    <div class="card-body">
      <form method="post" class="row g-3">
        <input type="hidden" name="id" value="<?php echo h($edit_user['id'] ?? ''); ?>">
        <div class="col-md-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required value="<?php echo h($edit_user['username'] ?? ''); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label"><?php echo $edit_user ? 'New Password (optional)' : 'Password'; ?></label>
          <input type="password" name="password" class="form-control" <?php echo $edit_user ? '' : 'required'; ?>>
        </div>
        <div class="col-md-3">
          <label class="form-label">Role</label>
          <select name="role" class="form-select">
            <option value="admin" <?php echo (isset($edit_user['role']) && $edit_user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
            <option value="branch" <?php echo (isset($edit_user['role']) && $edit_user['role'] === 'branch') ? 'selected' : ''; ?>>Branch</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Branch (for branch users)</label>
          <select name="branch_id" class="form-select">
            <option value="">None</option>
            <?php foreach($branches as $id=>$nm): ?>
              <option value="<?php echo $id; ?>" <?php echo isset($edit_user['branch_id']) && (int)$edit_user['branch_id'] === $id ? 'selected' : ''; ?>><?php echo h($nm); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3 align-self-end">
          <button class="btn btn-primary" type="submit"><?php echo $edit_user ? 'Update User' : 'Create User'; ?></button>
          <?php if ($edit_user): ?>
            <a href="users.php" class="btn btn-secondary ms-2">Cancel</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-bordered table-sm mb-0">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Branch</th>
            <th>Created At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($u = $users->fetch_assoc()): ?>
          <tr>
            <td><?php echo h($u['id']); ?></td>
            <td><?php echo h($u['username']); ?></td>
            <td><?php echo h($u['role']); ?></td>
            <td><?php echo h($u['branch_name']); ?></td>
            <td><?php echo h($u['created_at']); ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="users.php?edit_id=<?php echo $u['id']; ?>">Edit / Reset Password</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


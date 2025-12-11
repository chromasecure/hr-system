<?php
require_once "config.php";

// Auto-create default admin if none exists
$check = $mysqli->query("SELECT COUNT(*) AS c FROM users");
if ($check && ($row = $check->fetch_assoc()) && $row['c'] == 0) {
    $password_hash = password_hash("admin123", PASSWORD_DEFAULT);
    $mysqli->query("INSERT INTO users (username, password_hash, role, branch_id) VALUES ('admin', '{$password_hash}', 'admin', NULL)");
    $msg = "Default admin created. Username: admin, Password: admin123";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $mysqli->prepare("SELECT id, password_hash, role, branch_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($uid, $hash, $role, $branch_id);
    if ($stmt->fetch() && password_verify($password, $hash)) {
        $_SESSION['user_id'] = $uid;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['branch_id'] = $branch_id;
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
    $stmt->close();
}
$theme = load_theme_settings();
$hero_image = $theme['login_hero'] ?? '';
$login_bg = $theme['login_bg'] ?? '';
$primary = $theme['primary_color'] ?? '#2563eb';
$accent = $theme['accent_color'] ?? '#1d4ed8';
$hero_style = $hero_image
    ? "--hero-img:url('".h($hero_image)."');"
    : "--hero-img: linear-gradient(135deg, {$primary}, {$accent});";
$bg_style = $login_bg ? "style=\"background-image:url('".h($login_bg)."'); background-size:cover;\"" : "";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Essentia HR Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="login-shell" <?php echo $bg_style; ?>>
  <div class="login-frame">
    <div class="login-form-panel">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="login-logo">Essentia HR</div>
      </div>
      <h4 class="mb-3"><?php echo h($theme['login_title'] ?? 'Welcome back'); ?></h4>
      <p class="text-muted mb-4"><?php echo h($theme['login_subtitle'] ?? 'Manage your HR in one place.'); ?></p>

      <?php if (!empty($msg)): ?>
        <div class="alert alert-info"><?php echo h($msg); ?></div>
      <?php endif; ?>
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo h($error); ?></div>
      <?php endif; ?>

      <form method="post" class="mt-2">
        <div class="mb-3">
          <label class="form-label subtle-label">Username</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label subtle-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Login</button>
      </form>
      <div class="mt-4 text-muted small">
        &copy; <?php echo date('Y'); ?> Essentia HR
      </div>
    </div>
    <div class="login-hero-panel" style="<?php echo $hero_style; ?>">
      <div class="login-hero-overlay">
        <div class="login-hero-title">
          Start your journey by one click,<br> explore beautiful workdays!
        </div>
      </div>
    </div>
  </div>
</body>
</html>

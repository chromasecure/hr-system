<?php
require_once "config.php";
require_login();
if (($_SESSION['role'] ?? '') !== 'admin') {
    die("Access denied");
}

$theme = load_theme_settings();
$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_theme'])) {
    $theme['primary_color'] = $_POST['primary_color'] ?? $theme['primary_color'];
    $theme['accent_color'] = $_POST['accent_color'] ?? $theme['accent_color'];
    $theme['bg_soft'] = $_POST['bg_soft'] ?? $theme['bg_soft'];
    $theme['card_bg'] = $_POST['card_bg'] ?? $theme['card_bg'];
    $theme['login_title'] = trim($_POST['login_title'] ?? $theme['login_title']);
    $theme['login_subtitle'] = trim($_POST['login_subtitle'] ?? $theme['login_subtitle']);

    $uploads_dir = __DIR__ . '/uploads';
    if (!is_dir($uploads_dir)) {
        mkdir($uploads_dir, 0777, true);
    }

    foreach (['login_hero' => 'hero', 'login_bg' => 'bg'] as $field => $suffix) {
        if (!empty($_FILES[$field]['name']) && is_uploaded_file($_FILES[$field]['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
                $new_name = "theme_{$suffix}_" . time() . "." . $ext;
                $dest = $uploads_dir . '/' . $new_name;
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
                    $theme[$field] = 'uploads/' . $new_name;
                }
            }
        }
    }

    $path = __DIR__ . '/theme_settings.json';
    if (file_put_contents($path, json_encode($theme, JSON_PRETTY_PRINT))) {
        $msg = "Theme settings saved.";
    } else {
        $error = "Failed to save settings.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Settings - Essentia HR</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body class="app-shell">
<?php include "navbar.php"; ?>
<main class="container app-main">
  <div class="page-header mb-3">
    <div class="page-title mb-0">
      <span class="page-title-icon">ST</span>
      <span class="page-title-text">Theme & Login Settings</span>
    </div>
  </div>
  <?php if ($msg): ?><div class="alert alert-success"><?php echo h($msg); ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?php echo h($error); ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="card p-3">
    <input type="hidden" name="save_theme" value="1">
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Primary color</label>
        <input type="color" name="primary_color" class="form-control form-control-color" value="<?php echo h($theme['primary_color']); ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Accent color</label>
        <input type="color" name="accent_color" class="form-control form-control-color" value="<?php echo h($theme['accent_color']); ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Background (soft)</label>
        <input type="color" name="bg_soft" class="form-control form-control-color" value="<?php echo h($theme['bg_soft']); ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Card background</label>
        <input type="color" name="card_bg" class="form-control form-control-color" value="<?php echo h($theme['card_bg']); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Login title</label>
        <input type="text" name="login_title" class="form-control" value="<?php echo h($theme['login_title']); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Login subtitle</label>
        <input type="text" name="login_subtitle" class="form-control" value="<?php echo h($theme['login_subtitle']); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Login hero image</label>
        <input type="file" name="login_hero" class="form-control" accept="image/*">
        <?php if (!empty($theme['login_hero'])): ?>
          <div class="mt-2">
            <img src="<?php echo h($theme['login_hero']); ?>" alt="Hero" class="img-fluid rounded">
          </div>
        <?php endif; ?>
      </div>
      <div class="col-md-6">
        <label class="form-label">Login background image</label>
        <input type="file" name="login_bg" class="form-control" accept="image/*">
        <?php if (!empty($theme['login_bg'])): ?>
          <div class="mt-2">
            <img src="<?php echo h($theme['login_bg']); ?>" alt="Background" class="img-fluid rounded">
          </div>
        <?php endif; ?>
      </div>
      <div class="col-12">
        <button class="btn btn-primary" type="submit">Save Settings</button>
      </div>
    </div>
  </form>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

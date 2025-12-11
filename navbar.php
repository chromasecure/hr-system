<?php
require_once "config.php";

$nav_branches = [];
if (isset($_SESSION['user_id'])) {
    $res = $mysqli->query("SELECT id, name FROM branches ORDER BY name ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $nav_branches[] = $row;
        }
    }
}
$role = $_SESSION['role'] ?? '';
$theme = load_theme_settings();

// define links
$main_links = [
    ['href' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => '🏠'],
];
if ($role === 'branch') {
    $main_links[] = ['href' => 'attendance.php', 'label' => 'Attendance', 'icon' => '🗓'];
    $main_links[] = ['href' => 'payroll.php', 'label' => 'Payroll', 'icon' => '💳'];
} else {
    $main_links[] = ['href' => 'attendance.php', 'label' => 'Attendance', 'icon' => '🗓'];
    $main_links[] = ['href' => 'payroll.php', 'label' => 'Payroll', 'icon' => '💳'];
    $main_links[] = ['href' => 'targets.php', 'label' => 'Targets', 'icon' => '🎯'];
    $main_links[] = ['href' => 'employees.php', 'label' => 'Employees', 'icon' => '👥'];
    $main_links[] = ['href' => 'branches.php', 'label' => 'Branches', 'icon' => '📍'];
    $main_links[] = ['href' => 'csv_tools.php', 'label' => 'CSV Tools', 'icon' => '⬇️'];
}

$settings_links = [];
if ($role === 'admin') {
    $settings_links[] = ['href' => 'users.php', 'label' => 'Users', 'icon' => '👤'];
    $settings_links[] = ['href' => 'designations.php', 'label' => 'Designations', 'icon' => '🏷'];
    $settings_links[] = ['href' => 'backup.php', 'label' => 'Backups', 'icon' => '💾'];
    $settings_links[] = ['href' => 'activity.php', 'label' => 'Activity', 'icon' => '📜'];
    $settings_links[] = ['href' => 'settings.php', 'label' => 'Settings', 'icon' => '⚙'];
}
?>
<style>
:root {
  --primary-color: <?php echo h($theme['primary_color'] ?? '#2563eb'); ?>;
  --primary-strong: <?php echo h($theme['accent_color'] ?? '#1d4ed8'); ?>;
  --bg-soft: <?php echo h($theme['bg_soft'] ?? '#f3f4ff'); ?>;
  --card-bg: <?php echo h($theme['card_bg'] ?? '#ffffff'); ?>;
  --text-primary: #0f172a;
  --muted: #9ca3af;
}
</style>
<nav class="sidebar d-flex flex-column">
  <div class="sidebar-inner d-flex flex-column h-100">
    <div class="sidebar-top px-3 py-3">
      <div class="sidebar-brand">Essentia HR</div>
      <div class="sidebar-user text-muted small mt-1">
        <?php echo h($_SESSION['username'] ?? ''); ?> (<?php echo h($role); ?>)
      </div>
    </div>

    <div class="sidebar-section px-3">
      <div class="sidebar-section-label">MAIN</div>
      <ul class="nav flex-column">
        <?php foreach ($main_links as $link): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo $link['href']; ?>">
            <span class="nav-icon"><?php echo $link['icon']; ?></span>
            <span class="nav-text"><?php echo h($link['label']); ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <?php if (!empty($settings_links)): ?>
    <div class="sidebar-section px-3 mt-3">
      <div class="sidebar-section-label">SETTINGS</div>
      <ul class="nav flex-column">
        <?php foreach ($settings_links as $link): ?>
        <li class="nav-item">
          <a class="nav-link" href="<?php echo $link['href']; ?>">
            <span class="nav-icon"><?php echo $link['icon']; ?></span>
            <span class="nav-text"><?php echo h($link['label']); ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

  </div>
</nav>

<!-- Floating logout bottom-left -->
<div class="logout-floating">
  <a href="logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
</div>

<script>
  window.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert');
    if (!alerts.length) return;
    setTimeout(() => {
      alerts.forEach(el => {
        el.classList.add('fade');
        setTimeout(() => el.remove(), 400);
      });
    }, 5000);
  });
</script>

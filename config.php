<?php
// Database configuration
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "essentia_hr";

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

// ensure optional columns exist
@$mysqli->query("ALTER TABLE employees ADD COLUMN IF NOT EXISTS left_date DATE NULL");

session_start();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: index.php");
        exit;
    }
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// theme settings loader (stored in theme_settings.json)
function load_theme_settings(): array {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $defaults = [
        'primary_color' => '#2563eb',
        'accent_color' => '#1d4ed8',
        'bg_soft' => '#f3f4ff',
        'card_bg' => '#ffffff',
        'login_hero' => '',
        'login_bg' => '',
        'login_title' => 'Welcome back',
        'login_subtitle' => 'Manage your HR in one place.',
    ];
    $path = __DIR__ . '/theme_settings.json';
    if (file_exists($path)) {
        $data = json_decode(file_get_contents($path), true);
        if (is_array($data)) {
            $cache = array_merge($defaults, $data);
            return $cache;
        }
    }
    $cache = $defaults;
    return $cache;
}

/**
 * Record a high-level activity (import, delete, etc.) for auditing and undo.
 *
 * @param mysqli $mysqli
 * @param string $action_type   Short machine name, e.g. 'import_sales_csv'
 * @param string $description   Human readable description
 * @param array  $details       Extra data for undo, stored as JSON
 * @param bool   $can_undo      Whether this action supports undo
 */
function log_activity(mysqli $mysqli, string $action_type, string $description, array $details = [], bool $can_undo = false): void {
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $json = json_encode($details, JSON_UNESCAPED_UNICODE);
    $can_undo_int = $can_undo ? 1 : 0;
    $stmt = $mysqli->prepare("INSERT INTO activity_log (user_id, action_type, description, details, can_undo) VALUES (?,?,?,?,?)");
    // user_id may be null
    if ($user_id === null) {
        $null = null;
        $stmt->bind_param("isssi", $null, $action_type, $description, $json, $can_undo_int);
    } else {
        $stmt->bind_param("isssi", $user_id, $action_type, $description, $json, $can_undo_int);
    }
    $stmt->execute();
    $stmt->close();
}

/**
 * Automatically promote employees with designation "Internee" to "Sales Man"
 * once they have completed 30 days from their joining date.
 */
function auto_promote_internees(mysqli $mysqli): void {
    $cutoff = date('Y-m-d', strtotime('-30 days'));
    $stmt = $mysqli->prepare("UPDATE employees
                              SET designation='Sales Man'
                              WHERE designation='Internee'
                                AND joining_date IS NOT NULL
                                AND joining_date <> '0000-00-00'
                                AND joining_date <= ?");
    if ($stmt) {
        $stmt->bind_param("s", $cutoff);
        $stmt->execute();
        $stmt->close();
    }
}

// run lightweight auto-promotion on every request
auto_promote_internees($mysqli);
?>

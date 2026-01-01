<?php
/**
 * JEMA Water Management System
 * Configuration File
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jema_water_system');

// Application Settings
define('APP_NAME', 'JEMA Water Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/jema-water');

// Timezone
date_default_timezone_set('Africa/Mogadishu');

// Session Configuration
ini_set('session.gc_maxlifetime', 86400); // 24 hours
session_set_cookie_params(['lifetime' => 86400]);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($db->connect_error) {
        die(json_encode(['error' => 'Database connection failed: ' . $db->connect_error]));
    }

    $db->set_charset("utf8mb4");
} catch (Exception $e) {
    die(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
}

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Helper function to escape output
function escape($data) {
    if (is_array($data)) {
        return array_map('escape', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Helper function to get settings
function getSetting($key, $default = null) {
    global $db;
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['setting_value'] : $default;
}

// Helper function to log actions
function logAction($action, $table, $record_id, $changes = null) {
    global $db;
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $stmt = $db->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, changes) VALUES (?, ?, ?, ?, ?)");
    $changes_json = $changes ? json_encode($changes) : null;
    $stmt->bind_param("issss", $user_id, $action, $table, $record_id, $changes_json);
    $stmt->execute();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

// Require admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
}

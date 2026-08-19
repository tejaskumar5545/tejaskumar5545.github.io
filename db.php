<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'engihub');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

function sanitize($conn, $data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['student_id']) || isset($_SESSION['admin_id']);
}

function isStudent() {
    return isset($_SESSION['student_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isStudent()) {
        header("Location: login.php");
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header("Location: login.php");
        exit;
    }
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

function generateOTP($length = 6) {
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= random_int(0, 9);
    }
    return $otp;
}

function rateLimit($key, $maxAttempts = 5, $windowSeconds = 300) {
    $cacheKey = 'rate_' . $key;
    if (!isset($_SESSION[$cacheKey])) {
        $_SESSION[$cacheKey] = ['count' => 0, 'first_at' => time()];
    }
    $cache = &$_SESSION[$cacheKey];
    if (time() - $cache['first_at'] > $windowSeconds) {
        $cache = ['count' => 0, 'first_at' => time()];
    }
    $cache['count']++;
    return $cache['count'] <= $maxAttempts;
}

function getRateLimitRemaining($key, $maxAttempts = 5, $windowSeconds = 300) {
    $cacheKey = 'rate_' . $key;
    if (!isset($_SESSION[$cacheKey])) return $maxAttempts;
    $cache = $_SESSION[$cacheKey];
    if (time() - $cache['first_at'] > $windowSeconds) return $maxAttempts;
    return max(0, $maxAttempts - $cache['count']);
}

<?php
require_once '../db.php';
require_once __DIR__ . '/init-admin-tables.php';

header('Content-Type: application/json');

if (isAdmin()) {
    echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security validation failed.']);
    exit;
}

$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password'] ?? '';
$remember   = !empty($_POST['remember']);

if (empty($identifier) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if (isset($_SESSION['admin_lockout_until']) && time() < $_SESSION['admin_lockout_until']) {
    $remaining = $_SESSION['admin_lockout_until'] - time();
    $minutes   = ceil($remaining / 60);
    echo json_encode(['success' => false, 'message' => "Account temporarily locked. Try again in {$minutes} minute" . ($minutes !== 1 ? 's' : '') . "."]);
    exit;
}

if (!rateLimit('admin_login', 5, 900)) {
    $_SESSION['admin_lockout_until'] = time() + 900;
    echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Account locked for 15 minutes.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, full_name, username, email, password_hash, role, account_status, locked_until FROM admin_users WHERE (email = ? OR username = ?) LIMIT 1");
$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    logFailedAttempt($conn, $identifier);
    echo json_encode(['success' => false, 'message' => 'Invalid login credentials.']);
    exit;
}

$admin = $result->fetch_assoc();
$stmt->close();

if ($admin['account_status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'Invalid login credentials.']);
    exit;
}

if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
    $remaining = strtotime($admin['locked_until']) - time();
    $minutes   = ceil($remaining / 60);
    echo json_encode(['success' => false, 'message' => "Account temporarily locked. Try again in {$minutes} minute" . ($minutes !== 1 ? 's' : '') . "."]);
    exit;
}

if (!password_verify($password, $admin['password_hash'])) {
    incrementFailedAttempts($conn, $admin['id'], $identifier);
    echo json_encode(['success' => false, 'message' => 'Invalid login credentials.']);
    exit;
}

if (!in_array($admin['role'], ['super_admin', 'admin', 'editor'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid login credentials.']);
    exit;
}

$conn->prepare("UPDATE admin_users SET failed_attempts = 0, locked_until = NULL WHERE id = ?")->execute([$admin['id']]);

if ($remember) {
    $token    = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expires  = date('Y-m-d H:i:s', time() + 30 * 86400);

    $ins = $conn->prepare("INSERT INTO admin_reset_tokens (admin_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))");
    $ins->bind_param("is", $admin['id'], $tokenHash);
    $ins->execute();
    $ins->close();

    setcookie('admin_remember', $token, time() + 30 * 86400, '/', '', isset($_SERVER['HTTPS']), true);
}

session_regenerate_id(true);
$_SESSION['admin_id']       = $admin['id'];
$_SESSION['admin_name']     = $admin['full_name'] ?: $admin['username'];
$_SESSION['admin_username'] = $admin['username'];
$_SESSION['admin_email']    = $admin['email'];
$_SESSION['admin_role']     = $admin['role'];
$_SESSION['login_ip']       = $_SERVER['REMOTE_ADDR'];
$_SESSION['login_time']     = time();
unset($_SESSION['admin_login_attempts'], $_SESSION['admin_lockout_until']);

$ip = $_SERVER['REMOTE_ADDR'];
$upd = $conn->prepare("UPDATE admin_users SET last_login = NOW(), last_ip = ? WHERE id = ?");
$upd->bind_param("si", $ip, $admin['id']);
$upd->execute();
$upd->close();

$log = $conn->prepare("INSERT INTO activity_logs (user_type, user_id, action, details, ip_address) VALUES ('admin', ?, 'login', 'Admin login successful', ?)");
$log->bind_param("is", $admin['id'], $ip);
$log->execute();
$log->close();

echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
exit;

function logFailedAttempt($conn, $identifier) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO admin_login_attempts (identifier, ip_address, success) VALUES (?, ?, 0)");
    $stmt->bind_param("ss", $identifier, $ip);
    $stmt->execute();
    $stmt->close();
}

function incrementFailedAttempts($conn, $adminId, $identifier) {
    $ip = $_SERVER['REMOTE_ADDR'];

    $stmt = $conn->prepare("UPDATE admin_users SET failed_attempts = failed_attempts + 1 WHERE id = ?");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $stmt->close();

    $r = $conn->prepare("SELECT failed_attempts FROM admin_users WHERE id = ?");
    $r->bind_param("i", $adminId);
    $r->execute();
    $res = $r->get_result();
    $row = $res->fetch_assoc();
    $r->close();

    if ($row['failed_attempts'] >= 10) {
        $lock = $conn->prepare("UPDATE admin_users SET locked_until = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?");
        $lock->bind_param("i", $adminId);
        $lock->execute();
        $lock->close();
    }

    $log = $conn->prepare("INSERT INTO admin_login_attempts (identifier, ip_address, success) VALUES (?, ?, 0)");
    $log->bind_param("ss", $identifier, $ip);
    $log->execute();
    $log->close();
}

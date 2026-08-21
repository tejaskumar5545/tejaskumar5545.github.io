<?php
require_once '../db.php';

if (isset($_SESSION['admin_id'])) {
    $adminId = $_SESSION['admin_id'];
    $ip = $_SERVER['REMOTE_ADDR'];

    $log = $conn->prepare("INSERT INTO activity_logs (user_type, user_id, action, details, ip_address) VALUES ('admin', ?, 'logout', 'Admin logout', ?)");
    $log->bind_param("is", $adminId, $ip);
    $log->execute();
    $log->close();
}

if (isset($_COOKIE['admin_remember'])) {
    $tokenHash = hash('sha256', $_COOKIE['admin_remember']);
    $del = $conn->prepare("DELETE FROM admin_reset_tokens WHERE token_hash = ?");
    $del->bind_param("s", $tokenHash);
    $del->execute();
    $del->close();
    setcookie('admin_remember', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();
session_start();
session_regenerate_id(true);

header("Location: login.php");
exit;

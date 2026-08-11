<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header("Location: login.php?error=1");
    exit;
}

$username = mysqli_real_escape_string($conn, $username);
$query = "SELECT * FROM admin WHERE username = '$username' LIMIT 1";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) === 1) {
    $admin = mysqli_fetch_assoc($result);
    if (password_verify($password, $admin['password'])) {
        $_SESSION['admin'] = $admin['username'];
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: dashboard.php");
        exit;
    }
}

header("Location: login.php?error=1");
exit;
?>

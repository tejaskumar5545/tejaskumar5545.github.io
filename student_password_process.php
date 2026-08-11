<?php
session_start();
if (!isset($_SESSION['student'])) {
    header("Location: student_login.php");
    exit;
}
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: student_profile.php");
    exit;
}

$student_id = $_SESSION['student_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    header("Location: student_profile.php?error=missing");
    exit;
}

if (strlen($new_password) < 6) {
    header("Location: student_profile.php?error=short");
    exit;
}

if ($new_password !== $confirm_password) {
    header("Location: student_profile.php?error=mismatch");
    exit;
}

$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM students WHERE id = $student_id"));

if (!password_verify($current_password, $student['password'])) {
    header("Location: student_profile.php?error=current_wrong");
    exit;
}

$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$hashed = mysqli_real_escape_string($conn, $hashed);

$query = "UPDATE students SET password='$hashed' WHERE id=$student_id";

if (mysqli_query($conn, $query)) {
    header("Location: student_profile.php?msg=password_updated");
    exit;
} else {
    header("Location: student_profile.php?error=1");
    exit;
}
?>

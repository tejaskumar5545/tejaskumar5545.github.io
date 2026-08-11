<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$semester = trim($_POST['semester'] ?? '');
$branch = trim($_POST['branch'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($full_name) || empty($email) || empty($semester) || empty($branch) || empty($password)) {
    header("Location: register.php?error=missing");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: register.php?error=invalid_email");
    exit;
}

if ($password !== $confirm_password) {
    header("Location: register.php?error=mismatch");
    exit;
}

if (strlen($password) < 6) {
    header("Location: register.php?error=missing");
    exit;
}

$email = mysqli_real_escape_string($conn, $email);
$check = mysqli_query($conn, "SELECT id FROM students WHERE email = '$email' LIMIT 1");
if (mysqli_num_rows($check) > 0) {
    header("Location: register.php?error=exists");
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$full_name = mysqli_real_escape_string($conn, $full_name);
$semester = mysqli_real_escape_string($conn, $semester);
$branch = mysqli_real_escape_string($conn, $branch);

$query = "INSERT INTO students (full_name, email, password, semester, branch) VALUES ('$full_name', '$email', '$hashed_password', '$semester', '$branch')";

if (mysqli_query($conn, $query)) {
    header("Location: student_login.php?success=registered");
    exit;
} else {
    header("Location: register.php?error=1");
    exit;
}
?>

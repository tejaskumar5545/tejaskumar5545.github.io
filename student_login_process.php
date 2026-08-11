<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: student_login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header("Location: student_login.php?error=missing");
    exit;
}

$email = mysqli_real_escape_string($conn, $email);
$query = "SELECT * FROM students WHERE email = '$email' LIMIT 1";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) === 1) {
    $student = mysqli_fetch_assoc($result);
    if (password_verify($password, $student['password'])) {
        $_SESSION['student'] = $student['full_name'];
        $_SESSION['student_id'] = $student['id'];
        $_SESSION['student_email'] = $student['email'];
        $_SESSION['student_semester'] = $student['semester'];
        $_SESSION['student_branch'] = $student['branch'];
        header("Location: student_dashboard.php");
        exit;
    }
}

header("Location: student_login.php?error=1");
exit;
?>

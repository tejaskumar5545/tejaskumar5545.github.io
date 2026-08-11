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
$full_name = trim($_POST['full_name'] ?? '');
$semester = trim($_POST['semester'] ?? '');
$branch = trim($_POST['branch'] ?? '');

if (empty($full_name) || empty($semester) || empty($branch)) {
    header("Location: student_profile.php?error=missing");
    exit;
}

$full_name = mysqli_real_escape_string($conn, $full_name);
$semester = mysqli_real_escape_string($conn, $semester);
$branch = mysqli_real_escape_string($conn, $branch);

$query = "UPDATE students SET full_name='$full_name', semester='$semester', branch='$branch' WHERE id=$student_id";

if (mysqli_query($conn, $query)) {
    $_SESSION['student'] = $full_name;
    $_SESSION['student_semester'] = $semester;
    $_SESSION['student_branch'] = $branch;
    header("Location: student_profile.php?msg=updated");
    exit;
} else {
    header("Location: student_profile.php?error=1");
    exit;
}
?>

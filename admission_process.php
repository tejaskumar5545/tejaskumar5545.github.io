<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admission.php");
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$dob = trim($_POST['dob'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$course = trim($_POST['course'] ?? '');
$branch = trim($_POST['branch'] ?? '');
$previous_school = trim($_POST['previous_school'] ?? '');
$previous_marks = trim($_POST['previous_marks'] ?? '');
$address = trim($_POST['address'] ?? '');

if (empty($full_name) || empty($email) || empty($phone) || empty($dob) || empty($gender) || empty($course) || empty($branch)) {
    header("Location: admission.php?error=missing");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: admission.php?error=invalid_email");
    exit;
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    header("Location: admission.php?error=invalid_phone");
    exit;
}

$full_name = mysqli_real_escape_string($conn, $full_name);
$email = mysqli_real_escape_string($conn, $email);
$phone = mysqli_real_escape_string($conn, $phone);
$dob = mysqli_real_escape_string($conn, $dob);
$gender = mysqli_real_escape_string($conn, $gender);
$course = mysqli_real_escape_string($conn, $course);
$branch = mysqli_real_escape_string($conn, $branch);
$previous_school = mysqli_real_escape_string($conn, $previous_school);
$previous_marks = mysqli_real_escape_string($conn, $previous_marks);
$address = mysqli_real_escape_string($conn, $address);

$query = "INSERT INTO admissions (full_name, email, phone, dob, gender, course, branch, previous_school, previous_marks, address) 
          VALUES ('$full_name', '$email', '$phone', '$dob', '$gender', '$course', '$branch', '$previous_school', '$previous_marks', '$address')";

if (mysqli_query($conn, $query)) {
    header("Location: admission.php?success=1");
    exit;
} else {
    header("Location: admission.php?error=1");
    exit;
}
?>

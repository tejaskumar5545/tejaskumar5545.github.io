<?php
require_once 'db.php';

if (isStudent()) { header("Location: dashboard.php"); exit; }
if (isAdmin()) { header("Location: admin/"); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit;
}

$errors = [];

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $errors[] = "Invalid security token. Please try again.";
}
if (!rateLimit('register', 5, 300)) {
    $errors[] = "Too many registration attempts. Please wait 5 minutes and try again.";
}

$full_name = sanitize($conn, $_POST['full_name'] ?? '');
$email     = sanitize($conn, $_POST['email'] ?? '');
$mobile    = sanitize($conn, $_POST['mobile'] ?? '');
$college   = sanitize($conn, $_POST['college_name'] ?? '');
$branch    = sanitize($conn, $_POST['branch'] ?? '');
$semester  = sanitize($conn, $_POST['semester'] ?? '');
$password  = $_POST['password'] ?? '';
$confirm   = $_POST['confirm_password'] ?? '';
$captcha   = $_POST['captcha_answer'] ?? '';
$terms     = isset($_POST['terms']);

$sanitized = compact('full_name', 'email', 'mobile', 'college', 'branch', 'semester');

if (empty($full_name) || mb_strlen($full_name) < 2) $errors[] = "Full name must be at least 2 characters";
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address";
if (empty($mobile) || !preg_match('/^[6-9]\d{9}$/', $mobile)) $errors[] = "Please enter a valid 10-digit mobile number";
if (empty($college) || mb_strlen($college) < 2) $errors[] = "College name is required";
if (empty($branch) || !in_array($branch, ['CSE','ECE','EE','ME','CE','Other'])) $errors[] = "Please select a valid branch";
if (empty($semester) || !in_array($semester, ['1','2','3','4','5','6','7','8'])) $errors[] = "Please select a valid semester";
if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
if ($password !== $confirm) $errors[] = "Passwords do not match";
if (!$terms) $errors[] = "You must accept the Terms & Conditions";

if ($captcha === '' || intval($captcha) !== intval($_SESSION['captcha_answer'] ?? -9999)) {
    $errors[] = "Incorrect CAPTCHA answer. Please try again.";
}
unset($_SESSION['captcha_answer']);

if (empty($errors)) {
    $check = $conn->prepare("SELECT id FROM students WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) $errors[] = "This email is already registered. Please login instead.";
    $check->close();
}
if (empty($errors)) {
    $check2 = $conn->prepare("SELECT id FROM students WHERE mobile = ?");
    $check2->bind_param("s", $mobile);
    $check2->execute();
    if ($check2->get_result()->num_rows > 0) $errors[] = "This mobile number is already registered.";
    $check2->close();
}

if (!empty($errors)) {
    $_SESSION['reg_errors'] = $errors;
    $_SESSION['reg_data'] = $sanitized;
    header("Location: register.php");
    exit;
}

$otp = generateOTP();
$_SESSION['reg_otp'] = $otp;
$_SESSION['reg_otp_expires'] = time() + 600;
$_SESSION['reg_pending'] = [
    'full_name'    => $full_name,
    'email'        => $email,
    'mobile'       => $mobile,
    'college_name' => $college,
    'branch'       => $branch,
    'semester'     => $semester,
    'password'     => password_hash($password, PASSWORD_DEFAULT),
];
$_SESSION['csrf_token'] = null;

header("Location: verify-otp.php");
exit;

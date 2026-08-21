<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: forgot-password.html");
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    die(json_encode(['success' => false, 'message' => 'Invalid request.']));
}

$email = $_SESSION['reset_email'] ?? '';
if (empty($email)) {
    die(json_encode(['success' => false, 'message' => 'Session expired. Please start over.']));
}

$otp = trim($_POST['otp'] ?? '');
if (empty($otp) || strlen($otp) !== 6 || !ctype_digit($otp)) {
    die(json_encode(['success' => false, 'message' => 'Please enter a valid 6-digit OTP.']));
}

if (!rateLimit('otp_verify_' . $email, 5, 900)) {
    die(json_encode(['success' => false, 'message' => 'Too many failed attempts. Please request a new OTP.']));
}

$stmt = $conn->prepare("SELECT id, otp_code, otp_expires FROM students WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die(json_encode(['success' => false, 'message' => 'Account not found.']));
}

$student = $result->fetch_assoc();
$stmt->close();

if (empty($student['otp_code']) || empty($student['otp_expires'])) {
    die(json_encode(['success' => false, 'message' => 'No OTP found. Please request a new one.']));
}

if (strtotime($student['otp_expires']) < time()) {
    $stmt = $conn->prepare("UPDATE students SET otp_code = NULL, otp_expires = NULL WHERE id = ?");
    $stmt->bind_param("i", $student['id']);
    $stmt->execute();
    $stmt->close();
    die(json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new one.']));
}

if (!password_verify($otp, $student['otp_code'])) {
    $remaining = getRateLimitRemaining('otp_verify_' . $email, 5, 900);
    die(json_encode([
        'success' => false,
        'message' => 'Incorrect OTP. ' . $remaining . ' attempts remaining.',
        'attempts_left' => $remaining
    ]));
}

$stmt = $conn->prepare("UPDATE students SET otp_code = NULL, otp_expires = NULL WHERE id = ?");
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$stmt->close();

$_SESSION['reset_verified'] = true;
$_SESSION['reset_verified_at'] = time();

echo json_encode(['success' => true, 'message' => 'OTP verified! Redirecting to reset password...']);

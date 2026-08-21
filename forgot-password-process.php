<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: forgot-password.html");
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    die(json_encode(['success' => false, 'message' => 'Invalid request.']));
}

$email = strtolower(trim($_POST['email'] ?? ''));

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['success' => false, 'message' => 'Please enter a valid email address.']));
}

if (!rateLimit('forgot_' . $email, 3, 900)) {
    die(json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']));
}

$stmt = $conn->prepare("SELECT id, full_name FROM students WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => true, 'message' => 'If an account with that email exists, a reset OTP has been sent.']);
    $stmt->close();
    exit;
}

$student = $result->fetch_assoc();
$stmt->close();

$otp = generateOTP();
$otpHash = password_hash($otp, PASSWORD_DEFAULT);
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$stmt = $conn->prepare("UPDATE students SET otp_code = ?, otp_expires = ? WHERE id = ?");
$stmt->bind_param("ssi", $otpHash, $expires, $student['id']);
$stmt->execute();
$stmt->close();

$_SESSION['reset_email'] = $email;
$_SESSION['reset_otp_sent'] = time();

// In production, send OTP via email:
// mail($email, "EngiHub Password Reset OTP", "Your OTP is: $otp");

// For demo, return the OTP in response (REMOVE IN PRODUCTION)
echo json_encode([
    'success' => true,
    'message' => 'Reset OTP sent! Check your email.',
    'demo_otp' => $otp
]);

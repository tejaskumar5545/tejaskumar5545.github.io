<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.html");
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    die(json_encode(['success' => false, 'message' => 'Invalid request.']));
}

if (empty($_SESSION['reset_verified']) || !isset($_SESSION['reset_email'])) {
    die(json_encode(['success' => false, 'message' => 'Session expired. Please start the password reset process again.']));
}

if (time() - ($_SESSION['reset_verified_at'] ?? 0) > 600) {
    unset($_SESSION['reset_verified'], $_SESSION['reset_verified_at'], $_SESSION['reset_email']);
    die(json_encode(['success' => false, 'message' => 'Reset session expired. Please start over.']));
}

$email = $_SESSION['reset_email'];
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (strlen($newPassword) < 8) {
    die(json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']));
}
if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) ||
    !preg_match('/[0-9]/', $newPassword) || !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
    die(json_encode(['success' => false, 'message' => 'Password must include uppercase, lowercase, number, and special character.']));
}
if ($newPassword !== $confirmPassword) {
    die(json_encode(['success' => false, 'message' => 'Passwords do not match.']));
}

if (!rateLimit('reset_' . $email, 3, 3600)) {
    die(json_encode(['success' => false, 'message' => 'Too many password reset attempts. Please try again later.']));
}

$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE students SET password = ?, otp_code = NULL, otp_expires = NULL WHERE email = ?");
$stmt->bind_param("ss", $passwordHash, $email);
$stmt->execute();

if ($stmt->affected_rows > 0 || $stmt->affected_rows === 0) {
    unset($_SESSION['reset_email'], $_SESSION['reset_verified'], $_SESSION['reset_verified_at']);
    echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
}

$stmt->close();

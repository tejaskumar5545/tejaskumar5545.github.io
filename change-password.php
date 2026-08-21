<?php
require_once 'db.php';
header('Content-Type: application/json');

if (!isStudent()) { echo json_encode(['success' => false, 'message' => 'Not authenticated']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'message' => 'Invalid request']); exit; }
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { echo json_encode(['success' => false, 'message' => 'Invalid security token']); exit; }

$studentId = $_SESSION['student_id'];

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($currentPassword)) {
    echo json_encode(['success' => false, 'message' => 'Current password is required']); exit;
}
if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters']); exit;
}
if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword) || !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Password must include uppercase, lowercase, number, and special character']); exit;
}
if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match']); exit;
}
if ($currentPassword === $newPassword) {
    echo json_encode(['success' => false, 'message' => 'New password must be different from current password']); exit;
}

if (!rateLimit('change_pass_' . $studentId, 5, 900)) {
    echo json_encode(['success' => false, 'message' => 'Too many attempts. Please try again in 15 minutes.']); exit;
}

$stmt = $conn->prepare("SELECT password FROM students WHERE id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!password_verify($currentPassword, $student['password'])) {
    $remaining = getRateLimitRemaining('change_pass_' . $studentId, 5, 900);
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect. ' . $remaining . ' attempts remaining.']); exit;
}

$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
$stmt->bind_param("si", $newHash, $studentId);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Password changed successfully! Please login again with your new password.']);

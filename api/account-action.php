<?php
require_once '../db.php';
header('Content-Type: application/json');

if (!isStudent()) { echo json_encode(['success' => false, 'message' => 'Not authenticated']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'message' => 'Invalid request']); exit; }

$studentId = $_SESSION['student_id'];
$action = $_POST['action'] ?? 'deactivate';
$password = $_POST['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required']); exit;
}

$stmt = $conn->prepare("SELECT password FROM students WHERE id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!password_verify($password, $student['password'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect password']); exit;
}

if ($action === 'delete') {
    $confirmText = $_POST['confirm_text'] ?? '';
    if ($confirmText !== 'DELETE') {
        echo json_encode(['success' => false, 'message' => 'Please type DELETE to confirm']); exit;
    }
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $stmt->close();
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Account deleted']);
} else {
    $stmt = $conn->prepare("UPDATE students SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $stmt->close();
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Account deactivated']);
}

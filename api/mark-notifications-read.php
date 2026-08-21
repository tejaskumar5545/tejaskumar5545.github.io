<?php
require_once '../db.php';
header('Content-Type: application/json');

if (!isStudent()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$studentId = $_SESSION['student_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $notifId = intval($_POST['id']);
    $stmt = $conn->prepare("UPDATE user_notifications SET is_read = 1 WHERE id = ? AND student_id = ?");
    $stmt->bind_param("ii", $notifId, $studentId);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare("UPDATE user_notifications SET is_read = 1 WHERE student_id = ? AND is_read = 0");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true]);

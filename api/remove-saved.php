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

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM saved_resources WHERE id = ? AND student_id = ?");
$stmt->bind_param("ii", $id, $studentId);
$stmt->execute();
$deleted = $stmt->affected_rows > 0;
$stmt->close();

echo json_encode(['success' => $deleted]);

<?php
require_once 'db.php';
header('Content-Type: application/json');

if (!isStudent()) { echo json_encode(['success' => false, 'message' => 'Not authenticated']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'message' => 'Invalid request']); exit; }
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { echo json_encode(['success' => false, 'message' => 'Invalid security token']); exit; }

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']); exit;
}

$file = $_FILES['photo'];

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and GIF images are allowed']); exit;
}

$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Image must be under 5MB']); exit;
}

$studentId = $_SESSION['student_id'];

$uploadDir = __DIR__ . '/uploads/profiles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$stmt = $conn->prepare("SELECT profile_photo FROM students WHERE id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$old = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!empty($old['profile_photo'])) {
    $oldFile = $uploadDir . $old['profile_photo'];
    if (file_exists($oldFile)) @unlink($oldFile);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'profile_' . $studentId . '_' . time() . '.' . strtolower($ext);
$filepath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']); exit;
}

$stmt = $conn->prepare("UPDATE students SET profile_photo = ? WHERE id = ?");
$stmt->bind_param("si", $filename, $studentId);
$stmt->execute();
$stmt->close();

$_SESSION['profile_photo'] = $filename;

echo json_encode(['success' => true, 'message' => 'Profile photo updated', 'photo' => $filename]);

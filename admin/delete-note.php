<?php
require_once '../db.php';
requireAdmin();

// Must be POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: notes.php");
    exit;
}

// CSRF check
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    $_SESSION['note_flash'] = 'Security validation failed.';
    $_SESSION['note_flash_type'] = 'error';
    header("Location: notes.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['note_flash'] = 'Invalid note ID.';
    $_SESSION['note_flash_type'] = 'error';
    header("Location: notes.php");
    exit;
}

// Fetch note to get file path
$stmt = $conn->prepare("SELECT id, file_path, file_name FROM notes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows === 0) {
    $_SESSION['note_flash'] = 'Note not found.';
    $_SESSION['note_flash_type'] = 'error';
    header("Location: notes.php");
    exit;
}

$note = $result->fetch_assoc();

// Delete file from disk
$filePath = '../uploads/notes/' . $note['file_path'];
if (!empty($note['file_path']) && file_exists($filePath)) {
    @unlink($filePath);
}

// Delete database record
$stmt = $conn->prepare("DELETE FROM notes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Log activity
$adminId = $_SESSION['admin_id'] ?? 0;
$log = $conn->prepare("INSERT INTO activity_logs (admin_id, action, details) VALUES (?, 'note_deleted', ?)");
$details = "Deleted note: " . $note['file_name'];
$log->bind_param("is", $adminId, $details);
$log->execute();
$log->close();

$_SESSION['note_flash'] = 'Note deleted successfully.';
$_SESSION['note_flash_type'] = 'success';
header("Location: notes.php");
exit;

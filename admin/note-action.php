<?php
require_once '../db.php';
requireAdmin();

$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id <= 0 || !in_array($action, ['publish', 'unpublish'])) {
    $_SESSION['note_flash'] = 'Invalid request.';
    $_SESSION['note_flash_type'] = 'error';
    header("Location: notes.php");
    exit;
}

$stmt = $conn->prepare("SELECT id, title, status FROM notes WHERE id = ?");
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

if ($action === 'publish') {
    $newStatus = 'published';
    $publishedAt = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE notes SET status = 'published', published_at = ? WHERE id = ?");
    $stmt->bind_param("si", $publishedAt, $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['note_flash'] = 'Note "' . $note['title'] . '" published successfully.';
} else {
    $stmt = $conn->prepare("UPDATE notes SET status = 'draft', published_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['note_flash'] = 'Note "' . $note['title'] . '" unpublished.';
}
$_SESSION['note_flash_type'] = 'success';

// Log activity
$adminId = $_SESSION['admin_id'] ?? 0;
$log = $conn->prepare("INSERT INTO activity_logs (admin_id, action, details) VALUES (?, ?, ?)");
$logDetails = $action === 'publish' ? 'Published: ' : 'Unpublished: ';
$logDetails .= $note['title'];
$logAction = $action === 'publish' ? 'note_published' : 'note_unpublished';
$log->bind_param("iss", $adminId, $logAction, $logDetails);
$log->execute();
$log->close();

header("Location: notes.php");
exit;

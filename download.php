<?php
require_once 'db.php';
$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: index.html"); exit; }

$tables = ['notes','syllabus','pyq','practicals'];
if (!in_array($type, $tables)) { header("Location: index.html"); exit; }

$stmt = $conn->prepare("SELECT * FROM $type WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { die("File not found."); }
$row = $result->fetch_assoc();
$stmt->close();

$file = 'uploads/' . $type . '/' . $row['pdf_file'];
if (!file_exists($file)) { die("File not found on server."); }

if (isStudent()) {
    $sid = $_SESSION['student_id'];
    $conn->query("INSERT INTO downloads (student_id, note_id) VALUES ($sid, $id)");
    if ($type === 'notes') {
        $conn->query("UPDATE notes SET download_count = download_count + 1 WHERE id = $id");
    }
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($row['pdf_file']) . '"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: public, max-age=3600');
readfile($file);
exit;

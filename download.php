<?php
session_start();
include 'db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request.");
}

$id = intval($_GET['id']);
$query = "SELECT * FROM notes WHERE id = $id";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Note not found.");
}

$row = mysqli_fetch_assoc($result);
$file_path = 'uploads/' . $row['pdf_file'];

if (!file_exists($file_path)) {
    die("PDF file not found on server.");
}

// Track student download
if (isset($_SESSION['student_id'])) {
    $student_id = intval($_SESSION['student_id']);
    $note_id = $id;
    mysqli_query($conn, "INSERT INTO downloads (student_id, note_id) VALUES ($student_id, $note_id)");
}

header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"" . basename($row['pdf_file']) . "\"");
header("Content-Length: " . filesize($file_path));
header("Cache-Control: private, max-age=0, must-revalidate");

readfile($file_path);
exit;
?>

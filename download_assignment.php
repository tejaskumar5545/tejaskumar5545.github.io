<?php
session_start();
include 'db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request.");
}

$id = intval($_GET['id']);
$result = mysqli_query($conn, "SELECT * FROM assignments WHERE id = $id");

if (!$result || mysqli_num_rows($result) === 0) {
    die("Assignment not found.");
}

$row = mysqli_fetch_assoc($result);
$file_path = 'uploads/' . $row['pdf_file'];

if (!file_exists($file_path)) {
    die("PDF file not found on server.");
}

header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=\"" . basename($row['pdf_file']) . "\"");
header("Content-Length: " . filesize($file_path));
header("Cache-Control: private, max-age=0, must-revalidate");

readfile($file_path);
exit;

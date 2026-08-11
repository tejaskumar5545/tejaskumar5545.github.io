<?php
session_start();
include 'db.php';

$id = intval($_GET['id'] ?? 0);
if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }

mysqli_query($conn, "DELETE FROM exams WHERE id = $id");
header("Location: admin_exams.php?msg=deleted");
exit;
?>

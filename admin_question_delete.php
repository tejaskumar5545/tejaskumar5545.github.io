<?php
session_start();
include 'db.php';

$id = intval($_GET['id'] ?? 0);
$qid = intval($_GET['qid'] ?? 0);

if (!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }

mysqli_query($conn, "DELETE FROM questions WHERE id = $qid AND exam_id = $id");
$conn->query("UPDATE exams SET total_marks = (SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = $id) WHERE id = $id");

header("Location: admin_exam_questions.php?id=$id&msg=deleted");
exit;
?>

<?php
session_start();
include 'db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT pdf_file FROM notes WHERE id = $id"));
    if ($row) {
        $file_path = 'uploads/' . $row['pdf_file'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        mysqli_query($conn, "DELETE FROM notes WHERE id = $id");
        header("Location: dashboard.php?success=delete");
        exit;
    }
    header("Location: dashboard.php?error=delete_failed");
    exit;
}

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $branch = trim($_POST['branch'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || empty($semester) || empty($branch) || !isset($_FILES['pdf_file'])) {
        header("Location: dashboard.php?error=missing_fields");
        exit;
    }

    $file = $_FILES['pdf_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        header("Location: dashboard.php?error=upload_failed");
        exit;
    }

    if ($file['size'] > 20 * 1024 * 1024) {
        header("Location: dashboard.php?error=file_too_large");
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        header("Location: dashboard.php?error=invalid_file");
        exit;
    }

    $safe_title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
    $filename = $safe_title . '_' . time() . '.pdf';
    $destination = 'uploads/' . $filename;

    if (!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $title = mysqli_real_escape_string($conn, $title);
        $semester = mysqli_real_escape_string($conn, $semester);
        $branch = mysqli_real_escape_string($conn, $branch);
        $description = mysqli_real_escape_string($conn, $description);
        $filename = mysqli_real_escape_string($conn, $filename);

        $query = "INSERT INTO notes (title, description, semester, branch, pdf_file) VALUES ('$title', '$description', '$semester', '$branch', '$filename')";
        if (mysqli_query($conn, $query)) {
            header("Location: dashboard.php?success=upload");
            exit;
        } else {
            unlink($destination);
            header("Location: dashboard.php?error=upload_failed");
            exit;
        }
    } else {
        header("Location: dashboard.php?error=upload_failed");
        exit;
    }
}

header("Location: dashboard.php");
exit;
?>

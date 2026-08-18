<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$semesters = ['Semester 1','Semester 2','Semester 3','Semester 4','Semester 5','Semester 6'];
$branches = ['Computer Engineering','Information Technology','Electronics Engineering','Mechanical Engineering','Civil Engineering','Electrical Engineering','Automobile Engineering'];

$msg = '';
$err = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT pdf_file FROM assignments WHERE id = $id"));
    if ($row) {
        $file_path = 'uploads/' . $row['pdf_file'];
        if (file_exists($file_path)) unlink($file_path);
        mysqli_query($conn, "DELETE FROM assignments WHERE id = $id");
        $msg = 'Assignment deleted successfully.';
    } else {
        $err = 'Failed to delete assignment.';
    }
}

// Handle Upload / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $branch = trim($_POST['branch'] ?? '');
    $deadline = trim($_POST['deadline'] ?? '');

    if (empty($title) || empty($semester) || empty($branch)) {
        $err = 'Please fill in all required fields.';
    } else {
        $old_file = '';
        if ($edit_id) {
            $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT pdf_file FROM assignments WHERE id = $edit_id"));
            $old_file = $old ? $old['pdf_file'] : '';
        }

        $new_file = $old_file;
        $upload_error = '';

        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['pdf_file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $upload_error = 'Upload failed. Please try again.';
            } elseif ($file['size'] > 20 * 1024 * 1024) {
                $upload_error = 'PDF is too large. Maximum size is 20MB.';
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if ($ext !== 'pdf') {
                    $upload_error = 'Invalid file type. Only PDF files are allowed.';
                } else {
                    $safe_title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
                    $filename = 'asg_' . $safe_title . '_' . time() . '.pdf';
                    $destination = 'uploads/' . $filename;
                    if (!is_dir('uploads')) mkdir('uploads', 0755, true);
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $new_file = $filename;
                        if ($old_file && $old_file !== $new_file && file_exists('uploads/' . $old_file)) {
                            unlink('uploads/' . $old_file);
                        }
                    } else {
                        $upload_error = 'Failed to move uploaded file.';
                    }
                }
            }
        }

        if ($upload_error) {
            $err = $upload_error;
        } elseif ($new_file === '') {
            $err = 'PDF file is required.';
        } else {
            $title = mysqli_real_escape_string($conn, $title);
            $description = mysqli_real_escape_string($conn, $description);
            $subject = mysqli_real_escape_string($conn, $subject);
            $semester = mysqli_real_escape_string($conn, $semester);
            $branch = mysqli_real_escape_string($conn, $branch);
            $deadline = mysqli_real_escape_string($conn, $deadline);
            $new_file = mysqli_real_escape_string($conn, $new_file);

            if ($edit_id) {
                mysqli_query($conn, "UPDATE assignments SET title = '$title', description = '$description', subject = '$subject', semester = '$semester', branch = '$branch', deadline = '$deadline', pdf_file = '$new_file' WHERE id = $edit_id");
                $msg = 'Assignment updated successfully.';
            } else {
                mysqli_query($conn, "INSERT INTO assignments (title, description, subject, semester, branch, deadline, pdf_file) VALUES ('$title', '$description', '$subject', '$semester', '$branch', '$deadline', '$new_file')");
                $msg = 'Assignment uploaded successfully.';
            }
        }
    }
}

// Pre-fill for edit mode
$edit_row = null;
if (isset($_GET['edit'])) {
    $edit_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM assignments WHERE id = " . intval($_GET['edit'])));
}

$assignments = mysqli_query($conn, "SELECT * FROM assignments ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d2240">
    <title>Manage Assignments - EngiHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <a href="index.php" class="logo">
        <img src="images/logo.jpg" alt="EngiHub" class="logo-img">
        EngiHub
    </a>
    <button class="menu-toggle">&#9776;</button>
    <nav>
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="admin_syllabus.php">Syllabus</a>
        <a href="admin_pyq.php">PYQ</a>
        <a href="admin_practical.php">Practical</a>
        <a href="admin_coding.php">Coding</a>
        <a href="admin_projects.php">Projects</a>
        <a href="admin_placement.php">Placement</a>
        <a href="admin_exams.php">Exams</a>
        <a href="admin_assignments.php" class="active">Assignments</a>
        <a href="admin_notices.php">Notices</a>
        <a href="admin_gallery.php">Gallery</a>
        <a href="admin_students.php">Students</a>
        <a href="admin_results.php">Results</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h2>Manage Assignments</h2>
        </div>
        <a href="admin_assignments.php" class="btn btn-outline btn-sm">&larr; Clear Edit</a>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

    <div class="upload-box">
        <h3><?php echo $edit_row ? 'Edit Assignment' : 'Upload Assignment'; ?></h3>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($edit_row): ?><input type="hidden" name="edit_id" value="<?php echo $edit_row['id']; ?>"><?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" placeholder="e.g. Data Structures Assignment 1" value="<?php echo $edit_row ? htmlspecialchars($edit_row['title']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" placeholder="e.g. Data Structures" value="<?php echo $edit_row ? htmlspecialchars($edit_row['subject']) : ''; ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Semester *</label>
                    <select name="semester" required>
                        <option value="">Select Semester</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?php echo $sem; ?>" <?php echo $edit_row && $edit_row['semester'] === $sem ? 'selected' : ''; ?>><?php echo $sem; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Branch *</label>
                    <select name="branch" required>
                        <option value="">Select Branch</option>
                        <?php foreach ($branches as $br): ?>
                            <option value="<?php echo $br; ?>" <?php echo $edit_row && $edit_row['branch'] === $br ? 'selected' : ''; ?>><?php echo $br; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Deadline</label>
                    <input type="date" name="deadline" value="<?php echo $edit_row ? htmlspecialchars($edit_row['deadline']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>PDF File <?php echo $edit_row ? '(leave empty to keep current)' : '*'; ?></label>
                    <input type="file" name="pdf_file" accept=".pdf" <?php echo $edit_row ? '' : 'required'; ?>>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Assignment instructions (optional)"><?php echo $edit_row ? htmlspecialchars($edit_row['description']) : ''; ?></textarea>
            </div>
            <button type="submit" class="btn btn-success"><?php echo $edit_row ? 'Update Assignment' : 'Upload Assignment'; ?></button>
        </form>
    </div>

    <div class="table-container">
        <h3>All Assignments (<?php echo $assignments ? mysqli_num_rows($assignments) : 0; ?>)</h3>
        <?php if ($assignments && mysqli_num_rows($assignments) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Semester</th>
                            <th>Branch</th>
                            <th>Deadline</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($row = mysqli_fetch_assoc($assignments)): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                <td><?php echo htmlspecialchars($row['semester']); ?></td>
                                <td><?php echo htmlspecialchars($row['branch']); ?></td>
                                <td><?php echo $row['deadline'] ? date('d M Y', strtotime($row['deadline'])) : '-'; ?></td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td class="actions">
                                    <a href="download_assignment.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Download</a>
                                    <a href="admin_assignments.php?edit=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                                    <a href="admin_assignments.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm btn-delete">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128221;</div>
                <h3>No assignments uploaded yet</h3>
                <p>Use the form above to upload your first assignment.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer><p>&copy; 2026 EngiHub Portal</p></footer>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

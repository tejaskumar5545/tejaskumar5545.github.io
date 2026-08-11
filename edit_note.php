<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$id = intval($_GET['id'] ?? 0);
$note = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM notes WHERE id = $id"));
if (!$note) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $semester = trim($_POST['semester'] ?? '');
    $branch = trim($_POST['branch'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || empty($semester) || empty($branch)) {
        $err = 'Please fill in all required fields.';
    } else {
        $title = mysqli_real_escape_string($conn, $title);
        $semester = mysqli_real_escape_string($conn, $semester);
        $branch = mysqli_real_escape_string($conn, $branch);
        $description = mysqli_real_escape_string($conn, $description);
        mysqli_query($conn, "UPDATE notes SET title='$title', description='$description', semester='$semester', branch='$branch' WHERE id=$id");
        header("Location: dashboard.php?success=updated");
        exit;
    }
}

$branches = ['Computer Engineering','Information Technology','Electronics Engineering','Mechanical Engineering','Civil Engineering','Electrical Engineering','Automobile Engineering'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#0d2240">
    <title>Edit Note - ClassroomX</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <a href="index.php" class="logo">
        <img src="images/logo.jpg" alt="ClassroomX" class="logo-img">
        ClassroomX
    </a>
    <button class="menu-toggle">&#9776;</button>
    <nav>
        <a href="index.php">Home</a>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="admin_syllabus.php">Syllabus</a>
        <a href="admin_pyq.php">PYQ</a>
        <a href="admin_practical.php">Practical</a>
        <a href="admin_coding.php">Coding</a>
        <a href="admin_projects.php">Projects</a>
        <a href="admin_placement.php">Placement</a>
        <a href="admin_exams.php">Exams</a>
        <a href="admin_assignments.php">Assignments</a>
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
            <h2>Edit Note</h2>
        </div>
        <a href="dashboard.php" class="btn btn-outline btn-sm">&larr; Back to Dashboard</a>
    </div>

    <?php if (isset($err)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div>
    <?php endif; ?>

    <div class="upload-box">
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($note['title']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Semester *</label>
                    <select name="semester" required>
                        <option value="">Select Semester</option>
                        <?php for ($s = 1; $s <= 6; $s++): ?>
                            <option value="Semester <?php echo $s; ?>" <?php echo $note['semester'] == "Semester $s" ? 'selected' : ''; ?>>Semester <?php echo $s; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Branch *</label>
                    <select name="branch" required>
                        <option value="">Select Branch</option>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?php echo $b; ?>" <?php echo $note['branch'] == $b ? 'selected' : ''; ?>><?php echo $b; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" value="<?php echo htmlspecialchars($note['description']); ?>">
                </div>
            </div>
            <p style="color:var(--gray-500);font-size:13px;margin-bottom:16px;">
                Current file: <strong><?php echo htmlspecialchars($note['pdf_file']); ?></strong> (file cannot be changed here &mdash; delete and re-upload if needed)
            </p>
            <button type="submit" class="btn btn-success">Save Changes</button>
            <a href="dashboard.php" class="btn btn-outline">Cancel</a>
        </form>
    </div>
</div>

<footer><p>&copy; 2026 ClassroomX Portal</p></footer>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$id = intval($_GET['id'] ?? 0);
$exam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM exams WHERE id = $id"));
if (!$exam) {
    header("Location: admin_exams.php");
    exit;
}

$semesters = ['Semester 1','Semester 2','Semester 3','Semester 4','Semester 5','Semester 6'];
$branches = ['Computer Engineering','Information Technology','Electronics Engineering','Mechanical Engineering','Civil Engineering','Electrical Engineering','Automobile Engineering'];

$err = '';
$old = $exam;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['title'] = trim($_POST['title'] ?? '');
    $old['description'] = trim($_POST['description'] ?? '');
    $old['duration_minutes'] = intval($_POST['duration_minutes'] ?? 30);
    $old['total_marks'] = intval($_POST['total_marks'] ?? 0);
    $old['pass_percentage'] = floatval($_POST['pass_percentage'] ?? 40);
    $old['semester'] = trim($_POST['semester'] ?? '');
    $old['branch'] = trim($_POST['branch'] ?? '');
    $old['instructions'] = trim($_POST['instructions'] ?? '');
    $old['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if (empty($old['title']) || $old['duration_minutes'] <= 0) {
        $err = 'Please fill in the required fields correctly.';
    } else {
        $title = mysqli_real_escape_string($conn, $old['title']);
        $description = mysqli_real_escape_string($conn, $old['description']);
        $semester = mysqli_real_escape_string($conn, $old['semester']);
        $branch = mysqli_real_escape_string($conn, $old['branch']);
        $instructions = mysqli_real_escape_string($conn, $old['instructions']);

        $query = "UPDATE exams SET
                    title = '$title',
                    description = '$description',
                    duration_minutes = {$old['duration_minutes']},
                    total_marks = {$old['total_marks']},
                    pass_percentage = {$old['pass_percentage']},
                    semester = '$semester',
                    branch = '$branch',
                    instructions = '$instructions',
                    is_active = {$old['is_active']}
                  WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            header("Location: admin_exams.php?msg=saved");
            exit;
        } else {
            $err = 'Failed to update exam. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d2240">
    <title>Edit Exam - EngiHub</title>
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
        <a href="admin_exams.php" class="active">Exams</a>
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
            <h2>Edit Exam</h2>
        </div>
        <a href="admin_exams.php" class="btn btn-outline btn-sm">&larr; Back to Exams</a>
    </div>

    <?php if ($err): ?><div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

    <div class="upload-box">
        <form method="POST">
            <div class="form-group">
                <label>Exam Title *</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($old['title']); ?>" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"><?php echo htmlspecialchars($old['description']); ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Duration (minutes) *</label>
                    <input type="number" name="duration_minutes" min="1" max="600" value="<?php echo $old['duration_minutes']; ?>" required>
                </div>
                <div class="form-group">
                    <label>Pass Percentage *</label>
                    <input type="number" name="pass_percentage" min="0" max="100" step="0.01" value="<?php echo $old['pass_percentage']; ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Semester (optional)</label>
                    <select name="semester">
                        <option value="">All Semesters</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?php echo $sem; ?>" <?php echo $old['semester'] === $sem ? 'selected' : ''; ?>><?php echo $sem; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Branch (optional)</label>
                    <select name="branch">
                        <option value="">All Branches</option>
                        <?php foreach ($branches as $br): ?>
                            <option value="<?php echo $br; ?>" <?php echo $old['branch'] === $br ? 'selected' : ''; ?>><?php echo $br; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Instructions</label>
                <textarea name="instructions" rows="4"><?php echo htmlspecialchars($old['instructions']); ?></textarea>
            </div>
            <div class="form-group">
                <label style="display:inline-flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="is_active" value="1" <?php echo $old['is_active'] ? 'checked' : ''; ?>>
                    Active (visible to students)
                </label>
            </div>
            <button type="submit" class="btn btn-success">Update Exam</button>
        </form>
    </div>
</div>

<footer><p>&copy; 2026 EngiHub Portal</p></footer>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    mysqli_query($conn, "UPDATE exams SET is_active = IF(is_active = 1, 0, 1) WHERE id = $id");
    header("Location: admin_exams.php?msg=toggled");
    exit;
}

$exams = mysqli_query($conn, "
    SELECT e.*,
        (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as q_count,
        (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.id) as attempt_count
    FROM exams e
    ORDER BY e.created_at DESC
");

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d2240">
    <title>Manage Exams - ClassroomX</title>
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
            <h2>Manage Exams</h2>
        </div>
        <a href="admin_exam_add.php" class="btn btn-success btn-sm">+ Add New Exam</a>
    </div>

    <?php if ($msg === 'deleted'): ?><div class="alert alert-success">Exam deleted successfully.</div>
    <?php elseif ($msg === 'toggled'): ?><div class="alert alert-success">Exam status updated.</div>
    <?php elseif ($msg === 'saved'): ?><div class="alert alert-success">Exam saved successfully.</div>
    <?php elseif ($msg === 'question_deleted'): ?><div class="alert alert-success">Question deleted successfully.</div>
    <?php endif; ?>

    <?php if ($exams && mysqli_num_rows($exams) > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Semester</th>
                        <th>Branch</th>
                        <th>Questions</th>
                        <th>Marks</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = mysqli_fetch_assoc($exams)): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['semester']); ?></td>
                            <td><?php echo htmlspecialchars($row['branch']); ?></td>
                            <td><?php echo $row['q_count']; ?></td>
                            <td><?php echo $row['total_marks']; ?></td>
                            <td><?php echo $row['duration_minutes']; ?> min</td>
                            <td>
                                <a href="admin_exams.php?toggle=<?php echo $row['id']; ?>" class="btn <?php echo $row['is_active'] ? 'btn-success' : 'btn-danger'; ?> btn-sm"><?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?></a>
                            </td>
                            <td class="actions">
                                <a href="admin_exam_questions.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Questions</a>
                                <a href="admin_exam_results.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm">Results (<?php echo $row['attempt_count']; ?>)</a>
                                <a href="admin_exam_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                                <a href="admin_exam_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm btn-delete">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">&#129504;</div>
            <h3>No exams created yet</h3>
            <p>Click "Add New Exam" to create your first online test.</p>
        </div>
    <?php endif; ?>
</div>

<footer><p>&copy; 2026 ClassroomX Portal</p></footer>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

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

$attempts = mysqli_query($conn, "
    SELECT ea.*, s.full_name, s.email, s.semester, s.branch
    FROM exam_attempts ea
    JOIN students s ON ea.student_id = s.id
    WHERE ea.exam_id = $id
    ORDER BY ea.submitted_at DESC
");

$passed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM exam_attempts WHERE exam_id = $id AND passed = 1"))['c'];
$failed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM exam_attempts WHERE exam_id = $id AND passed = 0"))['c'];
$total = $passed + $failed;
$avg = 0;
if ($total > 0) {
    $avg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(percentage) as a FROM exam_attempts WHERE exam_id = $id"))['a'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d2240">
    <title>Exam Results - ClassroomX</title>
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
            <h2><?php echo htmlspecialchars($exam['title']); ?> - Results</h2>
        </div>
        <a href="admin_exams.php" class="btn btn-outline btn-sm">&larr; Back to Exams</a>
    </div>

    <div class="dash-stats">
        <div class="dash-stat-card">
            <div class="stat-label">Attempts</div>
            <div class="stat-value"><?php echo $total; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Passed</div>
            <div class="stat-value"><?php echo $passed; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Failed</div>
            <div class="stat-value"><?php echo $failed; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Avg Score</div>
            <div class="stat-value"><?php echo $avg !== 0 ? number_format($avg, 1) . '%' : '-'; ?></div>
        </div>
    </div>

    <?php if ($attempts && mysqli_num_rows($attempts) > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Sem / Branch</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Result</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($a = mysqli_fetch_assoc($attempts)): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($a['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($a['email']); ?></td>
                            <td><?php echo htmlspecialchars($a['semester']); ?> / <?php echo htmlspecialchars($a['branch']); ?></td>
                            <td><?php echo $a['score']; ?> / <?php echo $a['total_marks']; ?></td>
                            <td><?php echo $a['percentage']; ?>%</td>
                            <td>
                                <span class="btn <?php echo $a['passed'] ? 'btn-success' : 'btn-danger'; ?> btn-sm" style="pointer-events:none;"><?php echo $a['passed'] ? 'Passed' : 'Failed'; ?></span>
                            </td>
                            <td><?php echo date('d M Y, h:i A', strtotime($a['submitted_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">&#128200;</div>
            <h3>No attempts yet</h3>
            <p>Students who attempt this exam will appear here.</p>
        </div>
    <?php endif; ?>
</div>

<footer><p>&copy; 2026 ClassroomX Portal</p></footer>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

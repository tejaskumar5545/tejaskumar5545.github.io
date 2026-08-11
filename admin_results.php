<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$filter_exam = isset($_GET['exam']) ? intval($_GET['exam']) : 0;
$filter_result = isset($_GET['result']) ? $_GET['result'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = [];
if ($filter_exam > 0) {
    $where[] = "ea.exam_id = $filter_exam";
}
if ($filter_result === 'passed') {
    $where[] = "ea.passed = 1";
} elseif ($filter_result === 'failed') {
    $where[] = "ea.passed = 0";
}
if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(s.full_name LIKE '%$s%' OR s.email LIKE '%$s%')";
}
$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$results = mysqli_query($conn, "
    SELECT ea.*, s.full_name, s.email, s.semester, s.branch, e.title as exam_title
    FROM exam_attempts ea
    JOIN students s ON ea.student_id = s.id
    JOIN exams e ON ea.exam_id = e.id
    $where_sql
    ORDER BY ea.submitted_at DESC
");

$exams = mysqli_query($conn, "SELECT id, title FROM exams ORDER BY created_at DESC");

$stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total, SUM(passed = 1) as passed, SUM(passed = 0) as failed, AVG(percentage) as avg FROM exam_attempts"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d2240">
    <title>Test Results - ClassroomX</title>
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
        <a href="admin_exams.php">Exams</a>
        <a href="admin_assignments.php">Assignments</a>
        <a href="admin_notices.php">Notices</a>
        <a href="admin_gallery.php">Gallery</a>
        <a href="admin_students.php">Students</a>
        <a href="admin_results.php" class="active">Results</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h2>Test Results</h2>
        </div>
    </div>

    <div class="dash-stats">
        <div class="dash-stat-card">
            <div class="stat-label">Total Attempts</div>
            <div class="stat-value"><?php echo (int)$stats['total']; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Passed</div>
            <div class="stat-value"><?php echo (int)$stats['passed']; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Failed</div>
            <div class="stat-value"><?php echo (int)$stats['failed']; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Avg Score</div>
            <div class="stat-value"><?php echo $stats['avg'] !== null ? number_format($stats['avg'], 1) . '%' : '-'; ?></div>
        </div>
    </div>

    <form method="GET" action="admin_results.php">
        <div class="filter-bar">
            <input type="text" name="search" placeholder="Search student name or email..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="exam">
                <option value="0">All Exams</option>
                <?php if ($exams): while ($e = mysqli_fetch_assoc($exams)): ?>
                    <option value="<?php echo $e['id']; ?>" <?php echo $filter_exam == $e['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($e['title']); ?></option>
                <?php endwhile; endif; ?>
            </select>
            <select name="result">
                <option value="">All Results</option>
                <option value="passed" <?php echo $filter_result === 'passed' ? 'selected' : ''; ?>>Passed</option>
                <option value="failed" <?php echo $filter_result === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
            <button type="submit" class="btn-filter">Filter</button>
            <?php if ($search !== '' || $filter_exam > 0 || $filter_result !== ''): ?>
                <a href="admin_results.php" class="btn-reset">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($results && mysqli_num_rows($results) > 0): ?>
        <p style="color:var(--gray-700);font-size:14px;margin-bottom:16px;">Showing <?php echo mysqli_num_rows($results); ?> result(s)</p>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Exam</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Result</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = mysqli_fetch_assoc($results)): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?><br><small><?php echo htmlspecialchars($row['email']); ?></small></td>
                            <td><?php echo htmlspecialchars($row['exam_title']); ?></td>
                            <td><?php echo $row['score']; ?> / <?php echo $row['total_marks']; ?></td>
                            <td><?php echo $row['percentage']; ?>%</td>
                            <td>
                                <span class="btn <?php echo $row['passed'] ? 'btn-success' : 'btn-danger'; ?> btn-sm" style="pointer-events:none;"><?php echo $row['passed'] ? 'Passed' : 'Failed'; ?></span>
                            </td>
                            <td><?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">&#128200;</div>
            <h3>No results found</h3>
            <p>Results will appear here when students attempt exams.</p>
        </div>
    <?php endif; ?>
</div>

<footer><p>&copy; 2026 ClassroomX Portal</p></footer>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

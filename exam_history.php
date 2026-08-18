<?php
session_start();
if (!isset($_SESSION['student'])) {
    header("Location: student_login.php");
    exit;
}
include 'db.php';

$student_id = $_SESSION['student_id'];

$attempts = mysqli_query($conn, "
    SELECT a.*, e.title, e.total_marks as exam_marks, e.pass_percentage
    FROM exam_attempts a
    JOIN exams e ON a.exam_id = e.id
    WHERE a.student_id = $student_id AND a.submitted_at IS NOT NULL
    ORDER BY a.submitted_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#0d2240">
    <title>Exam History - EngiHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .result-badge {
            display: inline-block;
            padding: 3px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .result-badge.passed { background: var(--success-light); color: var(--success); border: 1px solid rgba(45,138,78,0.3); }
        .result-badge.failed { background: var(--danger-light); color: var(--danger); border: 1px solid rgba(192,57,43,0.3); }
    </style>
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
        <a href="notes.php">Notes</a>
        <a href="syllabus.php">Syllabus</a>
        <a href="pyq.php">PYQ</a>
        <a href="practical.php">Practical</a>
        <a href="coding.php">Coding</a>
        <a href="projects.php">Projects</a>
        <a href="placement.php">Placement</a>
        <a href="notices.php">Notices</a>
        <a href="exams.php">Online Test</a>
        <a href="student_dashboard.php">My Dashboard</a>
        <a href="student_logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h2>Exam History</h2>
            <p style="color:var(--gray-700);font-size:14px;margin-top:4px;">Your past exam results</p>
        </div>
        <a href="exams.php" class="btn btn-primary btn-sm">&larr; Available Exams</a>
    </div>

    <?php if ($attempts && mysqli_num_rows($attempts) > 0): ?>
        <div class="table-container">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Exam</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Result</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($row = mysqli_fetch_assoc($attempts)): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo $row['score']; ?> / <?php echo $row['total_marks']; ?></td>
                                <td><?php echo $row['percentage']; ?>%</td>
                                <td><span class="result-badge <?php echo $row['passed'] ? 'passed' : 'failed'; ?>"><?php echo $row['passed'] ? 'Passed' : 'Failed'; ?></span></td>
                                <td><?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?></td>
                                <td><a href="exam_result.php?attempt_id=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm">View</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">&#128200;</div>
            <h3>No exam history</h3>
            <p>You haven't attempted any exams yet.</p>
            <a href="exams.php" class="btn btn-primary" style="margin-top:16px;">Browse Exams</a>
        </div>
    <?php endif; ?>
</div>

<footer><p>&copy; 2026 EngiHub Portal</p></footer>
<a href="https://wa.me/918860695666?text=Hi%20EngiHub%2C%20I%20have%20a%20question" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M16.004 0h-.008C7.174 0 0 7.176 0 16c0 3.5 1.132 6.744 3.058 9.374L1.054 31.2l6.064-1.97A15.912 15.912 0 0016.004 32C24.826 32 32 24.822 32 16S24.826 0 16.004 0zm9.31 22.608c-.39 1.096-1.932 2.01-3.162 2.274-.844.18-1.946.322-5.656-1.216-4.746-1.966-7.798-6.79-8.036-7.108-.23-.318-1.9-2.524-1.9-4.814 0-2.29 1.204-3.416 1.63-3.884.39-.428.924-.57 1.23-.57.31 0 .618.004.886.016.284.012.664-.106 1.036.79.39.932 1.33 3.24 1.446 3.478.116.238.194.516.038.834-.156.318-.232.516-.462.794-.23.278-.484.62-.692.832-.23.238-.47.496-.2.972.27.476 1.2 1.98 2.578 3.208 1.77 1.58 3.26 2.07 3.736 2.298.374.18.792.136 1.086-.23.374-.476.836-1.262 1.304-2.02.334-.542.756-.61 1.276-.414.53.196 3.364 1.586 3.94 1.87.576.284.96.428 1.102.664.14.236.14 1.37-.25 2.464z"/></svg>
</a>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

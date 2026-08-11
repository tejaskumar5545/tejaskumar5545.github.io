<?php
session_start();
if (!isset($_SESSION['student'])) {
    header("Location: student_login.php");
    exit;
}
include 'db.php';

$student_id = $_SESSION['student_id'];
$branch = $_SESSION['student_branch'];
$semester = $_SESSION['student_semester'];

$exams = mysqli_query($conn, "
    SELECT e.*,
        (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as q_count,
        (SELECT id FROM exam_attempts WHERE exam_id = e.id AND student_id = $student_id ORDER BY submitted_at DESC LIMIT 1) as last_attempt_id,
        (SELECT passed FROM exam_attempts WHERE exam_id = e.id AND student_id = $student_id ORDER BY submitted_at DESC LIMIT 1) as last_passed
    FROM exams e
    WHERE e.is_active = 1 AND (e.branch = '' OR e.branch = '$branch') AND (e.semester = '' OR e.semester = '$semester')
    ORDER BY e.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#0d2240">
    <title>Online Exams - ClassroomX</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .exam-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            padding: 24px;
            transition: var(--transition);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
        }
        .exam-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-glass-lg);
            border-color: var(--glass-border-accent);
        }
        .exam-card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .exam-meta {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin: 12px 0;
            font-size: 13px;
            color: var(--gray-700);
        }
        .exam-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .exam-status {
            font-size: 12px;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 12px;
        }
        .exam-status.available { background: var(--success-light); color: var(--success); border: 1px solid rgba(45,138,78,0.3); }
        .exam-status.passed { background: var(--success-light); color: var(--success); border: 1px solid rgba(45,138,78,0.3); }
        .exam-status.failed { background: var(--danger-light); color: var(--danger); border: 1px solid rgba(192,57,43,0.3); }
        .exam-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
        }
        @media (max-width: 480px) { .exam-grid { grid-template-columns: 1fr; } }
    </style>
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
        <a href="notes.php">Notes</a>
        <a href="syllabus.php">Syllabus</a>
        <a href="pyq.php">PYQ</a>
        <a href="practical.php">Practical</a>
        <a href="coding.php">Coding</a>
        <a href="projects.php">Projects</a>
        <a href="placement.php">Placement</a>
        <a href="notices.php">Notices</a>
        <a href="exams.php" class="active">Online Test</a>
        <a href="student_dashboard.php">My Dashboard</a>
        <a href="student_logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h2>Online Exams</h2>
            <p style="color:var(--gray-700);font-size:14px;margin-top:4px;">Available exams for your branch and semester</p>
        </div>
        <a href="exam_history.php" class="btn btn-outline btn-sm">My Results</a>
    </div>

    <div class="exam-grid">
        <?php if ($exams && mysqli_num_rows($exams) > 0): while ($exam = mysqli_fetch_assoc($exams)): ?>
            <div class="exam-card">
                <?php if ($exam['last_attempt_id']): ?>
                    <div class="exam-status <?php echo $exam['last_passed'] ? 'passed' : 'failed'; ?>">
                        <?php echo $exam['last_passed'] ? '&#10004; Passed' : '&#10008; Failed'; ?>
                    </div>
                <?php else: ?>
                    <div class="exam-status available">&#128197; Available</div>
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($exam['title']); ?></h3>
                <?php if ($exam['description']): ?>
                    <p style="font-size:13px;color:var(--gray-700);line-height:1.6;"><?php echo htmlspecialchars($exam['description']); ?></p>
                <?php endif; ?>
                <div class="exam-meta">
                    <span>&#9200; <?php echo $exam['duration_minutes']; ?> min</span>
                    <span>&#128221; <?php echo $exam['q_count']; ?> questions</span>
                    <span>&#127919; <?php echo $exam['total_marks']; ?> marks</span>
                    <span>&#9989; Pass: <?php echo $exam['pass_percentage']; ?>%</span>
                </div>
                <?php if ($exam['last_attempt_id']): ?>
                    <a href="exam_result.php?attempt_id=<?php echo $exam['last_attempt_id']; ?>" class="btn btn-outline btn-sm">View Result</a>
                    <a href="exam_take.php?id=<?php echo $exam['id']; ?>" class="btn btn-primary btn-sm" onclick="return confirm('Retake this exam? Your previous result will be overwritten.')">Retake</a>
                <?php else: ?>
                    <a href="exam_take.php?id=<?php echo $exam['id']; ?>" class="btn btn-primary btn-sm">Start Exam</a>
                <?php endif; ?>
            </div>
        <?php endwhile; else: ?>
            <div class="empty-state" style="grid-column:1/-1;">
                <div class="empty-icon">&#128221;</div>
                <h3>No exams available</h3>
                <p>Check back later for new exams.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer><p>&copy; 2026 ClassroomX Portal</p></footer>
<a href="https://wa.me/918860695666?text=Hi%20ClassroomX%2C%20I%20have%20a%20question" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M16.004 0h-.008C7.174 0 0 7.176 0 16c0 3.5 1.132 6.744 3.058 9.374L1.054 31.2l6.064-1.97A15.912 15.912 0 0016.004 32C24.826 32 32 24.822 32 16S24.826 0 16.004 0zm9.31 22.608c-.39 1.096-1.932 2.01-3.162 2.274-.844.18-1.946.322-5.656-1.216-4.746-1.966-7.798-6.79-8.036-7.108-.23-.318-1.9-2.524-1.9-4.814 0-2.29 1.204-3.416 1.63-3.884.39-.428.924-.57 1.23-.57.31 0 .618.004.886.016.284.012.664-.106 1.036.79.39.932 1.33 3.24 1.446 3.478.116.238.194.516.038.834-.156.318-.232.516-.462.794-.23.278-.484.62-.692.832-.23.238-.47.496-.2.972.27.476 1.2 1.98 2.578 3.208 1.77 1.58 3.26 2.07 3.736 2.298.374.18.792.136 1.086-.23.374-.476.836-1.262 1.304-2.02.334-.542.756-.61 1.276-.414.53.196 3.364 1.586 3.94 1.87.576.284.96.428 1.102.664.14.236.14 1.37-.25 2.464z"/></svg>
</a>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

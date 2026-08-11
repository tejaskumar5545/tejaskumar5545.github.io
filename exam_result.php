<?php
session_start();
if (!isset($_SESSION['student'])) {
    header("Location: student_login.php");
    exit;
}
include 'db.php';

$attempt_id = intval($_GET['attempt_id'] ?? 0);
$student_id = $_SESSION['student_id'];

$attempt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM exam_attempts WHERE id = $attempt_id AND student_id = $student_id"));
if (!$attempt) { header("Location: exam_history.php"); exit; }

$exam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM exams WHERE id = {$attempt['exam_id']}"));

$answers = mysqli_query($conn, "
    SELECT sa.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option, q.marks
    FROM student_answers sa
    JOIN questions q ON sa.question_id = q.id
    WHERE sa.attempt_id = $attempt_id
    ORDER BY sa.id ASC
");

$correct_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM student_answers WHERE attempt_id = $attempt_id AND is_correct = 1"))['c'];
$total_questions = mysqli_num_rows($answers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#0d2240">
    <title>Exam Result - ClassroomX</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .result-card {
            text-align: center;
            padding: 40px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            backdrop-filter: blur(var(--glass-blur));
        }
        .result-score {
            font-size: 56px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent), #80c4ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .result-passed { color: var(--success); font-size: 20px; font-weight: 700; }
        .result-failed { color: var(--danger); font-size: 20px; font-weight: 700; }
        .result-meta { display: flex; justify-content: center; gap: 32px; margin-top: 16px; flex-wrap: wrap; }
        .result-meta-item { font-size: 14px; }
        .result-meta-item strong { display: block; font-size: 20px; }
        .answer-review {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 12px;
            backdrop-filter: blur(var(--glass-blur));
        }
        .answer-review.correct { border-color: rgba(45,138,78,0.4); }
        .answer-review.wrong { border-color: rgba(192,57,43,0.4); }
        .answer-review h4 { font-size: 15px; margin-bottom: 10px; }
        .ans-option { font-size: 13px; padding: 6px 12px; margin: 4px 0; border-radius: 6px; }
        .ans-option.correct { background: var(--success-light); border-left: 3px solid var(--success); }
        .ans-option.wrong { background: var(--danger-light); border-left: 3px solid var(--danger); }
        .ans-option.selected { font-weight: 600; }
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
        <a href="exams.php">Online Test</a>
        <a href="student_dashboard.php">My Dashboard</a>
        <a href="student_logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h2>Exam Result</h2>
            <p style="color:var(--gray-700);font-size:14px;margin-top:4px;"><?php echo htmlspecialchars($exam['title']); ?></p>
        </div>
        <div>
            <a href="exams.php" class="btn btn-outline btn-sm">&larr; Back to Exams</a>
            <a href="exam_history.php" class="btn btn-outline btn-sm">All Results</a>
        </div>
    </div>

    <div class="result-card fade-in">
        <div class="result-score"><?php echo $attempt['percentage']; ?>%</div>
        <div class="<?php echo $attempt['passed'] ? 'result-passed' : 'result-failed'; ?>">
            <?php echo $attempt['passed'] ? '&#10004; Congratulations! You Passed!' : '&#10008; Sorry, You Did Not Pass'; ?>
        </div>
        <div class="result-meta">
            <div class="result-meta-item">Score <strong><?php echo $attempt['score']; ?> / <?php echo $attempt['total_marks']; ?></strong></div>
            <div class="result-meta-item">Correct <strong><?php echo $correct_count; ?> / <?php echo $total_questions; ?></strong></div>
            <div class="result-meta-item">Pass Mark <strong><?php echo $exam['pass_percentage']; ?>%</strong></div>
            <div class="result-meta-item">Submitted <strong><?php echo date('d M Y, h:i A', strtotime($attempt['submitted_at'])); ?></strong></div>
        </div>
    </div>

    <h3 style="margin-bottom:16px;">Answer Review</h3>
    <?php $qnum = 1; while ($a = mysqli_fetch_assoc($answers)): ?>
        <div class="answer-review <?php echo $a['is_correct'] ? 'correct' : 'wrong'; ?>">
            <h4>Q<?php echo $qnum++; ?>. <?php echo htmlspecialchars($a['question_text']); ?>
                <span style="float:right;font-size:12px;font-weight:400;">
                    <?php echo $a['is_correct'] ? '<span style="color:var(--success);">+'.$a['marks'].'</span>' : '<span style="color:var(--danger);">0</span>'; ?>
                </span>
            </h4>
            <?php foreach (['A','B','C','D'] as $opt):
                $val = $a["option_" . strtolower($opt)];
                if (empty($val)) continue;
                $class = '';
                if ($a['correct_option'] === $opt) $class = 'correct';
                if ($a['selected_option'] === $opt && !$a['is_correct']) $class = 'wrong';
                if ($a['selected_option'] === $opt) $class .= ' selected';
            ?>
                <div class="ans-option <?php echo $class; ?>">
                    <?php echo $opt; ?>. <?php echo htmlspecialchars($val); ?>
                    <?php if ($a['correct_option'] === $opt): ?><span style="float:right;color:var(--success);">&#10004; Correct Answer</span>
                    <?php elseif ($a['selected_option'] === $opt): ?><span style="float:right;color:var(--danger);">&#10008; Your Answer</span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endwhile; ?>
</div>

<footer><p>&copy; 2026 ClassroomX Portal</p></footer>
<a href="https://wa.me/918860695666?text=Hi%20ClassroomX%2C%20I%20have%20a%20question" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M16.004 0h-.008C7.174 0 0 7.176 0 16c0 3.5 1.132 6.744 3.058 9.374L1.054 31.2l6.064-1.97A15.912 15.912 0 0016.004 32C24.826 32 32 24.822 32 16S24.826 0 16.004 0zm9.31 22.608c-.39 1.096-1.932 2.01-3.162 2.274-.844.18-1.946.322-5.656-1.216-4.746-1.966-7.798-6.79-8.036-7.108-.23-.318-1.9-2.524-1.9-4.814 0-2.29 1.204-3.416 1.63-3.884.39-.428.924-.57 1.23-.57.31 0 .618.004.886.016.284.012.664-.106 1.036.79.39.932 1.33 3.24 1.446 3.478.116.238.194.516.038.834-.156.318-.232.516-.462.794-.23.278-.484.62-.692.832-.23.238-.47.496-.2.972.27.476 1.2 1.98 2.578 3.208 1.77 1.58 3.26 2.07 3.736 2.298.374.18.792.136 1.086-.23.374-.476.836-1.262 1.304-2.02.334-.542.756-.61 1.276-.414.53.196 3.364 1.586 3.94 1.87.576.284.96.428 1.102.664.14.236.14 1.37-.25 2.464z"/></svg>
</a>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

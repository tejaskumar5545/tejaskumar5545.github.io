<?php
session_start();
if (!isset($_SESSION['student'])) {
    header("Location: student_login.php");
    exit;
}
include 'db.php';

$exam_id = intval($_GET['id'] ?? 0);
$student_id = $_SESSION['student_id'];

$exam = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM exams WHERE id = $exam_id AND is_active = 1"));
if (!$exam) { header("Location: exams.php"); exit; }

$questions = mysqli_query($conn, "SELECT * FROM questions WHERE exam_id = $exam_id ORDER BY id ASC");
if (mysqli_num_rows($questions) === 0) { header("Location: exams.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    $attempt_id = intval($_POST['attempt_id'] ?? 0);
    $attempt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM exam_attempts WHERE id = $attempt_id AND student_id = $student_id AND exam_id = $exam_id AND submitted_at IS NULL"));
    if (!$attempt) { header("Location: exams.php"); exit; }

    $total_score = 0;
    $total_marks = 0;

    while ($q = mysqli_fetch_assoc($questions)) {
        $selected = strtoupper(trim($_POST['q_' . $q['id']] ?? ''));
        $is_correct = ($selected === $q['correct_option']) ? 1 : 0;
        $marks_obtained = $is_correct ? $q['marks'] : 0;
        $total_score += $marks_obtained;
        $total_marks += $q['marks'];

        $selected_safe = mysqli_real_escape_string($conn, $selected);
        mysqli_query($conn, "INSERT INTO student_answers (attempt_id, question_id, selected_option, is_correct, marks_obtained) VALUES ($attempt_id, {$q['id']}, '$selected_safe', $is_correct, $marks_obtained)");
    }

    $percentage = $total_marks > 0 ? round(($total_score / $total_marks) * 100, 2) : 0;
    $passed = $percentage >= $exam['pass_percentage'] ? 1 : 0;

    mysqli_query($conn, "UPDATE exam_attempts SET submitted_at = NOW(), score = $total_score, total_marks = $total_marks, percentage = $percentage, passed = $passed WHERE id = $attempt_id");

    header("Location: exam_result.php?attempt_id=$attempt_id");
    exit;
}

$existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM exam_attempts WHERE exam_id = $exam_id AND student_id = $student_id AND submitted_at IS NULL LIMIT 1"));

if ($existing) {
    $attempt_id = $existing['id'];
} else {
    mysqli_query($conn, "INSERT INTO exam_attempts (student_id, exam_id) VALUES ($student_id, $exam_id)");
    $attempt_id = mysqli_insert_id($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0d2240">
    <title><?php echo htmlspecialchars($exam['title']); ?> - EngiHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .exam-header {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            padding: 20px 24px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .exam-timer {
            font-size: 20px;
            font-weight: 700;
            color: var(--accent);
            font-variant-numeric: tabular-nums;
        }
        .exam-timer.warning { color: var(--accent-gold); }
        .exam-timer.danger { color: var(--danger); }
        .question-block {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            padding: 28px;
            margin-bottom: 20px;
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
        }
        .question-block h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .q-num {
            display: inline-block;
            background: var(--accent);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            font-size: 13px;
            font-weight: 700;
            margin-right: 10px;
        }
        .option-label {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border: 1px solid var(--glass-border);
            border-radius: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: var(--transition);
            font-size: 14px;
        }
        .option-label:hover {
            border-color: var(--accent);
            background: var(--accent-light);
        }
        .option-label input[type="radio"] {
            accent-color: var(--accent);
            width: 18px;
            height: 18px;
        }
        .option-label.selected {
            border-color: var(--accent);
            background: var(--accent-light);
        }
        .instructions-box {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            padding: 24px;
            margin-bottom: 20px;
            backdrop-filter: blur(var(--glass-blur));
        }
        .instructions-box h3 { margin-bottom: 12px; }
        .instructions-box ul { padding-left: 20px; font-size: 14px; color: var(--gray-700); line-height: 2; }
        .inst-check { display: flex; align-items: center; gap: 10px; margin: 16px 0; font-size: 14px; }
        @media (max-width: 480px) {
            .exam-header { flex-direction: column; gap: 8px; text-align: center; }
        }
    </style>
</head>
<body>

<div class="container">
    <form method="POST" id="examForm">
        <input type="hidden" name="attempt_id" value="<?php echo $attempt_id; ?>">
        <input type="hidden" name="submit_exam" value="1">

        <div class="exam-header" id="examHeader">
            <div>
                <h2 style="font-size:18px;font-weight:600;"><?php echo htmlspecialchars($exam['title']); ?></h2>
                <span style="font-size:12px;color:var(--gray-700);"><?php echo mysqli_num_rows($questions); ?> questions</span>
            </div>
            <div>
                <div class="exam-timer" id="examTimer"><?php echo $exam['duration_minutes']; ?>:00</div>
                <span style="font-size:11px;color:var(--gray-500);">time remaining</span>
            </div>
        </div>

        <?php if ($exam['instructions']): ?>
            <div class="instructions-box" id="instructionsBox">
                <h3>&#128196; Instructions</h3>
                <ul>
                    <?php foreach (explode("\n", $exam['instructions']) as $line): ?>
                        <?php if (trim($line)): ?><li><?php echo htmlspecialchars(trim($line)); ?></li><?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <label class="inst-check">
                    <input type="checkbox" id="agreeCheck">
                    I have read and understood the instructions. I am ready to begin.
                </label>
                <button type="button" class="btn btn-primary" id="startBtn" disabled onclick="startExam()">Start Exam</button>
            </div>
        <?php endif; ?>

        <div id="questionsArea" style="display:none;">
            <?php $qnum = 1; while ($q = mysqli_fetch_assoc($questions)): ?>
                <div class="question-block">
                    <h3><span class="q-num"><?php echo $qnum++; ?></span> <?php echo htmlspecialchars($q['question_text']); ?></h3>
                    <?php foreach (['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']] as $key => $val): ?>
                        <?php if (empty($val)) continue; ?>
                        <label class="option-label">
                            <input type="radio" name="q_<?php echo $q['id']; ?>" value="<?php echo $key; ?>" onchange="this.closest('.option-label').classList.add('selected')">
                            <span><strong><?php echo $key; ?>.</strong> <?php echo htmlspecialchars($val); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endwhile; ?>

            <div style="text-align:center;padding:20px 0;">
                <p style="font-size:13px;color:var(--gray-700);margin-bottom:12px;">Make sure you have answered all questions before submitting.</p>
                <button type="submit" class="btn btn-primary" style="padding:14px 48px;font-size:16px;" onclick="return confirm('Are you sure you want to submit the exam?')">&#10004; Submit Exam</button>
            </div>
        </div>
    </form>
</div>

<script>
    var duration = <?php echo $exam['duration_minutes']; ?>;
    var timeLeft = duration * 60;
    var timerEl = document.getElementById('examTimer');
    var questionsArea = document.getElementById('questionsArea');
    var instructionsBox = document.getElementById('instructionsBox');
    var examHeader = document.getElementById('examHeader');
    var timerInterval;

    function pad(n) { return n < 10 ? '0' + n : n; }

    function updateTimer() {
        var m = Math.floor(timeLeft / 60);
        var s = timeLeft % 60;
        timerEl.textContent = m + ':' + pad(s);
        if (timeLeft <= 60) {
            timerEl.className = 'exam-timer danger';
        } else if (timeLeft <= 300) {
            timerEl.className = 'exam-timer warning';
        }
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            alert('Time is up! Your exam will be submitted automatically.');
            document.getElementById('examForm').submit();
        }
        timeLeft--;
    }

    function startExam() {
        if (!document.getElementById('agreeCheck').checked) return;
        if (instructionsBox) instructionsBox.style.display = 'none';
        questionsArea.style.display = 'block';
        timerInterval = setInterval(updateTimer, 1000);
    }

    if (!instructionsBox) {
        questionsArea.style.display = 'block';
        timerInterval = setInterval(updateTimer, 1000);
    }

    document.getElementById('agreeCheck')?.addEventListener('change', function() {
        document.getElementById('startBtn').disabled = !this.checked;
    });
</script>
</body>
</html>
<?php mysqli_close($conn); ?>

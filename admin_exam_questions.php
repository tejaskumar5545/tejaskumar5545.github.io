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

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $question_text = trim($_POST['question_text'] ?? '');
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $correct_option = strtoupper(trim($_POST['correct_option'] ?? ''));
    $marks = intval($_POST['marks'] ?? 1);

    if (empty($question_text) || empty($option_a) || empty($option_b) || !in_array($correct_option, ['A','B','C','D'])) {
        $err = 'Please fill in question, options A & B, and select the correct answer.';
    } else {
        $question_text = mysqli_real_escape_string($conn, $question_text);
        $option_a = mysqli_real_escape_string($conn, $option_a);
        $option_b = mysqli_real_escape_string($conn, $option_b);
        $option_c = mysqli_real_escape_string($conn, $option_c);
        $option_d = mysqli_real_escape_string($conn, $option_d);

        $query = "INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option, marks)
                  VALUES ($id, '$question_text', '$option_a', '$option_b', '$option_c', '$option_d', '$correct_option', $marks)";
        if (mysqli_query($conn, $query)) {
            mysqli_query($conn, "UPDATE exams SET total_marks = (SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = $id) WHERE id = $id");
            header("Location: admin_exam_questions.php?id=$id&msg=added");
            exit;
        } else {
            $err = 'Failed to add question. Please try again.';
        }
    }
}

$questions = mysqli_query($conn, "SELECT * FROM questions WHERE exam_id = $id ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d2240">
    <title>Exam Questions - ClassroomX</title>
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
            <h2><?php echo htmlspecialchars($exam['title']); ?></h2>
            <p style="color:var(--gray-700);font-size:14px;margin-top:4px;">
                Total Marks: <?php echo $exam['total_marks']; ?> &bull; Duration: <?php echo $exam['duration_minutes']; ?> min
            </p>
        </div>
        <a href="admin_exams.php" class="btn btn-outline btn-sm">&larr; Back to Exams</a>
    </div>

    <?php if ($msg === 'added'): ?><div class="alert alert-success">Question added successfully.</div>
    <?php elseif ($msg === 'deleted'): ?><div class="alert alert-success">Question deleted successfully.</div>
    <?php elseif ($msg === 'created'): ?><div class="alert alert-success">Exam created. Now add questions to it.</div>
    <?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

    <div class="upload-box">
        <h3>Add Question</h3>
        <form method="POST">
            <div class="form-group">
                <label>Question *</label>
                <textarea name="question_text" rows="2" placeholder="Enter the question" required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Option A *</label>
                    <input type="text" name="option_a" required>
                </div>
                <div class="form-group">
                    <label>Option B *</label>
                    <input type="text" name="option_b" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Option C</label>
                    <input type="text" name="option_c">
                </div>
                <div class="form-group">
                    <label>Option D</label>
                    <input type="text" name="option_d">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Correct Answer *</label>
                    <select name="correct_option" required>
                        <option value="">Select</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Marks *</label>
                    <input type="number" name="marks" min="1" max="100" value="1" required>
                </div>
            </div>
            <button type="submit" name="add_question" class="btn btn-success">Add Question</button>
        </form>
    </div>

    <div class="table-container">
        <h3>Questions (<?php echo $questions ? mysqli_num_rows($questions) : 0; ?>)</h3>
        <?php if ($questions && mysqli_num_rows($questions) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Question</th>
                            <th>Options</th>
                            <th>Correct</th>
                            <th>Marks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($q = mysqli_fetch_assoc($questions)): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($q['question_text']); ?></td>
                                <td style="font-size:13px;line-height:1.5;">
                                    A. <?php echo htmlspecialchars($q['option_a']); ?><br>
                                    B. <?php echo htmlspecialchars($q['option_b']); ?><br>
                                    C. <?php echo htmlspecialchars($q['option_c']); ?><br>
                                    D. <?php echo htmlspecialchars($q['option_d']); ?>
                                </td>
                                <td><?php echo $q['correct_option']; ?></td>
                                <td><?php echo $q['marks']; ?></td>
                                <td class="actions">
                                    <a href="admin_question_delete.php?id=<?php echo $id; ?>&qid=<?php echo $q['id']; ?>" class="btn btn-danger btn-sm btn-delete">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128221;</div>
                <h3>No questions yet</h3>
                <p>Add questions using the form above.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer><p>&copy; 2026 ClassroomX Portal</p></footer>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

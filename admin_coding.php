<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$languages = ['C','C++','Java','Python','JavaScript','HTML','CSS','PHP','SQL','Other'];
$difficulties = ['Easy','Medium','Hard'];

$msg = '';
$err = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM coding WHERE id = $id");
    $msg = 'Coding problem deleted successfully.';
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $language = trim($_POST['language'] ?? '');
    $difficulty = trim($_POST['difficulty'] ?? 'Easy');
    $code = $_POST['code'] ?? '';

    if (empty($title) || empty($language)) {
        $err = 'Please fill in the title and language.';
    } else {
        $title = mysqli_real_escape_string($conn, $title);
        $description = mysqli_real_escape_string($conn, $description);
        $language = mysqli_real_escape_string($conn, $language);
        $difficulty = mysqli_real_escape_string($conn, $difficulty);
        $code = mysqli_real_escape_string($conn, $code);

        if ($edit_id) {
            mysqli_query($conn, "UPDATE coding SET title = '$title', description = '$description', language = '$language', difficulty = '$difficulty', code = '$code' WHERE id = $edit_id");
            $msg = 'Coding problem updated successfully.';
        } else {
            mysqli_query($conn, "INSERT INTO coding (title, description, language, difficulty, code) VALUES ('$title', '$description', '$language', '$difficulty', '$code')");
            $msg = 'Coding problem added successfully.';
        }
    }
}

// Pre-fill for edit mode
$edit_row = null;
if (isset($_GET['edit'])) {
    $edit_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM coding WHERE id = " . intval($_GET['edit'])));
}

$coding = mysqli_query($conn, "SELECT * FROM coding ORDER BY FIELD(difficulty,'Easy','Medium','Hard'), created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d2240">
    <title>Manage Coding - EngiHub</title>
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
        <a href="admin_coding.php" class="active">Coding</a>
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
            <h2>Manage Coding Problems</h2>
        </div>
        <a href="admin_coding.php" class="btn btn-outline btn-sm">&larr; Clear Edit</a>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

    <div class="upload-box">
        <h3><?php echo $edit_row ? 'Edit Coding Problem' : 'Add Coding Problem'; ?></h3>
        <form method="POST">
            <?php if ($edit_row): ?><input type="hidden" name="edit_id" value="<?php echo $edit_row['id']; ?>"><?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" placeholder="e.g. Sum of Two Numbers" value="<?php echo $edit_row ? htmlspecialchars($edit_row['title']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Language *</label>
                    <select name="language" required>
                        <option value="">Select Language</option>
                        <?php foreach ($languages as $lang): ?>
                            <option value="<?php echo $lang; ?>" <?php echo $edit_row && $edit_row['language'] === $lang ? 'selected' : ''; ?>><?php echo $lang; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Difficulty</label>
                <select name="difficulty">
                    <?php foreach ($difficulties as $diff): ?>
                        <option value="<?php echo $diff; ?>" <?php echo $edit_row && $edit_row['difficulty'] === $diff ? 'selected' : ''; ?>><?php echo $diff; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Problem statement"><?php echo $edit_row ? htmlspecialchars($edit_row['description']) : ''; ?></textarea>
            </div>
            <div class="form-group">
                <label>Solution Code</label>
                <textarea name="code" rows="8" style="font-family:monospace;" placeholder="// Solution code"><?php echo $edit_row ? htmlspecialchars($edit_row['code']) : ''; ?></textarea>
            </div>
            <button type="submit" class="btn btn-success"><?php echo $edit_row ? 'Update Problem' : 'Add Problem'; ?></button>
        </form>
    </div>

    <div class="table-container">
        <h3>All Coding Problems (<?php echo $coding ? mysqli_num_rows($coding) : 0; ?>)</h3>
        <?php if ($coding && mysqli_num_rows($coding) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Language</th>
                            <th>Difficulty</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($row = mysqli_fetch_assoc($coding)): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['language']); ?></td>
                                <td><?php echo htmlspecialchars($row['difficulty']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td class="actions">
                                    <a href="admin_coding.php?edit=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                                    <a href="admin_coding.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm btn-delete">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128187;</div>
                <h3>No coding problems yet</h3>
                <p>Use the form above to add the first coding problem.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer><p>&copy; 2026 EngiHub Portal</p></footer>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

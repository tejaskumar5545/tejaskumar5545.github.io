<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$categories = ['General','Exam','Event','Admission','Result','Other'];

$msg = '';
$err = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM notices WHERE id = $id");
    $msg = 'Notice deleted successfully.';
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $is_important = isset($_POST['is_important']) ? 1 : 0;

    if (empty($title)) {
        $err = 'Please enter the notice title.';
    } else {
        $title = mysqli_real_escape_string($conn, $title);
        $content = mysqli_real_escape_string($conn, $content);
        $category = mysqli_real_escape_string($conn, $category);

        if ($edit_id) {
            mysqli_query($conn, "UPDATE notices SET title = '$title', content = '$content', category = '$category', is_important = $is_important WHERE id = $edit_id");
            $msg = 'Notice updated successfully.';
        } else {
            mysqli_query($conn, "INSERT INTO notices (title, content, category, is_important) VALUES ('$title', '$content', '$category', $is_important)");
            $msg = 'Notice added successfully.';
        }
    }
}

// Pre-fill for edit mode
$edit_row = null;
if (isset($_GET['edit'])) {
    $edit_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM notices WHERE id = " . intval($_GET['edit'])));
}

$notices = mysqli_query($conn, "SELECT * FROM notices ORDER BY is_important DESC, created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d2240">
    <title>Manage Notices - EngiHub</title>
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
        <a href="admin_exams.php">Exams</a>
        <a href="admin_assignments.php">Assignments</a>
        <a href="admin_notices.php" class="active">Notices</a>
        <a href="admin_gallery.php">Gallery</a>
        <a href="admin_students.php">Students</a>
        <a href="admin_results.php">Results</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h2>Manage Notices</h2>
        </div>
        <a href="admin_notices.php" class="btn btn-outline btn-sm">&larr; Clear Edit</a>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

    <div class="upload-box">
        <h3><?php echo $edit_row ? 'Edit Notice' : 'Add Notice'; ?></h3>
        <form method="POST">
            <?php if ($edit_row): ?><input type="hidden" name="edit_id" value="<?php echo $edit_row['id']; ?>"><?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" placeholder="e.g. Mid Term Exam Schedule" value="<?php echo $edit_row ? htmlspecialchars($edit_row['title']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo $edit_row && $edit_row['category'] === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Content</label>
                <textarea name="content" rows="4" placeholder="Notice details"><?php echo $edit_row ? htmlspecialchars($edit_row['content']) : ''; ?></textarea>
            </div>
            <div class="form-group">
                <label style="display:inline-flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="is_important" value="1" <?php echo $edit_row && $edit_row['is_important'] ? 'checked' : ''; ?>>
                    Important (shown first / highlighted)
                </label>
            </div>
            <button type="submit" class="btn btn-success"><?php echo $edit_row ? 'Update Notice' : 'Add Notice'; ?></button>
        </form>
    </div>

    <div class="table-container">
        <h3>All Notices (<?php echo $notices ? mysqli_num_rows($notices) : 0; ?>)</h3>
        <?php if ($notices && mysqli_num_rows($notices) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Important</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($row = mysqli_fetch_assoc($notices)): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><?php echo $row['is_important'] ? '&#11088; Yes' : '-'; ?></td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td class="actions">
                                    <a href="admin_notices.php?edit=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                                    <a href="admin_notices.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm btn-delete">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128226;</div>
                <h3>No notices yet</h3>
                <p>Use the form above to add your first notice.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer><p>&copy; 2026 EngiHub Portal</p></footer>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

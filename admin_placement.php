<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$msg = '';
$err = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM placement WHERE id = $id");
    $msg = 'Placement posting deleted successfully.';
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $title = trim($_POST['title'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $package = trim($_POST['package'] ?? '');
    $link = trim($_POST['link'] ?? '');

    if (empty($title)) {
        $err = 'Please enter the posting title.';
    } else {
        $title = mysqli_real_escape_string($conn, $title);
        $company = mysqli_real_escape_string($conn, $company);
        $description = mysqli_real_escape_string($conn, $description);
        $role = mysqli_real_escape_string($conn, $role);
        $package = mysqli_real_escape_string($conn, $package);
        $link = mysqli_real_escape_string($conn, $link);

        if ($edit_id) {
            mysqli_query($conn, "UPDATE placement SET title = '$title', company = '$company', description = '$description', role = '$role', package = '$package', link = '$link' WHERE id = $edit_id");
            $msg = 'Placement posting updated successfully.';
        } else {
            mysqli_query($conn, "INSERT INTO placement (title, company, description, role, package, link) VALUES ('$title', '$company', '$description', '$role', '$package', '$link')");
            $msg = 'Placement posting added successfully.';
        }
    }
}

// Pre-fill for edit mode
$edit_row = null;
if (isset($_GET['edit'])) {
    $edit_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM placement WHERE id = " . intval($_GET['edit'])));
}

$placement = mysqli_query($conn, "SELECT * FROM placement ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d2240">
    <title>Manage Placement - ClassroomX</title>
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
        <a href="admin_placement.php" class="active">Placement</a>
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
            <h2>Manage Placement Postings</h2>
        </div>
        <a href="admin_placement.php" class="btn btn-outline btn-sm">&larr; Clear Edit</a>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

    <div class="upload-box">
        <h3><?php echo $edit_row ? 'Edit Posting' : 'Add Posting'; ?></h3>
        <form method="POST">
            <?php if ($edit_row): ?><input type="hidden" name="edit_id" value="<?php echo $edit_row['id']; ?>"><?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" placeholder="e.g. TCS Campus Drive" value="<?php echo $edit_row ? htmlspecialchars($edit_row['title']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Company</label>
                    <input type="text" name="company" placeholder="e.g. TCS" value="<?php echo $edit_row ? htmlspecialchars($edit_row['company']) : ''; ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Role</label>
                    <input type="text" name="role" placeholder="e.g. Software Engineer" value="<?php echo $edit_row ? htmlspecialchars($edit_row['role']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Package</label>
                    <input type="text" name="package" placeholder="e.g. 7 LPA" value="<?php echo $edit_row ? htmlspecialchars($edit_row['package']) : ''; ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Apply Link</label>
                <input type="url" name="link" placeholder="https://..." value="<?php echo $edit_row ? htmlspecialchars($edit_row['link']) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" placeholder="Details about the drive / eligibility / deadline"><?php echo $edit_row ? htmlspecialchars($edit_row['description']) : ''; ?></textarea>
            </div>
            <button type="submit" class="btn btn-success"><?php echo $edit_row ? 'Update Posting' : 'Add Posting'; ?></button>
        </form>
    </div>

    <div class="table-container">
        <h3>All Postings (<?php echo $placement ? mysqli_num_rows($placement) : 0; ?>)</h3>
        <?php if ($placement && mysqli_num_rows($placement) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Company</th>
                            <th>Role</th>
                            <th>Package</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($row = mysqli_fetch_assoc($placement)): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['company']); ?></td>
                                <td><?php echo htmlspecialchars($row['role']); ?></td>
                                <td><?php echo htmlspecialchars($row['package']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td class="actions">
                                    <a href="admin_placement.php?edit=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                                    <a href="admin_placement.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm btn-delete">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128188;</div>
                <h3>No placement postings yet</h3>
                <p>Use the form above to add the first posting.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer><p>&copy; 2026 ClassroomX Portal</p></footer>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

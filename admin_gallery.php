<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$msg = '';
$err = '';
$categories = ['General','Campus','Event','Sports','Library','Lab','Achievement'];

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM gallery_images WHERE id = $id"));
    if ($row) {
        $file_path = 'uploads/gallery/' . $row['image'];
        if (file_exists($file_path)) unlink($file_path);
        mysqli_query($conn, "DELETE FROM gallery_images WHERE id = $id");
        $msg = 'Image deleted successfully.';
    } else {
        $err = 'Failed to delete image.';
    }
}

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_image'])) {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'General');

    if (empty($title) || !isset($_FILES['image'])) {
        $err = 'Please fill in all required fields.';
    } else {
        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $err = 'Upload failed. Please try again.';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $err = 'Image is too large. Maximum size is 10MB.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) {
                $err = 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.';
            } else {
                $safe_title = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
                $filename = 'img_' . $safe_title . '_' . time() . '.' . $ext;
                $destination = 'uploads/gallery/' . $filename;

                if (!is_dir('uploads/gallery')) mkdir('uploads/gallery', 0755, true);

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $title = mysqli_real_escape_string($conn, $title);
                    $category = mysqli_real_escape_string($conn, $category);
                    mysqli_query($conn, "INSERT INTO gallery_images (title, category, image) VALUES ('$title', '$category', '$filename')");
                    $msg = 'Image uploaded successfully.';
                } else {
                    $err = 'Failed to move uploaded file.';
                }
            }
        }
    }
}

$images = mysqli_query($conn, "SELECT * FROM gallery_images ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d2240">
    <title>Manage Gallery - ClassroomX</title>
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
        <a href="admin_gallery.php" class="active">Gallery</a>
        <a href="admin_students.php">Students</a>
        <a href="admin_results.php">Results</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h2>Manage Gallery</h2>
        </div>
        <a href="dashboard.php" class="btn btn-outline btn-sm">&larr; Back to Dashboard</a>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

    <div class="upload-box">
        <h3>Upload Image</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" placeholder="e.g. Annual Tech Fest 2026" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Image File *</label>
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp" required>
            </div>
            <button type="submit" name="add_image" class="btn btn-success">Upload Image</button>
        </form>
    </div>

    <?php if ($images && mysqli_num_rows($images) > 0): ?>
        <div class="gallery-grid">
            <?php while ($row = mysqli_fetch_assoc($images)): ?>
                <div class="gallery-item">
                    <img src="uploads/gallery/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" loading="lazy">
                    <div class="gallery-overlay">
                        <p><?php echo htmlspecialchars($row['title']); ?></p>
                        <a href="admin_gallery.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm btn-delete" style="margin-top:8px;padding:4px 10px;font-size:11px;">Delete</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">&#128247;</div>
            <h3>No images yet</h3>
            <p>Upload your first gallery image above.</p>
        </div>
    <?php endif; ?>
</div>

<footer><p>&copy; 2026 ClassroomX Portal</p></footer>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

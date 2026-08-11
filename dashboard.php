<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$total_notes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM notes"))['c'];
$total_branches = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT branch) as c FROM notes"))['c'];
$total_semesters = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT semester) as c FROM notes"))['c'];
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM students"))['c'];
$total_pyq = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pyq"))['c'];
$total_assignments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM assignments"))['c'];
$total_notices = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM notices"))['c'];
$total_gallery = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM gallery_images"))['c'];
$total_syllabus = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM syllabus"))['c'];
$total_practicals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM practicals"))['c'];
$total_coding = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM coding"))['c'];
$total_projects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM projects"))['c'];
$total_placement = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM placement"))['c'];
$total_exams = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM exams"))['c'];
$total_attempts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM exam_attempts"))['c'];

$notes = mysqli_query($conn, "SELECT * FROM notes ORDER BY upload_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#0d2240">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Dashboard - ClassroomX</title>
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
        <a href="dashboard.php" class="active">Dashboard</a>
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
        <a href="admin_results.php">Results</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="dashboard-header">
        <h2>Admin Dashboard</h2>
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin']); ?></span>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php
            switch($_GET['success']) {
                case 'upload': echo 'Note uploaded successfully!'; break;
                case 'delete': echo 'Note deleted successfully!'; break;
                case 'updated': echo 'Note updated successfully!'; break;
                default: echo 'Operation completed successfully!';
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php
            switch($_GET['error']) {
                case 'upload_failed': echo 'Failed to upload note. Please try again.'; break;
                case 'invalid_file': echo 'Invalid file type. Only PDF files are allowed.'; break;
                case 'delete_failed': echo 'Failed to delete note.'; break;
                case 'file_too_large': echo 'File is too large. Maximum size is 20MB.'; break;
                case 'missing_fields': echo 'Please fill in all required fields.'; break;
                default: echo 'An error occurred. Please try again.';
            }
            ?>
        </div>
    <?php endif; ?>

    <div class="dash-stats">
        <div class="dash-stat-card">
            <div class="stat-label">Total Notes</div>
            <div class="stat-value"><?php echo $total_notes; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Branches</div>
            <div class="stat-value"><?php echo $total_branches; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Semesters</div>
            <div class="stat-value"><?php echo $total_semesters; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Students</div>
            <div class="stat-value"><?php echo $total_students; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Question Papers</div>
            <div class="stat-value"><?php echo $total_pyq; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Assignments</div>
            <div class="stat-value"><?php echo $total_assignments; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Notices</div>
            <div class="stat-value"><?php echo $total_notices; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Gallery Photos</div>
            <div class="stat-value"><?php echo $total_gallery; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Syllabus Files</div>
            <div class="stat-value"><?php echo $total_syllabus; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Practical Files</div>
            <div class="stat-value"><?php echo $total_practicals; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Coding Problems</div>
            <div class="stat-value"><?php echo $total_coding; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Projects</div>
            <div class="stat-value"><?php echo $total_projects; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Placement Postings</div>
            <div class="stat-value"><?php echo $total_placement; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Exams</div>
            <div class="stat-value"><?php echo $total_exams; ?></div>
        </div>
        <div class="dash-stat-card">
            <div class="stat-label">Test Attempts</div>
            <div class="stat-value"><?php echo $total_attempts; ?></div>
        </div>
    </div>

    <div class="user-account-grid">
        <a href="dashboard.php" class="user-account-card">
            <div class="user-account-icon">&#128196;</div>
            <h3>Notes Upload</h3>
            <p>Upload &amp; manage notes</p>
        </a>
        <a href="admin_syllabus.php" class="user-account-card">
            <div class="user-account-icon">&#128214;</div>
            <h3>Syllabus Upload</h3>
            <p>Upload &amp; manage syllabus</p>
        </a>
        <a href="admin_pyq.php" class="user-account-card">
            <div class="user-account-icon">&#128218;</div>
            <h3>PYQ Upload</h3>
            <p>Previous year questions</p>
        </a>
        <a href="admin_practical.php" class="user-account-card">
            <div class="user-account-icon">&#128196;</div>
            <h3>Practical Files</h3>
            <p>Practical lists &amp; manuals</p>
        </a>
        <a href="admin_coding.php" class="user-account-card">
            <div class="user-account-icon">&#128187;</div>
            <h3>Coding Practice</h3>
            <p>Add coding problems</p>
        </a>
        <a href="admin_projects.php" class="user-account-card">
            <div class="user-account-icon">&#128736;</div>
            <h3>Projects</h3>
            <p>Manage project reports</p>
        </a>
        <a href="admin_placement.php" class="user-account-card">
            <div class="user-account-icon">&#128188;</div>
            <h3>Placement</h3>
            <p>Manage job postings</p>
        </a>
        <a href="admin_assignments.php" class="user-account-card">
            <div class="user-account-icon">&#128221;</div>
            <h3>Assignment Upload</h3>
            <p>Upload assignments</p>
        </a>
        <a href="admin_notices.php" class="user-account-card">
            <div class="user-account-icon">&#128226;</div>
            <h3>Notice Add</h3>
            <p>Post announcements</p>
        </a>
        <a href="admin_gallery.php" class="user-account-card">
            <div class="user-account-icon">&#128247;</div>
            <h3>Gallery Upload</h3>
            <p>Upload campus photos</p>
        </a>
        <a href="admin_exams.php" class="user-account-card">
            <div class="user-account-icon">&#129504;</div>
            <h3>Quiz Questions</h3>
            <p>Create exams &amp; add questions</p>
        </a>
        <a href="admin_students.php" class="user-account-card">
            <div class="user-account-icon">&#128100;</div>
            <h3>Students Manage</h3>
            <p>View &amp; remove students</p>
        </a>
        <a href="admin_results.php" class="user-account-card">
            <div class="user-account-icon">&#128200;</div>
            <h3>Test Results</h3>
            <p>See all submissions</p>
        </a>
    </div>
    <div style="height:24px;"></div>

    <div class="upload-box">
        <h3>Upload New Note</h3>
        <form method="POST" action="upload.php" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" placeholder="e.g. Data Structures Notes" required>
                </div>
                <div class="form-group">
                    <label>Semester *</label>
                    <select name="semester" required>
                        <option value="">Select Semester</option>
                        <option value="Semester 1">Semester 1</option>
                        <option value="Semester 2">Semester 2</option>
                        <option value="Semester 3">Semester 3</option>
                        <option value="Semester 4">Semester 4</option>
                        <option value="Semester 5">Semester 5</option>
                        <option value="Semester 6">Semester 6</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Branch *</label>
                    <select name="branch" required>
                        <option value="">Select Branch</option>
                        <option value="Computer Engineering">Computer Engineering</option>
                        <option value="Information Technology">Information Technology</option>
                        <option value="Electronics Engineering">Electronics Engineering</option>
                        <option value="Mechanical Engineering">Mechanical Engineering</option>
                        <option value="Civil Engineering">Civil Engineering</option>
                        <option value="Electrical Engineering">Electrical Engineering</option>
                        <option value="Automobile Engineering">Automobile Engineering</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" placeholder="Brief description (optional)">
                </div>
            </div>
            <div class="form-group">
                <label>PDF File *</label>
                <div class="upload-area">
                    <div class="upload-icon">&#128196;</div>
                    <p>Click or drag to select PDF file</p>
                    <p class="file-name"></p>
                    <input type="file" name="pdf_file" id="pdf_file" accept=".pdf" required style="display:none;">
                </div>
            </div>
            <button type="submit" class="btn btn-success">Upload Note</button>
        </form>
    </div>

    <div class="table-container">
        <h3>All Uploaded Notes (<?php echo $total_notes; ?>)</h3>
        <?php if ($notes && mysqli_num_rows($notes) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Semester</th>
                            <th>Branch</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($row = mysqli_fetch_assoc($notes)): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['semester']); ?></td>
                                <td><?php echo htmlspecialchars($row['branch']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['upload_date'])); ?></td>
                                <td class="actions">
                                    <a href="download.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Download</a>
                                    <a href="edit_note.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                                    <a href="upload.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm btn-delete">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128196;</div>
                <h3>No notes uploaded yet</h3>
                <p>Use the form above to upload your first note.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer>
    <p>&copy; 2026 ClassroomX Portal</p>
</footer>

<a href="https://wa.me/918860695666?text=Hi%20ClassroomX%2C%20I%20have%20a%20question" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M16.004 0h-.008C7.174 0 0 7.176 0 16c0 3.5 1.132 6.744 3.058 9.374L1.054 31.2l6.064-1.97A15.912 15.912 0 0016.004 32C24.826 32 32 24.822 32 16S24.826 0 16.004 0zm9.31 22.608c-.39 1.096-1.932 2.01-3.162 2.274-.844.18-1.946.322-5.656-1.216-4.746-1.966-7.798-6.79-8.036-7.108-.23-.318-1.9-2.524-1.9-4.814 0-2.29 1.204-3.416 1.63-3.884.39-.428.924-.57 1.23-.57.31 0 .618.004.886.016.284.012.664-.106 1.036.79.39.932 1.33 3.24 1.446 3.478.116.238.194.516.038.834-.156.318-.232.516-.462.794-.23.278-.484.62-.692.832-.23.238-.47.496-.2.972.27.476 1.2 1.98 2.578 3.208 1.77 1.58 3.26 2.07 3.736 2.298.374.18.792.136 1.086-.23.374-.476.836-1.262 1.304-2.02.334-.542.756-.61 1.276-.414.53.196 3.364 1.586 3.94 1.87.576.284.96.428 1.102.664.14.236.14 1.37-.25 2.464z"/></svg>
</a>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

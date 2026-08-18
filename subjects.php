<?php
session_start();
include 'db.php';

$branches = mysqli_query($conn, "SELECT DISTINCT branch FROM notes ORDER BY branch");
$semesters = mysqli_query($conn, "SELECT DISTINCT semester FROM notes ORDER BY semester");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#0d2240">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Subjects - EngiHub</title>
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
        <a href="subjects.php" class="active">Subjects</a>
        <a href="notes.php">Notes</a>
        <a href="pdfs.php">PDFs</a>
        <a href="exams.php">MCQ Quiz</a>
        <a href="pyq.php">Previous Papers</a>
        <a href="syllabus.php">Syllabus</a>
        <a href="notices.php">Notices</a>
        <a href="profile.php">Profile</a>
        <?php if (isset($_SESSION['admin'])): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php elseif (isset($_SESSION['student'])): ?>
            <a href="student_dashboard.php">My Dashboard</a>
            <a href="student_logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Admin Login</a>
            <a href="student_login.php" class="btn-login">Student Login</a>
            <a href="register.php" style="color:rgba(255,255,255,0.8);font-weight:600;">Register</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container">
    <div class="section-header">
        <h2>Select Your Branch & Semester</h2>
    </div>

    <div class="filter-bar">
        <div class="form-group">
            <label>Branch</label>
            <select id="branchFilter" onchange="filterNotes(this.value)">
                <option value="">All Branches</option>
                <?php while ($row = mysqli_fetch_assoc($branches)): ?>
                    <option value="<?php echo htmlspecialchars($row['branch']); ?>"><?php echo htmlspecialchars($row['branch']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Semester</label>
            <select id="semesterFilter" onchange="filterNotes('')">
                <option value="">All Semesters</option>
                <!-- Options will be populated by JS after branch selection -->
            </select>
        </div>
        <button class="btn btn-primary" onclick="window.location='notes.php'">Reset Filters</button>
    </div>

    <div class="cards" id="subjectsCards">
        <?php
        // Fetch notes grouped by branch/semester for display
        $notes = mysqli_query($conn, "SELECT * FROM notes ORDER BY upload_date DESC LIMIT 20");
        if ($notes && mysqli_num_rows($notes) > 0):
            $currentBranch = '';
            $currentSemester = '';
            while ($row = mysqli_fetch_assoc($notes)): ?>
                <?php if ($row['branch'] !== $currentBranch || $row['semester'] !== $currentSemester): ?>
                    <?php if ($currentBranch !== ''): ?>
                </div>
                    </div>
                <?php endif; ?>
                <div class="card" style="margin-bottom:24px;background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:var(--radius);padding:24px;backdrop-filter:blur(var(--glass-blur));-webkit-backdrop-filter:blur(var(--glass-blur));">
                    <span class="card-badge badge-sem"><?php echo htmlspecialchars($row['semester']); ?></span>
                    <span class="card-badge badge-branch"><?php echo htmlspecialchars($row['branch']); ?></span>
                    <h3 style="font-size:18px;font-weight:600;color:var(--white);margin-bottom:8px;"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p style="font-size:14px;color:var(--gray-700);margin-bottom:12px;"><?php echo !empty($row['description']) ? htmlspecialchars($row['description']) : 'No description available'; ?></p>
                    <div style="display:flex;justify-content:space-between;margin-top:12px;">
                        <a href="download.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Download</a>
                        <span style="font-size:12px;color:var(--gray-500);"><?php echo $row['upload_date']; ?></span>
                    </div>
                </div>
                <?php $currentBranch = $row['branch'];
                      $currentSemester = $row['semester'];
            endwhile;
        else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128214;</div>
                <h3>No subjects found</h3>
                <p>Upload notes first to see subjects classified.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterNotes(branch) {
    // Navigate to notes with branch filter or show appropriate content
    window.location.href = 'notes.php?branch=' + branch;
}
</script>

<footer><p>&copy; 2026 EngiHub Portal</p></footer>

<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
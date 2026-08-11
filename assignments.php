<?php
session_start();
include 'db.php';

$semesters = ['Semester 1','Semester 2','Semester 3','Semester 4','Semester 5','Semester 6'];
$branches_result = mysqli_query($conn, "SELECT DISTINCT branch FROM assignments ORDER BY branch");
$branches = [];
while ($r = mysqli_fetch_assoc($branches_result)) {
    $branches[] = $r['branch'];
}

$filter_sem = isset($_GET['semester']) ? $_GET['semester'] : '';
$filter_branch = isset($_GET['branch']) ? $_GET['branch'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = [];
if ($filter_sem !== '') {
    $where[] = "semester = '" . mysqli_real_escape_string($conn, $filter_sem) . "'";
}
if ($filter_branch !== '') {
    $where[] = "branch = '" . mysqli_real_escape_string($conn, $filter_branch) . "'";
}
if ($search !== '') {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $where[] = "(title LIKE '%$search_safe%' OR subject LIKE '%$search_safe%')";
}

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
$assignments = mysqli_query($conn, "SELECT * FROM assignments $where_sql ORDER BY deadline ASC, created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#0d2240">
    <title>Assignments - ClassroomX</title>
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
        <a href="notes.php">Notes</a>
        <a href="syllabus.php">Syllabus</a>
        <a href="pyq.php">PYQ</a>
        <a href="practical.php">Practical</a>
        <a href="coding.php">Coding</a>
        <a href="projects.php">Projects</a>
        <a href="placement.php">Placement</a>
        <a href="notices.php">Notices</a>
        <a href="exams.php">Online Test</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact Us</a>
        <?php if (isset($_SESSION['admin'])): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php elseif (isset($_SESSION['student'])): ?>
            <a href="student_dashboard.php">My Dashboard</a>
            <a href="student_logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Admin</a>
            <a href="student_login.php" class="btn-login">Student Login</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container">
    <h2 style="font-size:28px;font-weight:700;margin-bottom:8px;color:var(--white);">Assignments</h2>
    <p style="color:var(--gray-700);font-size:15px;margin-bottom:28px;">Download assignment sheets, solve them, and submit before the deadline.</p>

    <form method="GET" action="assignments.php">
        <div class="filter-bar">
            <input type="text" name="search" placeholder="Search by title or subject..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="semester">
                <option value="">All Semesters</option>
                <?php foreach ($semesters as $sem): ?>
                    <option value="<?php echo $sem; ?>" <?php echo $filter_sem === $sem ? 'selected' : ''; ?>><?php echo $sem; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="branch">
                <option value="">All Branches</option>
                <?php foreach ($branches as $br): ?>
                    <option value="<?php echo $br; ?>" <?php echo $filter_branch === $br ? 'selected' : ''; ?>><?php echo $br; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-filter">Filter</button>
            <?php if ($filter_sem !== '' || $filter_branch !== '' || $search !== ''): ?>
                <a href="assignments.php" class="btn-reset">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($assignments && mysqli_num_rows($assignments) > 0): ?>
        <p style="color:var(--gray-700);font-size:14px;margin-bottom:16px;">Showing <?php echo mysqli_num_rows($assignments); ?> assignment(s)</p>
        <div class="cards">
            <?php while ($row = mysqli_fetch_assoc($assignments)): ?>
                <?php
                $deadline_class = '';
                if (!empty($row['deadline'])) {
                    $today = date('Y-m-d');
                    $deadline_class = ($row['deadline'] < $today) ? 'deadline-past' : (($row['deadline'] === $today) ? 'deadline-today' : '');
                }
                ?>
                <div class="card">
                    <span class="card-badge badge-sem"><?php echo htmlspecialchars($row['semester']); ?></span>
                    <span class="card-badge badge-branch"><?php echo htmlspecialchars($row['branch']); ?></span>
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                    <?php if (!empty($row['description'])): ?>
                        <p class="card-desc"><?php echo htmlspecialchars($row['description']); ?></p>
                    <?php endif; ?>
                    <p class="card-desc">
                        <?php if (!empty($row['subject'])): ?>Subject: <?php echo htmlspecialchars($row['subject']); ?><br><?php endif; ?>
                        <?php if (!empty($row['deadline'])): ?>
                            Deadline: <span class="<?php echo $deadline_class; ?>" style="font-weight:600;"><?php echo date('d M Y', strtotime($row['deadline'])); ?></span>
                        <?php else: ?>
                            Deadline: No deadline
                        <?php endif; ?>
                    </p>
                    <div class="card-meta">
                        <span class="card-date">Posted: <?php echo date('d M Y', strtotime($row['created_at'])); ?></span>
                        <a href="download_assignment.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Download</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">&#128221;</div>
            <h3>No assignments found</h3>
            <p>Try adjusting your filters or check back later.</p>
        </div>
    <?php endif; ?>
</div>

<footer><p>&copy; 2026 ClassroomX Portal</p></footer>
<a href="https://wa.me/918860695666?text=Hi%20ClassroomX%2C%20I%20have%20a%20question" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M16.004 0h-.008C7.174 0 0 7.176 0 16c0 3.5 1.132 6.744 3.058 9.374L1.054 31.2l6.064-1.97A15.912 15.912 0 0016.004 32C24.826 32 32 24.822 32 16S24.826 0 16.004 0zm9.31 22.608c-.39 1.096-1.932 2.01-3.162 2.274-.844.18-1.946.322-5.656-1.216-4.746-1.966-7.798-6.79-8.036-7.108-.23-.318-1.9-2.524-1.9-4.814 0-2.29 1.204-3.416 1.63-3.884.39-.428.924-.57 1.23-.57.31 0 .618.004.886.016.284.012.664-.106 1.036.79.39.932 1.33 3.24 1.446 3.478.116.238.194.516.038.834-.156.318-.232.516-.462.794-.23.278-.484.62-.692.832-.23.238-.47.496-.2.972.27.476 1.2 1.98 2.578 3.208 1.77 1.58 3.26 2.07 3.736 2.298.374.18.792.136 1.086-.23.374-.476.836-1.262 1.304-2.02.334-.542.756-.61 1.276-.414.53.196 3.364 1.586 3.94 1.87.576.284.96.428 1.102.664.14.236.14 1.37-.25 2.464z"/></svg>
</a>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

<?php
session_start();
include 'db.php';

// Count PDFs by type
$totalNotes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM notes"))['c'];
$totalPyq = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pyq"))['c'];
$totalSyllabus = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM syllabus"))['c'];
$totalPracticals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM practicals"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#0d2240">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>PDFs - ClassroomX</title>
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
        <a href="subjects.php">Subjects</a>
        <a href="notes.php">Notes</a>
        <a href="pdfs.php" class="active">PDFs</a>
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
        <h2>Available PDFs <span style="color:var(--gray-700);font-size:12px;">(<?php echo $totalNotes + $totalPyq + $totalSyllabus + $totalPracticals; ?> total)</span></h2>
    </div>

    <div class="filter-bar" style="margin-bottom:24px;">
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <span style="font-size:13px;color:var(--gray-700);">Filter by type:</span>
            <a href="notes.php" class="btn btn-outline btn-sm">Notes</a>
            <a href="pyq.php" class="btn btn-outline btn-sm">PYQ Papers</a>
            <a href="syllabus.php" class="btn btn-outline btn-sm">Syllabus</a>
            <a href="practical.php" class="btn btn-outline btn-sm">Practicals</a>
        </div>
    </div>

    <div class="cards">
        <?php
        // Show notes PDFs
        $notes = mysqli_query($conn, "SELECT * FROM notes ORDER BY upload_date DESC LIMIT 12");
        if ($notes && mysqli_num_rows($notes) > 0):
            while ($row = mysqli_fetch_assoc($notes)): ?>
                <div class="card" style="margin-bottom:24px;background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:var(--radius);padding:24px;backdrop-filter:blur(var(--glass-blur));-webkit-backdrop-filter:blur(var(--glass-blur));">
                    <span class="card-badge badge-branch" style="background:rgba(212,168,67,0.15);color:var(--accent-gold);border-color:rgba(212,168,67,0.2);"><?php echo htmlspecialchars($row['branch']); ?></span>
                    <span class="card-badge badge-sem" style="background:rgba(77,166,255,0.15);color:var(--accent);border-color:rgba(77,166,255,0.2);"><?php echo htmlspecialchars($row['semester']); ?></span>
                    <h3 style="font-size:18px;font-weight:600;color:var(--white);margin-bottom:8px;"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p style="font-size:14px;color:var(--gray-700);margin-bottom:12px;"><?php echo !empty($row['description']) ? htmlspecialchars($row['description']) : 'No description'; ?></p>
                    <div style="display:flex;justify-content:space-between;">
                        <a href="download.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Download</a>
                        <span style="font-size:12px;color:var(--gray-500);"><?php echo date('d M Y', strtotime($row['upload_date'])); ?></span>
                    </div>
                </div>
            <?php endwhile;
        else: ?>
            <div class="empty-state">
                <div class="empty-icon">&#128196;</div>
                <h3>No notes PDFs yet</h3>
                <p>Admin can upload notes from the dashboard.</p>
            </div>
        <?php endif; ?>

        <?php
        // Show PYQ PDFs
        $pyq = mysqli_query($conn, "SELECT * FROM pyq ORDER BY upload_date DESC LIMIT 12");
        if ($pyq && mysqli_num_rows($pyq) > 0):
            while ($row = mysqli_fetch_assoc($pyq)): ?>
                <div class="card" style="margin-bottom:24px;background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:var(--radius);padding:24px;backdrop-filter:blur(var(--glass-blur));-webkit-backdrop-filter:blur(var(--glass-blur));margin-top:24px;">
                    <span class="card-badge badge-branch" style="background:rgba(212,168,67,0.15);color:var(--accent-gold);border-color:rgba(212,168,67,0.2);"><?php echo htmlspecialchars($row['branch']); ?></span>
                    <span class="card-badge badge-sem" style="background:rgba(77,166,255,0.15);color:var(--accent);border-color:rgba(77,166,255,0.2);"><?php echo htmlspecialchars($row['semester']); ?></span>
                    <h3 style="font-size:18px;font-weight:600;color:var(--white);margin-bottom:8px;"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p style="font-size:14px;color:var(--gray-700);margin-bottom:12px;"><?php echo htmlspecialchars($row['subject'] ?? ''); ?> - <?php echo htmlspecialchars($row['year']); ?></p>
                    <div style="display:flex;justify-content:space-between;">
                        <a href="download_pyq.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Download</a>
                        <span style="font-size:12px;color:var(--gray-500);"><?php echo date('d M Y', strtotime($row['upload_date'])); ?></span>
                    </div>
                </div>
            <?php endwhile;
        else: ?>
            <div class="empty-state" style="margin-top:24px;">
                <div class="empty-icon">&#128218;</div>
                <h3>No PYQ PDFs yet</h3>
                <p>Admin can upload previous year question papers from the dashboard.</p>
            </div>
        <?php endif; ?>

        <?php
        // Show syllabus PDFs
        $syllabus = mysqli_query($conn, "SELECT * FROM syllabus ORDER BY upload_date DESC LIMIT 12");
        if ($syllabus && mysqli_num_rows($syllabus) > 0):
            while ($row = mysqli_fetch_assoc($syllabus)): ?>
                <div class="card" style="margin-bottom:24px;background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:var(--radius);padding:24px;backdrop-filter:blur(var(--glass-blur));-webkit-backdrop-filter:blur(var(--glass-blur));margin-top:24px;">
                    <span class="card-badge badge-branch" style="background:rgba(212,168,67,0.15);color:var(--accent-gold);border-color:rgba(212,168,67,0.2);"><?php echo htmlspecialchars($row['branch']); ?></span>
                    <span class="card-badge badge-sem" style="background:rgba(77,166,255,0.15);color:var(--accent);border-color:rgba(77,166,255,0.2);"><?php echo htmlspecialchars($row['semester']); ?></span>
                    <h3 style="font-size:18px;font-weight:600;color:var(--white);margin-bottom:8px;"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p style="font-size:14px;color:var(--gray-700);margin-bottom:12px;"><?php echo htmlspecialchars($row['subject'] ?? ''); ?> - Semester <?php echo htmlspecialchars($row['semester']); ?></p>
                    <div style="display:flex;justify-content:space-between;">
                        <a href="download_syllabus.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Download</a>
                        <span style="font-size:12px;color:var(--gray-500);"><?php echo date('d M Y', strtotime($row['upload_date'])); ?></span>
                    </div>
                </div>
            <?php endwhile;
        else: ?>
            <div class="empty-state" style="margin-top:24px;">
                <div class="empty-icon">&#128214;</div>
                <h3>No syllabus PDFs yet</h3>
                <p>Admin can upload syllabus files from the dashboard.</p>
            </div>
        <?php endif; ?>

        <?php
        // Show practicals PDFs
        $practicals = mysqli_query($conn, "SELECT * FROM practicals ORDER BY upload_date DESC LIMIT 12");
        if ($practicals && mysqli_num_rows($practicals) > 0):
            while ($row = mysqli_fetch_assoc($practicals)): ?>
                <div class="card" style="margin-bottom:24px;background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:var(--radius);padding:24px;backdrop-filter:blur(var(--glass-blur));-webkit-backdrop-filter:blur(var(--glass-blur));margin-top:24px;">
                    <span class="card-badge badge-branch" style="background:rgba(212,168,67,0.15);color:var(--accent-gold);border-color:rgba(212,168,67,0.2);"><?php echo htmlspecialchars($row['branch']); ?></span>
                    <span class="card-badge badge-sem" style="background:rgba(77,166,255,0.15);color:var(--accent);border-color:rgba(77,166,255,0.2);"><?php echo htmlspecialchars($row['semester']); ?></span>
                    <h3 style="font-size:18px;font-weight:600;color:var(--white);margin-bottom:8px;"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p style="font-size:14px;color:var(--gray-700);margin-bottom:12px;"><?php echo htmlspecialchars($row['subject'] ?? ''); ?> - Semester <?php echo htmlspecialchars($row['semester']); ?></p>
                    <div style="display:flex;justify-content:space-between;">
                        <a href="download_practical.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Download</a>
                        <span style="font-size:12px;color:var(--gray-500);"><?php echo date('d M Y', strtotime($row['upload_date'])); ?></span>
                    </div>
                </div>
            <?php endwhile;
        else: ?>
            <div class="empty-state" style="margin-top:24px;">
                <div class="empty-icon">&#128196;</div>
                <h3>No practical PDFs yet</h3>
                <p>Admin can upload practical files from the dashboard.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer><p>&copy; 2026 ClassroomX Portal</p></footer>

<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>
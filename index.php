<?php
session_start();
include 'db.php';

$total_notes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM notes"))['c'];
$total_branches = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT branch) as c FROM notes"))['c'];
$total_semesters = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT semester) as c FROM notes"))['c'];
$total_pyq = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM pyq"))['c'];
$total_assignments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM assignments"))['c'];
$total_syllabus = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM syllabus"))['c'];
$total_practicals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM practicals"))['c'];
$total_coding = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM coding"))['c'];
$total_projects = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM projects"))['c'];
$total_placement = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM placement"))['c'];

$notices = mysqli_query($conn, "SELECT * FROM notices ORDER BY is_important DESC, created_at DESC LIMIT 4");
$gallery = mysqli_query($conn, "SELECT * FROM gallery_images ORDER BY created_at DESC LIMIT 6");

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search !== '') {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $notes_query = "SELECT * FROM notes WHERE title LIKE '%$search_safe%' OR branch LIKE '%$search_safe%' OR semester LIKE '%$search_safe%' ORDER BY upload_date DESC";
} else {
    $notes_query = "SELECT * FROM notes ORDER BY upload_date DESC LIMIT 6";
}
$notes_result = mysqli_query($conn, $notes_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#0d2240">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Engineering Student Hub - Free Study Material</title>
    <meta name="description" content="Engineering Student Hub - notes, syllabus, PYQ, practicals, coding practice, projects and placement updates for all branches and semesters.">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header-transparent" id="siteHeader">
    <a href="index.php" class="logo">
<img src="images/logo.jpg" alt="EngiHub" class="logo-img">
    EngiHub
    </a>
    <button class="menu-toggle">&#9776;</button>
    <nav>
        <a href="index.php" class="active">Home</a>
        <a href="subjects.php">Subjects</a>
        <a href="notes.php">Notes</a>
        <a href="pdfs.php">PDFs</a>

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

<section class="hero">
    <div class="hero-content">
        <h1>Welcome to the <span class="highlight">Engineering</span> Student Hub</h1>
        <p>Your one-stop portal for notes, syllabus, previous year questions, practicals, coding practice, projects and placement updates - all organized by branch and semester.</p>
        <div class="hero-stats">
            <div class="hero-stat-glass">
                <span class="stat-number"><?php echo $total_notes; ?></span>
                <span class="stat-label">Notes</span>
            </div>
            <div class="hero-stat-glass">
                <span class="stat-number"><?php echo $total_branches; ?></span>
                <span class="stat-label">Branches</span>
            </div>
            <div class="hero-stat-glass">
                <span class="stat-number"><?php echo $total_semesters; ?></span>
                <span class="stat-label">Semesters</span>
            </div>
            <div class="hero-stat-glass">
                <span class="stat-number"><?php echo $total_pyq; ?></span>
                <span class="stat-label">PYQ Papers</span>
            </div>
            <div class="hero-stat-glass">
                <span class="stat-number"><?php echo $total_assignments; ?></span>
                <span class="stat-label">Assignments</span>
            </div>
            <div class="hero-stat-glass">
                <span class="stat-number"><?php echo $total_syllabus; ?></span>
                <span class="stat-label">Syllabus</span>
            </div>
            <div class="hero-stat-glass">
                <span class="stat-number"><?php echo $total_practicals; ?></span>
                <span class="stat-label">Practicals</span>
            </div>
            <div class="hero-stat-glass">
                <span class="stat-number"><?php echo $total_coding; ?></span>
                <span class="stat-label">Coding</span>
            </div>
            <div class="hero-stat-glass">
                <span class="stat-number"><?php echo $total_projects; ?></span>
                <span class="stat-label">Projects</span>
            </div>
            <div class="hero-stat-glass">
                <span class="stat-number"><?php echo $total_placement; ?></span>
                <span class="stat-label">Placements</span>
            </div>
        </div>
    </div>
</section>

<div class="search-section">
    <form method="GET" action="index.php">
        <div class="search-box">
            <input type="text" name="search" placeholder="Search notes by title, branch, or semester..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
        </div>
    </form>
</div>

<div class="collage-slider-section">
    <div class="collage-slider-track">
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(26,53,80,0.7),rgba(26,53,80,0.3));"><span>&#128218;</span><p>Classroom Notes</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(212,168,67,0.6),rgba(212,168,67,0.25));"><span>&#127891;</span><p>Graduation Day</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(45,138,78,0.6),rgba(45,138,78,0.25));"><span>&#128187;</span><p>Lab Sessions</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(26,53,80,0.65),rgba(38,75,110,0.3));"><span>&#128205;</span><p>Campus Life</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(212,168,67,0.55),rgba(15,34,54,0.35));"><span>&#128214;</span><p>Library Studies</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(26,53,80,0.6),rgba(45,138,78,0.2));"><span>&#127942;</span><p>Achievements</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(15,34,54,0.7),rgba(212,168,67,0.2));"><span>&#128101;</span><p>Group Projects</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(45,138,78,0.5),rgba(26,53,80,0.3));"><span>&#127775;</span><p>Student Life</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(26,53,80,0.7),rgba(26,53,80,0.3));"><span>&#128218;</span><p>Classroom Notes</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(212,168,67,0.6),rgba(212,168,67,0.25));"><span>&#127891;</span><p>Graduation Day</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(45,138,78,0.6),rgba(45,138,78,0.25));"><span>&#128187;</span><p>Lab Sessions</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(26,53,80,0.65),rgba(38,75,110,0.3));"><span>&#128205;</span><p>Campus Life</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(212,168,67,0.55),rgba(15,34,54,0.35));"><span>&#128214;</span><p>Library Studies</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(26,53,80,0.6),rgba(45,138,78,0.2));"><span>&#127942;</span><p>Achievements</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(15,34,54,0.7),rgba(212,168,67,0.2));"><span>&#128101;</span><p>Group Projects</p></div></div>
        <div class="collage-slide"><div class="collage-card" style="background:linear-gradient(135deg,rgba(45,138,78,0.5),rgba(26,53,80,0.3));"><span>&#127775;</span><p>Student Life</p></div></div>
    </div>
    <div class="collage-overlay-left"></div>
    <div class="collage-overlay-right"></div>
</div>

<div class="container">
    <div class="section-header">
        <h2><?php echo $search !== '' ? 'Search Results for "' . htmlspecialchars($search) . '"' : 'Recently Uploaded Notes'; ?></h2>
        <?php if ($search === ''): ?>
            <a href="notes.php">View All &rarr;</a>
        <?php endif; ?>
    </div>

    <?php if ($notes_result && mysqli_num_rows($notes_result) > 0): ?>
        <div class="cards">
            <?php while ($row = mysqli_fetch_assoc($notes_result)): ?>
                <div class="card">
                    <span class="card-badge badge-sem"><?php echo htmlspecialchars($row['semester']); ?></span>
                    <span class="card-badge badge-branch"><?php echo htmlspecialchars($row['branch']); ?></span>
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                    <?php if (!empty($row['description'])): ?>
                        <p class="card-desc"><?php echo htmlspecialchars($row['description']); ?></p>
                    <?php endif; ?>
                    <div class="card-meta">
                        <span class="card-date"><?php echo date('d M Y', strtotime($row['upload_date'])); ?></span>
                        <a href="download.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Download</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">&#128214;</div>
            <h3><?php echo $search !== '' ? 'No notes found' : 'No notes uploaded yet'; ?></h3>
            <p><?php echo $search !== '' ? 'Try a different search term.' : 'Admin can upload notes from the dashboard.'; ?></p>
        </div>
    <?php endif; ?>

    <div style="margin-top:48px;">
        <div class="section-header fade-in">
            <h2>Latest Notices</h2>
            <a href="notices.php">View All &rarr;</a>
        </div>
        <div style="display:flex;flex-direction:column;gap:14px;">
            <?php if ($notices && mysqli_num_rows($notices) > 0): ?>
                <?php while ($n = mysqli_fetch_assoc($notices)): ?>
                    <div class="notice-card <?php echo $n['is_important'] ? 'notice-important' : ''; ?> slide-up">
                        <?php if ($n['is_important']): ?>
                            <span class="notice-important-badge">&#128226; Important</span>
                        <?php endif; ?>
                        <div class="notice-head">
                            <h3><?php echo htmlspecialchars($n['title']); ?></h3>
                            <span class="notice-category"><?php echo htmlspecialchars($n['category']); ?></span>
                        </div>
                        <?php if (!empty($n['content'])): ?>
                            <p class="notice-content"><?php echo nl2br(htmlspecialchars(mb_substr($n['content'], 0, 200))); ?><?php echo mb_strlen($n['content']) > 200 ? '...' : ''; ?></p>
                        <?php endif; ?>
                        <div class="notice-date">Posted on <?php echo date('d M Y', strtotime($n['created_at'])); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">&#128226;</div>
                    <h3>No notices yet</h3>
                    <p>Check back later for announcements.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div style="margin-top:48px;">
        <div class="section-header fade-in">
            <h2>Campus Gallery</h2>
            <a href="gallery.php">View All &rarr;</a>
        </div>
        <?php if ($gallery && mysqli_num_rows($gallery) > 0): ?>
            <div class="gallery-grid">
                <?php while ($g = mysqli_fetch_assoc($gallery)): ?>
                    <div class="gallery-item slide-up">
                        <img src="uploads/gallery/<?php echo htmlspecialchars($g['image']); ?>" alt="<?php echo htmlspecialchars($g['title']); ?>" loading="lazy">
                        <div class="gallery-overlay"><p><?php echo htmlspecialchars($g['title']); ?></p></div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="gallery-grid">
                <div class="gallery-item slide-up">
                    <div class="gallery-placeholder" style="background:linear-gradient(135deg,rgba(77,166,255,0.35),rgba(13,34,64,0.85));">
                        <span>&#127961; Campus View</span>
                    </div>
                    <div class="gallery-overlay"><p>Campus View</p></div>
                </div>
                <div class="gallery-item slide-up">
                    <div class="gallery-placeholder" style="background:linear-gradient(135deg,rgba(45,138,78,0.35),rgba(13,34,64,0.85));">
                        <span>&#128218; Library</span>
                    </div>
                    <div class="gallery-overlay"><p>Library</p></div>
                </div>
                <div class="gallery-item slide-up">
                    <div class="gallery-placeholder" style="background:linear-gradient(135deg,rgba(212,168,67,0.35),rgba(13,34,64,0.85));">
                        <span>&#128187; Computer Lab</span>
                    </div>
                    <div class="gallery-overlay"><p>Computer Lab</p></div>
                </div>
                <div class="gallery-item slide-up">
                    <div class="gallery-placeholder" style="background:linear-gradient(135deg,rgba(77,166,255,0.25),rgba(212,168,67,0.2));">
                        <span>&#127891; Seminar Hall</span>
                    </div>
                    <div class="gallery-overlay"><p>Seminar Hall</p></div>
                </div>
                <div class="gallery-item slide-up">
                    <div class="gallery-placeholder" style="background:linear-gradient(135deg,rgba(45,138,78,0.25),rgba(212,168,67,0.2));">
                        <span>&#128295; Workshop</span>
                    </div>
                    <div class="gallery-overlay"><p>Workshop</p></div>
                </div>
                <div class="gallery-item slide-up">
                    <div class="gallery-placeholder" style="background:linear-gradient(135deg,rgba(192,57,43,0.2),rgba(13,34,64,0.7));">
                        <span>&#9917; Sports Area</span>
                    </div>
                    <div class="gallery-overlay"><p>Sports Area</p></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div style="margin-top:48px;">
        <div class="section-header fade-in">
            <h2>Explore the Hub</h2>
        </div>
        <div class="user-account-grid">
            <a href="notes.php" class="user-account-card">
                <div class="user-account-icon">&#128196;</div>
                <h3>Notes</h3>
                <p>Semester-wise study notes</p>
            </a>
            <a href="syllabus.php" class="user-account-card">
                <div class="user-account-icon">&#128214;</div>
                <h3>Syllabus</h3>
                <p>Download latest syllabus</p>
            </a>
            <a href="pyq.php" class="user-account-card">
                <div class="user-account-icon">&#128218;</div>
                <h3>Previous Year Questions</h3>
                <p>Practice old exam papers</p>
            </a>
            <a href="practical.php" class="user-account-card">
                <div class="user-account-icon">&#128196;</div>
                <h3>Practical Files</h3>
                <p>Practical lists &amp; manuals</p>
            </a>
            <a href="coding.php" class="user-account-card">
                <div class="user-account-icon">&#128187;</div>
                <h3>Coding Practice</h3>
                <p>Problems with solutions</p>
            </a>
            <a href="projects.php" class="user-account-card">
                <div class="user-account-icon">&#128736;</div>
                <h3>Projects</h3>
                <p>Project ideas &amp; reports</p>
            </a>
            <a href="placement.php" class="user-account-card">
                <div class="user-account-icon">&#128188;</div>
                <h3>Placement Corner</h3>
                <p>Drives &amp; job openings</p>
            </a>
            <a href="assignments.php" class="user-account-card">
                <div class="user-account-icon">&#128221;</div>
                <h3>Assignments</h3>
                <p>Download &amp; submit</p>
            </a>
            
        </div>
    </div>

    <div class="features">
        <div class="feature-card">
            <div class="feature-icon blue">&#128196;</div>
            <h3>PDF Downloads</h3>
            <p>All notes, syllabus, PYQ and practical files are available as PDFs for offline reading.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon orange">&#128269;</div>
            <h3>Quick Search</h3>
            <p>Find material instantly by title, branch, or semester using the search feature.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon green">&#128241;</div>
            <h3>Mobile Friendly</h3>
            <p>Access everything on any device - phone, tablet, or desktop computer.</p>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2026 EngiHub Portal. All rights reserved.</p>
</footer>

<a href="https://wa.me/918860695666?text=Hi%20EngiHub%2C%20I%20have%20a%20question" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M16.004 0h-.008C7.174 0 0 7.176 0 16c0 3.5 1.132 6.744 3.058 9.374L1.054 31.2l6.064-1.97A15.912 15.912 0 0016.004 32C24.826 32 32 24.822 32 16S24.826 0 16.004 0zm9.31 22.608c-.39 1.096-1.932 2.01-3.162 2.274-.844.18-1.946.322-5.656-1.216-4.746-1.966-7.798-6.79-8.036-7.108-.23-.318-1.9-2.524-1.9-4.814 0-2.29 1.204-3.416 1.63-3.884.39-.428.924-.57 1.23-.57.31 0 .618.004.886.016.284.012.664-.106 1.036.79.39.932 1.33 3.24 1.446 3.478.116.238.194.516.038.834-.156.318-.232.516-.462.794-.23.278-.484.62-.692.832-.23.238-.47.496-.2.972.27.476 1.2 1.98 2.578 3.208 1.77 1.58 3.26 2.07 3.736 2.298.374.18.792.136 1.086-.23.374-.476.836-1.262 1.304-2.02.334-.542.756-.61 1.276-.414.53.196 3.364 1.586 3.94 1.87.576.284.96.428 1.102.664.14.236.14 1.37-.25 2.464z"/></svg>
</a>

<div id="galleryLightbox" class="lightbox">
    <button class="lightbox-close">&times;</button>
    <img class="lightbox-img" src="" alt="">
</div>

<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

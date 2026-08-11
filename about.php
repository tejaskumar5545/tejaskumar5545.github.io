<?php
session_start();
include 'db.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#0d2240">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>About Us - ClassroomX</title>
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
        <a href="about.php" class="active">About</a>
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
            <a href="register.php" style="color:rgba(255,255,255,0.8);font-weight:600;">Register</a>
        <?php endif; ?>
    </nav>
</header>

<section class="hero" style="padding:60px 40px 80px;">
    <div class="hero-content">
        <h1>About <span class="highlight">ClassroomX</span></h1>
        <p>A modern digital platform built for diploma college students to access, share, and download study notes easily.</p>
    </div>
</section>



<div class="container">
    <div style="max-width:900px;margin:0 auto;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center;margin-bottom:60px;">
            <div>
                <h2 style="font-size:28px;font-weight:700;margin-bottom:16px;color:var(--white);">Our Mission</h2>
                <p style="color:var(--gray-700);font-size:16px;line-height:1.8;margin-bottom:16px;">
                    ClassroomX is designed to bridge the gap between students and quality study material. We believe every student deserves easy access to organized, semester-wise notes regardless of their branch or background.
                </p>
                <p style="color:var(--gray-700);font-size:16px;line-height:1.8;">
                    Our platform allows admin to upload PDF notes which students can browse, search, and download for free. We support all diploma engineering branches and semesters.
                </p>
            </div>
            <div style="background:var(--glass-bg);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid var(--glass-border-strong);border-radius:var(--radius-lg);padding:40px;text-align:center;box-shadow:var(--shadow-glass);">
                <div style="font-size:64px;margin-bottom:16px;">&#127891;</div>
                <h3 style="font-size:22px;font-weight:700;color:var(--white);margin-bottom:8px;">Empowering Students</h3>
                <p style="color:var(--gray-700);font-size:14px;">Making education accessible through technology</p>
            </div>
        </div>

        <h2 style="font-size:28px;font-weight:700;margin-bottom:24px;text-align:center;color:var(--white);">How It Works</h2>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-bottom:60px;">
            <div class="feature-card">
                <div class="feature-icon blue">&#127919;</div>
                <h3>Register</h3>
                <p>Create a free student account with your name, email, semester, and branch.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon orange">&#128270;</div>
                <h3>Browse & Search</h3>
                <p>Find notes by title, branch, or semester using our search and filter system.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon green">&#128229;</div>
                <h3>Download</h3>
                <p>Download PDF notes for free and study offline anytime, anywhere.</p>
            </div>
        </div>

        <div style="margin-bottom:60px;">
            <div class="director-card">
                <div class="director-avatar">
                    <span>&#128100;</span>
                </div>
                <div class="director-info">
                    <h3>Tejas Kumar</h3>
                    <p class="director-title">Director</p>
                    <p class="director-desc">Leading ClassroomX with a vision to make quality education accessible to every diploma student. Committed to building digital tools that empower the next generation of engineers.</p>
                    <div class="director-contact">
                        <a href="mailto:kumartejas884@gmail.com" class="director-contact-item">
                            <span>&#128231;</span> kumartejas884@gmail.com
                        </a>
                        <a href="tel:+918860695666" class="director-contact-item">
                            <span>&#128222;</span> 8860695666
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div style="background:var(--glass-bg);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid var(--glass-border-strong);border-radius:var(--radius-lg);padding:48px;text-align:center;color:var(--white);box-shadow:var(--shadow-glass-lg);">
            <h2 style="font-size:28px;font-weight:700;margin-bottom:12px;">Supported Branches</h2>
            <p style="opacity:0.8;margin-bottom:28px;font-size:16px;">We cover all major diploma engineering branches</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;max-width:800px;margin:0 auto;">
                <div style="background:rgba(255,255,255,0.1);padding:14px;border-radius:var(--radius-sm);font-weight:500;">&#128187; Computer Engineering</div>
                <div style="background:rgba(255,255,255,0.1);padding:14px;border-radius:var(--radius-sm);font-weight:500;">&#128187; Information Technology</div>
                <div style="background:rgba(255,255,255,0.1);padding:14px;border-radius:var(--radius-sm);font-weight:500;">&#128225; Electronics Engineering</div>
                <div style="background:rgba(255,255,255,0.1);padding:14px;border-radius:var(--radius-sm);font-weight:500;">&#9881; Mechanical Engineering</div>
                <div style="background:rgba(255,255,255,0.1);padding:14px;border-radius:var(--radius-sm);font-weight:500;">&#127959; Civil Engineering</div>
                <div style="background:rgba(255,255,255,0.1);padding:14px;border-radius:var(--radius-sm);font-weight:500;">&#9889; Electrical Engineering</div>
                <div style="background:rgba(255,255,255,0.1);padding:14px;border-radius:var(--radius-sm);font-weight:500;">&#128663; Automobile Engineering</div>
            </div>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2026 ClassroomX. All rights reserved.</p>
</footer>

<a href="https://wa.me/918860695666?text=Hi%20ClassroomX%2C%20I%20have%20a%20question" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M16.004 0h-.008C7.174 0 0 7.176 0 16c0 3.5 1.132 6.744 3.058 9.374L1.054 31.2l6.064-1.97A15.912 15.912 0 0016.004 32C24.826 32 32 24.822 32 16S24.826 0 16.004 0zm9.31 22.608c-.39 1.096-1.932 2.01-3.162 2.274-.844.18-1.946.322-5.656-1.216-4.746-1.966-7.798-6.79-8.036-7.108-.23-.318-1.9-2.524-1.9-4.814 0-2.29 1.204-3.416 1.63-3.884.39-.428.924-.57 1.23-.57.31 0 .618.004.886.016.284.012.664-.106 1.036.79.39.932 1.33 3.24 1.446 3.478.116.238.194.516.038.834-.156.318-.232.516-.462.794-.23.278-.484.62-.692.832-.23.238-.47.496-.2.972.27.476 1.2 1.98 2.578 3.208 1.77 1.58 3.26 2.07 3.736 2.298.374.18.792.136 1.086-.23.374-.476.836-1.262 1.304-2.02.334-.542.756-.61 1.276-.414.53.196 3.364 1.586 3.94 1.87.576.284.96.428 1.102.664.14.236.14 1.37-.25 2.464z"/></svg>
</a>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

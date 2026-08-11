<?php
session_start();
if (!isset($_SESSION['student'])) {
    header("Location: student_login.php");
    exit;
}
include 'db.php';

$student_id = $_SESSION['student_id'];
$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE id = $student_id"));

if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#0d2240">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>My Profile - ClassroomX</title>
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
        <a href="student_dashboard.php" class="active">My Dashboard</a>
        <a href="student_logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h2>My Profile</h2>
            <p style="color:var(--gray-700);font-size:14px;margin-top:4px;">Manage your account details</p>
        </div>
        <a href="student_dashboard.php" class="btn btn-outline btn-sm">&larr; Back to Dashboard</a>
    </div>

    <?php if (isset($msg)): ?>
        <div class="alert alert-success">
            <?php
            switch($msg) {
                case 'updated': echo 'Profile updated successfully.'; break;
                case 'password_updated': echo 'Password changed successfully.'; break;
                default: echo htmlspecialchars($msg);
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php
            switch($_GET['error']) {
                case 'current_wrong': echo 'Current password is incorrect.'; break;
                case 'mismatch': echo 'New passwords do not match.'; break;
                case 'short': echo 'New password must be at least 6 characters.'; break;
                case 'missing': echo 'Please fill in all fields.'; break;
                default: echo 'An error occurred. Please try again.';
            }
            ?>
        </div>
    <?php endif; ?>

    <div class="profile-grid">
        <div class="login-box">
            <h3 style="margin-bottom:20px;">&#128100; Personal Details</h3>
            <form method="POST" action="student_profile_process.php">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" value="<?php echo htmlspecialchars($student['email']); ?>" disabled style="opacity:0.6;cursor:not-allowed;">
                    <small style="color:var(--gray-500);font-size:12px;">Email cannot be changed</small>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Semester</label>
                        <select name="semester" required>
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="Semester <?php echo $i; ?>" <?php echo $student['semester'] === "Semester $i" ? 'selected' : ''; ?>>Semester <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Branch</label>
                        <select name="branch" required>
                            <?php
                            $branches = ['Computer Engineering', 'Information Technology', 'Electronics Engineering', 'Mechanical Engineering', 'Civil Engineering', 'Electrical Engineering', 'Automobile Engineering'];
                            foreach ($branches as $b):
                            ?>
                                <option value="<?php echo $b; ?>" <?php echo $student['branch'] === $b ? 'selected' : ''; ?>><?php echo $b; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
            </form>
        </div>

        <div class="login-box">
            <h3 style="margin-bottom:20px;">&#128274; Change Password</h3>
            <form method="POST" action="student_password_process.php">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" placeholder="Enter current password" required>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Enter new password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Update Password</button>
            </form>

            <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--glass-border);">
                <h3 style="margin-bottom:12px;">Account Info</h3>
                <p style="font-size:13px;color:var(--gray-700);">
                    Member since: <strong><?php echo date('d M Y', strtotime($student['created_at'])); ?></strong>
                </p>
            </div>
        </div>
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

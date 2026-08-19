<?php
require_once 'db.php';
if (isStudent()) { header("Location: dashboard.php"); exit; }
if (isAdmin()) { header("Location: admin/"); exit; }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($conn, $_POST['full_name'] ?? '');
    $email = sanitize($conn, $_POST['email'] ?? '');
    $college = sanitize($conn, $_POST['college_name'] ?? '');
    $branch = sanitize($conn, $_POST['branch'] ?? '');
    $semester = sanitize($conn, $_POST['semester'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($branch)) $errors[] = "Branch is required";
    if (empty($semester)) $errors[] = "Semester is required";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
    if ($password !== $confirm) $errors[] = "Passwords do not match";
    if (empty($errors)) {
        $check = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) $errors[] = "Email already registered";
        $check->close();
    }
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO students (full_name, email, college_name, branch, semester, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $full_name, $email, $college, $branch, $semester, $hashed);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#2563eb">
    <title>Register - EngiHub</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex;flex-direction:column}
        .navbar{width:100%;height:70px;background:white;display:flex;align-items:center;justify-content:space-between;padding:0 6%;box-shadow:0 2px 10px rgba(0,0,0,.08);position:sticky;top:0;z-index:100}
        .logo{font-size:28px;font-weight:bold;color:#2563eb}.logo span{color:#111827}
        .nav-links{display:flex;gap:25px;list-style:none;align-items:center}.nav-links a{text-decoration:none;color:#333;font-weight:500;transition:color .2s}.nav-links a:hover{color:#2563eb}.nav-links a.active{color:#2563eb;font-weight:700}
        .login-btn{background:#2563eb;color:white!important;padding:10px 20px;border-radius:8px}.login-btn:hover{background:#1d4ed8}
        .menu-toggle{display:none;background:none;border:none;font-size:28px;cursor:pointer;color:#111827}
        .auth-wrapper{flex:1;display:flex;align-items:center;justify-content:center;padding:30px 6%}
        .auth-container{display:flex;max-width:950px;width:100%;border-radius:18px;overflow:hidden;box-shadow:0 12px 40px rgba(0,0,0,.1);background:white}
        .auth-side{flex:0 0 360px;background:linear-gradient(135deg,#1e3a5f,#2563eb);color:white;padding:44px 32px;display:flex;flex-direction:column;justify-content:center}
        .auth-side h2{font-size:26px;font-weight:800;margin-bottom:12px;line-height:1.3}.auth-side p{font-size:14px;opacity:.85;line-height:1.7;margin-bottom:24px}
        .side-features{list-style:none}.side-features li{display:flex;align-items:center;gap:10px;font-size:14px;opacity:.9;padding:7px 0}.side-features li span{font-size:18px}
        .auth-form-section{flex:1;padding:36px 38px;display:flex;flex-direction:column;justify-content:center}
        .auth-form-section h2{font-size:22px;font-weight:800;color:#111827;margin-bottom:3px}.auth-form-section .subtitle{font-size:13px;color:#6b7280;margin-bottom:22px}
        .form-group{margin-bottom:14px}.form-group label{display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px}
        .input-wrapper{position:relative}.input-wrapper input,.input-wrapper select{width:100%;padding:11px 13px;border:2px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;color:#111827;outline:none;transition:border-color .2s;appearance:auto}
        .input-wrapper input:focus,.input-wrapper select:focus{border-color:#2563eb}
        .toggle-pass{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:18px;cursor:pointer;color:#9ca3af;padding:4px}
        .toggle-pass:hover{color:#374151}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .submit-btn{width:100%;padding:13px;background:#2563eb;color:white;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s;margin-top:6px}.submit-btn:hover{background:#1d4ed8}
        .auth-footer{text-align:center;font-size:14px;color:#6b7280;margin-top:18px}.auth-footer a{color:#2563eb;font-weight:700;text-decoration:none}.auth-footer a:hover{text-decoration:underline}
        .alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px;font-weight:500}.alert-danger{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
        .footer{background:#111827;color:white;padding:20px 6%;text-align:center;margin-top:auto}.footer p{font-size:13px;color:#6b7280}
        @media(max-width:768px){.navbar{height:60px;padding:0 20px}.logo{font-size:23px}.nav-links{display:none;position:absolute;top:60px;left:0;right:0;background:white;flex-direction:column;padding:16px 20px;box-shadow:0 8px 24px rgba(0,0,0,.1);gap:0}.nav-links.open{display:flex}.nav-links a{padding:14px 0;border-bottom:1px solid #f3f4f6}.menu-toggle{display:block}.auth-wrapper{padding:20px 16px}.auth-container{flex-direction:column}.auth-side{flex:none;padding:28px 22px}.auth-form-section{padding:24px 20px}.form-row{grid-template-columns:1fr}}
    </style>
</head>
<body>
<nav class="navbar"><a href="index.html" class="logo">Engi<span>Hub</span></a><button class="menu-toggle" id="menuToggle">&#9776;</button><ul class="nav-links" id="navLinks"><li><a href="index.html">Home</a></li><li><a href="syllabus.html">Syllabus</a></li><li><a href="coding.html">Coding</a></li><li><a href="login.php">Login</a></li><li><a href="register.php" class="login-btn">Register</a></li></ul></nav>
<div class="auth-wrapper"><div class="auth-container">
    <div class="auth-side"><h2>Join EngiHub Today</h2><p>Create your free account and access everything an engineering student needs.</p>
        <ul class="side-features"><li><span>&#128218;</span> 500+ study resources</li><li><span>&#128196;</span> Previous year papers</li><li><span>&#128295;</span> Lab manuals &amp; practicals</li><li><span>&#128187;</span> Coding practice hub</li><li><span>&#127919;</span> Placement preparation</li></ul></div>
    <div class="auth-form-section">
        <h2>Create Your Account</h2><p class="subtitle">Fill in your details to get started.</p>
        <?php if (!empty($errors)): ?><div class="alert alert-danger"><?php foreach ($errors as $e) echo $e.'<br>'; ?></div><?php endif; ?>
        <form method="POST" action="register.php" novalidate>
            <div class="form-group"><label>Full Name</label><div class="input-wrapper"><input type="text" name="full_name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required></div></div>
            <div class="form-group"><label>Email Address</label><div class="input-wrapper"><input type="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required></div></div>
            <div class="form-group"><label>College Name</label><div class="input-wrapper"><input type="text" name="college_name" placeholder="Your college name" value="<?php echo htmlspecialchars($_POST['college_name'] ?? ''); ?>"></div></div>
            <div class="form-row">
                <div class="form-group"><label>Branch</label><div class="input-wrapper"><select name="branch" required><option value="">Select Branch</option><option value="CSE">CSE</option><option value="ECE">ECE</option><option value="ME">Mechanical</option><option value="CE">Civil</option><option value="EE">Electrical</option></select></div></div>
                <div class="form-group"><label>Semester</label><div class="input-wrapper"><select name="semester" required><option value="">Select Sem</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option></select></div></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Password</label><div class="input-wrapper"><input type="password" id="regPass" name="password" placeholder="Min 6 characters" required><button type="button" class="toggle-pass" onclick="toggleP('regPass',this)">&#128065;</button></div></div>
                <div class="form-group"><label>Confirm Password</label><div class="input-wrapper"><input type="password" id="regPass2" name="confirm_password" placeholder="Re-enter password" required></div></div>
            </div>
            <button type="submit" class="submit-btn">Create Account</button>
        </form>
        <div class="auth-footer">Already have an account? <a href="login.php">Login here</a></div>
    </div>
</div></div>
<footer class="footer"><p>&copy; 2026 EngiHub. All rights reserved.</p></footer>
<script>
document.getElementById('menuToggle').addEventListener('click',function(){document.getElementById('navLinks').classList.toggle('open')});
function toggleP(id,btn){var i=document.getElementById(id);i.type=i.type==='password'?'text':'password';btn.innerHTML=i.type==='password'?'&#128065;':'&#128064;'}
</script>
</body></html>

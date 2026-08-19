<?php
require_once 'db.php';
if (isStudent()) { header("Location: dashboard.php"); exit; }
if (isAdmin()) { header("Location: admin/"); exit; }
$error = '';
if (isset($_SESSION['success_msg'])) { $success = $_SESSION['success_msg']; unset($_SESSION['success_msg']); } else { $success = ''; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $loginType = $_POST['login_type'] ?? 'student';
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        if ($loginType === 'admin') {
            $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['username'];
                    header("Location: admin/");
                    exit;
                }
            }
            $error = "Invalid admin credentials";
            $stmt->close();
        } else {
            $stmt = $conn->prepare("SELECT * FROM students WHERE email = ? AND is_active = 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $student = $result->fetch_assoc();
                if (password_verify($password, $student['password'])) {
                    $_SESSION['student_id'] = $student['id'];
                    $_SESSION['student_name'] = $student['full_name'];
                    $_SESSION['student_email'] = $student['email'];
                    $_SESSION['student_branch'] = $student['branch'];
                    $_SESSION['student_semester'] = $student['semester'];
                    header("Location: dashboard.php");
                    exit;
                }
            }
            $error = "Invalid email or password";
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#2563eb">
    <title>Login - EngiHub</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex;flex-direction:column}
        .navbar{width:100%;height:70px;background:white;display:flex;align-items:center;justify-content:space-between;padding:0 6%;box-shadow:0 2px 10px rgba(0,0,0,.08);position:sticky;top:0;z-index:100}
        .logo{font-size:28px;font-weight:bold;color:#2563eb}.logo span{color:#111827}
        .nav-links{display:flex;gap:25px;list-style:none;align-items:center}.nav-links a{text-decoration:none;color:#333;font-weight:500;transition:color .2s}.nav-links a:hover{color:#2563eb}
        .login-btn{background:#2563eb;color:white!important;padding:10px 20px;border-radius:8px}.login-btn:hover{background:#1d4ed8}
        .menu-toggle{display:none;background:none;border:none;font-size:28px;cursor:pointer;color:#111827}
        .auth-wrapper{flex:1;display:flex;align-items:center;justify-content:center;padding:30px 6%}
        .auth-container{display:flex;max-width:950px;width:100%;border-radius:18px;overflow:hidden;box-shadow:0 12px 40px rgba(0,0,0,.1);background:white}
        .auth-side{flex:0 0 360px;background:linear-gradient(135deg,#1e3a5f,#2563eb);color:white;padding:44px 32px;display:flex;flex-direction:column;justify-content:center}
        .auth-side h2{font-size:26px;font-weight:800;margin-bottom:12px;line-height:1.3}.auth-side p{font-size:14px;opacity:.85;line-height:1.7;margin-bottom:24px}
        .side-features{list-style:none}.side-features li{display:flex;align-items:center;gap:10px;font-size:14px;opacity:.9;padding:7px 0}.side-features li span{font-size:18px}
        .auth-form-section{flex:1;padding:36px 38px;display:flex;flex-direction:column;justify-content:center}
        .auth-form-section h2{font-size:22px;font-weight:800;color:#111827;margin-bottom:3px}.auth-form-section .subtitle{font-size:13px;color:#6b7280;margin-bottom:22px}
        .form-group{margin-bottom:16px}.form-group label{display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px}
        .input-wrapper{position:relative}.input-wrapper input{width:100%;padding:11px 13px;border:2px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;color:#111827;outline:none;transition:border-color .2s}.input-wrapper input:focus{border-color:#2563eb}
        .toggle-pass{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:18px;cursor:pointer;color:#9ca3af;padding:4px}
        .toggle-pass:hover{color:#374151}
        .remember-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;font-size:13px}
        .remember-row label{display:flex;align-items:center;gap:6px;cursor:pointer;color:#6b7280}
        .remember-row a{color:#2563eb;text-decoration:none;font-weight:600}.remember-row a:hover{text-decoration:underline}
        .submit-btn{width:100%;padding:13px;background:#2563eb;color:white;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s}.submit-btn:hover{background:#1d4ed8}
        .divider{display:flex;align-items:center;gap:12px;margin:20px 0;font-size:13px;color:#9ca3af}.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e5e7eb}
        .social-login{display:flex;gap:10px}.social-btn{flex:1;padding:11px;border:2px solid #e5e7eb;border-radius:10px;background:white;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px;transition:border-color .2s;color:#374151}.social-btn:hover{border-color:#2563eb}
        .auth-footer{text-align:center;font-size:14px;color:#6b7280;margin-top:18px}.auth-footer a{color:#2563eb;font-weight:700;text-decoration:none}.auth-footer a:hover{text-decoration:underline}
        .alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px;font-weight:500}
        .alert-danger{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
        .alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
        .footer{background:#111827;color:white;padding:20px 6%;text-align:center;margin-top:auto}.footer p{font-size:13px;color:#6b7280}
        @media(max-width:768px){.navbar{height:60px;padding:0 20px}.logo{font-size:23px}.nav-links{display:none;position:absolute;top:60px;left:0;right:0;background:white;flex-direction:column;padding:16px 20px;box-shadow:0 8px 24px rgba(0,0,0,.1);gap:0}.nav-links.open{display:flex}.nav-links a{padding:14px 0;border-bottom:1px solid #f3f4f6}.menu-toggle{display:block}.auth-wrapper{padding:20px 16px}.auth-container{flex-direction:column}.auth-side{flex:none;padding:28px 22px}.auth-form-section{padding:24px 20px}}
    </style>
</head>
<body>
<nav class="navbar"><a href="index.html" class="logo">Engi<span>Hub</span></a><button class="menu-toggle" id="menuToggle">&#9776;</button><ul class="nav-links" id="navLinks"><li><a href="index.html">Home</a></li><li><a href="syllabus.html">Syllabus</a></li><li><a href="coding.html">Coding</a></li><li><a href="login.php" class="login-btn">Login</a></li><li><a href="register.php">Register</a></li></ul></nav>
<div class="auth-wrapper"><div class="auth-container">
    <div class="auth-side"><h2>Welcome Back!</h2><p>Login to access your notes, papers, practicals, and continue your learning journey with EngiHub.</p>
        <ul class="side-features"><li><span>&#128218;</span> Access all study materials</li><li><span>&#128202;</span> Track your progress</li><li><span>&#128241;</span> Responsive on all devices</li><li><span>&#128274;</span> Secure &amp; fast platform</li></ul></div>
    <div class="auth-form-section">
        <h2>Login to EngiHub</h2><p class="subtitle">Enter your credentials to access your account.</p>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST" action="login.php" novalidate>
            <div class="form-group"><label>Login As</label><div class="input-wrapper"><select name="login_type" style="width:100%;padding:11px 13px;border:2px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;color:#111827;background:white"><option value="student">Student</option><option value="admin">Admin</option></select></div></div>
            <div class="form-group"><label>Email / Username</label><div class="input-wrapper"><input type="text" name="email" id="loginEmail" placeholder="Enter email or username" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required></div></div>
            <div class="form-group"><label>Password</label><div class="input-wrapper"><input type="password" id="loginPass" name="password" placeholder="Enter your password" required><button type="button" class="toggle-pass" onclick="toggleP('loginPass',this)">&#128065;</button></div></div>
            <div class="remember-row"><label><input type="checkbox" name="remember"> Remember me</label><a href="#">Forgot password?</a></div>
            <button type="submit" class="submit-btn">Login</button>
        </form>
        <div class="auth-footer">Don't have an account? <a href="register.php">Register now</a></div>
    </div>
</div></div>
<footer class="footer"><p>&copy; 2026 EngiHub. All rights reserved.</p></footer>
<script>
document.getElementById('menuToggle').addEventListener('click',function(){document.getElementById('navLinks').classList.toggle('open')});
function toggleP(id,btn){var i=document.getElementById(id);i.type=i.type==='password'?'text':'password';btn.innerHTML=i.type==='password'?'&#128065;':'&#128064;'}
</script>
</body></html>

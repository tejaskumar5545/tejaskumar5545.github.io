<?php
require_once 'db.php';
if (isStudent()) { header("Location: dashboard.php"); exit; }
if (isAdmin()) { header("Location: admin/"); exit; }
$csrf = generateCSRFToken();
$error = '';
if (isset($_SESSION['success_msg'])) { $success = $_SESSION['success_msg']; unset($_SESSION['success_msg']); } else { $success = ''; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please try again.";
    } else {
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#2563eb">
    <title>Login - EngiHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter','Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex;flex-direction:column}
        .navbar{width:100%;height:70px;background:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 6%;box-shadow:0 1px 3px rgba(0,0,0,.06);position:sticky;top:0;z-index:100}
        .logo{font-size:28px;font-weight:800;color:#2563eb;text-decoration:none;letter-spacing:-.5px}.logo span{color:#0f172a}
        .nav-links{display:flex;gap:28px;list-style:none;align-items:center}.nav-links a{text-decoration:none;color:#475569;font-weight:500;transition:color .2s;font-size:14px}.nav-links a:hover{color:#2563eb}
        .nav-links .btn-login{background:#2563eb;color:#fff!important;padding:10px 22px;border-radius:10px;font-weight:600}.nav-links .btn-login:hover{background:#1d4ed8}
        .menu-toggle{display:none;background:none;border:none;font-size:26px;cursor:pointer;color:#0f172a}
        .auth-wrapper{flex:1;display:flex;align-items:center;justify-content:center;padding:32px 6%}
        .auth-container{display:flex;max-width:960px;width:100%;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.08);background:#fff;animation:fadeUp .6s cubic-bezier(.16,1,.3,1)}
        @keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
        .auth-side{flex:0 0 380px;background:linear-gradient(160deg,#0f172a 0%,#1e3a5f 45%,#2563eb 100%);color:#fff;padding:48px 36px;display:flex;flex-direction:column;justify-content:center;position:relative;overflow:hidden}
        .auth-side::before{content:'';position:absolute;top:-60px;right:-60px;width:200px;height:200px;background:rgba(255,255,255,.04);border-radius:50%}
        .auth-side::after{content:'';position:absolute;bottom:-80px;left:-40px;width:240px;height:240px;background:rgba(56,189,248,.06);border-radius:50%}
        .auth-side h2{font-size:28px;font-weight:800;margin-bottom:12px;line-height:1.3;position:relative;letter-spacing:-.3px}
        .auth-side p{font-size:14px;opacity:.8;line-height:1.7;margin-bottom:28px;position:relative}
        .side-features{list-style:none;position:relative}.side-features li{display:flex;align-items:center;gap:12px;font-size:14px;opacity:.85;padding:9px 0}.side-features li span{font-size:20px;width:30px;text-align:center;flex-shrink:0}
        .auth-form{flex:1;padding:40px 42px;display:flex;flex-direction:column;justify-content:center}
        .auth-form h2{font-size:24px;font-weight:800;color:#0f172a;margin-bottom:4px;letter-spacing:-.3px}.auth-form .subtitle{font-size:14px;color:#64748b;margin-bottom:24px}
        .form-group{margin-bottom:18px}.form-group label{display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:7px}
        .input-wrapper{position:relative}
        .input-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:16px;color:#94a3b8;pointer-events:none;z-index:1}
        .input-wrapper input,.input-wrapper select{width:100%;padding:13px 14px 13px 42px;border:2px solid #e2e8f0;border-radius:12px;font-size:14px;font-family:inherit;color:#0f172a;outline:none;transition:all .25s;background:#fff}
        .input-wrapper input:focus,.input-wrapper select:focus{border-color:#2563eb;box-shadow:0 0 0 4px rgba(37,99,235,.08)}
        .input-wrapper input:focus ~ .input-icon{color:#2563eb}
        .input-wrapper select{padding-left:14px;appearance:auto;cursor:pointer}
        .toggle-pass{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:18px;cursor:pointer;color:#94a3b8;padding:4px;transition:color .2s;z-index:2}.toggle-pass:hover{color:#334155}
        .remember-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;font-size:13px}
        .remember-row label{display:flex;align-items:center;gap:8px;cursor:pointer;color:#64748b}
        .remember-row label input{accent-color:#2563eb;width:16px;height:16px}
        .remember-row a{color:#2563eb;text-decoration:none;font-weight:600;transition:color .2s}.remember-row a:hover{color:#1d4ed8}
        .submit-btn{width:100%;padding:14px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .25s;box-shadow:0 4px 14px rgba(37,99,235,.3)}.submit-btn:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(37,99,235,.35)}
        .divider{display:flex;align-items:center;gap:14px;margin:22px 0;font-size:13px;color:#94a3b8;font-weight:500}.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e2e8f0}
        .social-login{display:flex;gap:10px}.social-btn{flex:1;padding:12px;border:2px solid #e2e8f0;border-radius:12px;background:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;color:#334155}.social-btn:hover{border-color:#2563eb;background:#f8fafc;transform:translateY(-1px)}
        .auth-footer{text-align:center;font-size:14px;color:#64748b;margin-top:20px}.auth-footer a{color:#2563eb;font-weight:700;text-decoration:none}.auth-footer a:hover{text-decoration:underline}
        .alert{padding:14px 16px;border-radius:10px;font-size:13px;margin-bottom:18px;font-weight:500;display:flex;align-items:flex-start;gap:8px;animation:pop .3s cubic-bezier(.16,1,.3,1);line-height:1.5}
        @keyframes pop{0%{transform:scale(.95);opacity:0}100%{transform:scale(1);opacity:1}}
        .alert-danger{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
        .alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
        .alert-icon{font-size:16px;flex-shrink:0;margin-top:1px}
        .footer{background:#0f172a;color:#fff;padding:20px 6%;text-align:center;margin-top:auto}.footer p{font-size:13px;color:#64748b}
        @media(max-width:768px){
            .navbar{height:60px;padding:0 20px}.logo{font-size:23px}
            .nav-links{display:none;position:absolute;top:60px;left:0;right:0;background:#fff;flex-direction:column;padding:16px 20px;box-shadow:0 8px 30px rgba(0,0,0,.08);gap:0}.nav-links.open{display:flex}.nav-links a{padding:14px 0;border-bottom:1px solid #f1f5f9}.menu-toggle{display:block}
            .auth-wrapper{padding:16px 12px}.auth-container{flex-direction:column;border-radius:16px}
            .auth-side{flex:none;padding:32px 24px}
            .auth-form{padding:28px 20px}
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.html" class="logo">Engi<span>Hub</span></a>
    <button class="menu-toggle" id="menuToggle">&#9776;</button>
    <ul class="nav-links" id="navLinks">
        <li><a href="index.html">Home</a></li>
        <li><a href="syllabus.html">Syllabus</a></li>
        <li><a href="coding.html">Coding</a></li>
        <li><a href="register.php">Register</a></li>
        <li><a href="login.php" class="btn-login">Login</a></li>
    </ul>
</nav>

<div class="auth-wrapper">
    <div class="auth-container">
        <div class="auth-side">
            <h2>Welcome Back!</h2>
            <p>Login to access your notes, papers, practicals, and continue your learning journey with EngiHub.</p>
            <ul class="side-features">
                <li><span>&#128218;</span> Access all study materials</li>
                <li><span>&#128202;</span> Track your progress</li>
                <li><span>&#128241;</span> Responsive on all devices</li>
                <li><span>&#128274;</span> Secure &amp; fast platform</li>
            </ul>
        </div>
        <div class="auth-form">
            <h2>Login to EngiHub</h2>
            <p class="subtitle">Enter your credentials to access your account.</p>

            <?php if ($success): ?>
                <div class="alert alert-success"><span class="alert-icon">&#10003;</span><span><?php echo htmlspecialchars($success); ?></span></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><span class="alert-icon">&#9888;</span><span><?php echo htmlspecialchars($error); ?></span></div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>
                <?php csrfField(); ?>
                <div class="form-group">
                    <label>Login As</label>
                    <div class="input-wrapper">
                        <select name="login_type" style="padding-left:14px">
                            <option value="student">&#127891; Student</option>
                            <option value="admin">&#128188; Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email / Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon">&#9993;</span>
                        <input type="text" name="email" placeholder="Enter email or username" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">&#128274;</span>
                        <input type="password" id="loginPass" name="password" placeholder="Enter your password" required>
                        <button type="button" class="toggle-pass" onclick="toggleP('loginPass',this)">&#128065;</button>
                    </div>
                </div>
                <div class="remember-row">
                    <label><input type="checkbox" name="remember"> Remember me</label>
                    <a href="#">Forgot password?</a>
                </div>
                <button type="submit" class="submit-btn">Login &#10148;</button>
            </form>

            <div class="divider"><span>or</span></div>
            <div class="social-login">
                <button type="button" class="social-btn" onclick="alert('Google sign-in coming soon!')">&#128269; Continue with Google</button>
                <button type="button" class="social-btn" onclick="alert('GitHub sign-in coming soon!')">&#128187; Continue with GitHub</button>
            </div>

            <div class="auth-footer">Don't have an account? <a href="register.php">Register now</a></div>
        </div>
    </div>
</div>

<footer class="footer"><p>&copy; 2026 EngiHub. All rights reserved.</p></footer>
<script>
document.getElementById('menuToggle').addEventListener('click',function(){document.getElementById('navLinks').classList.toggle('open')});
function toggleP(id,btn){var i=document.getElementById(id);i.type=i.type==='password'?'text':'password';btn.innerHTML=i.type==='password'?'&#128065;':'&#128064;'}
</script>
</body>
</html>

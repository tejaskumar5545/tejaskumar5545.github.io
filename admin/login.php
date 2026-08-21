<?php
require_once '../db.php';
require_once __DIR__ . '/init-admin-tables.php';

if (isAdmin()) { header("Location: dashboard.php"); exit; }

$error = '';
$old_username = '';
$locked_out = false;
$lock_remaining = 0;

if (isset($_SESSION['admin_lockout_until']) && time() < $_SESSION['admin_lockout_until']) {
    $locked_out = true;
    $lock_remaining = $_SESSION['admin_lockout_until'] - time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Security validation failed. Please try again.';
    } else if ($locked_out) {
        $minutes = ceil($lock_remaining / 60);
        $error = "Account temporarily locked. Try again in {$minutes} minute" . ($minutes !== 1 ? 's' : '') . ".";
    } else {
        $identifier = trim($_POST['identifier'] ?? '');
        $password  = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            $error = 'All fields are required.';
        } else {
            if (!rateLimit('admin_login', 5, 900)) {
                $_SESSION['admin_lockout_until'] = time() + 900;
                $error = 'Too many failed attempts. Account locked for 15 minutes.';
                $locked_out = true;
                $lock_remaining = 900;
            } else {
                $stmt = $conn->prepare("SELECT id, full_name, username, email, password_hash, role, account_status FROM admin_users WHERE (email = ? OR username = ?) AND account_status = 'active' LIMIT 1");
                $stmt->bind_param("ss", $identifier, $identifier);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $admin = $result->fetch_assoc();

                    if (password_verify($password, $admin['password_hash'])) {
                        if (!in_array($admin['role'], ['super_admin', 'admin', 'editor'])) {
                            $error = 'Invalid login credentials.';
                        } else {
                            session_regenerate_id(true);
                            $_SESSION['admin_id']       = $admin['id'];
                            $_SESSION['admin_name']     = $admin['full_name'] ?: $admin['username'];
                            $_SESSION['admin_username'] = $admin['username'];
                            $_SESSION['admin_email']    = $admin['email'];
                            $_SESSION['admin_role']     = $admin['role'];
                            $_SESSION['login_ip']       = $_SERVER['REMOTE_ADDR'];
                            $_SESSION['login_time']     = time();
                            unset($_SESSION['admin_login_attempts'], $_SESSION['admin_lockout_until']);

                            $stmt->close();
                            $upd = $conn->prepare("UPDATE admin_users SET last_login = NOW(), last_ip = ? WHERE id = ?");
                            $ip = $_SERVER['REMOTE_ADDR'];
                            $upd->bind_param("si", $ip, $admin['id']);
                            $upd->execute();
                            $upd->close();

                            $log = $conn->prepare("INSERT INTO activity_logs (user_type, user_id, action, details, ip_address) VALUES ('admin', ?, 'login', 'Admin login successful', ?)");
                            $log->bind_param("is", $admin['id'], $ip);
                            $log->execute();
                            $log->close();

                            header("Location: dashboard.php");
                            exit;
                        }
                    } else {
                        $error = 'Invalid login credentials.';
                    }
                } else {
                    $error = 'Invalid login credentials.';
                }
                $stmt->close();
            }
        }
    }
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="theme-color" content="#0f172a">
<meta name="robots" content="noindex, nofollow">
<title>Admin Login - EngiHub</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#0b1120;color:#111827;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;overflow:hidden}
body::before{content:'';position:fixed;top:-40%;left:-20%;width:600px;height:600px;background:radial-gradient(circle,rgba(37,99,235,.08) 0%,transparent 70%);pointer-events:none}
body::after{content:'';position:fixed;bottom:-30%;right:-15%;width:500px;height:500px;background:radial-gradient(circle,rgba(96,165,250,.06) 0%,transparent 70%);pointer-events:none}

.bg-grid{position:fixed;top:0;left:0;right:0;bottom:0;background-image:linear-gradient(rgba(255,255,255,.02) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.02) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;z-index:0}

.login-wrapper{position:relative;z-index:1;width:100%;max-width:440px;animation:fadeUp .6s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}

.login-card{background:#ffffff;border-radius:20px;padding:44px 38px 36px;box-shadow:0 25px 60px rgba(0,0,0,.4),0 0 0 1px rgba(255,255,255,.05);position:relative;overflow:hidden}
.login-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#1e40af,#2563eb,#60a5fa)}

.logo-section{text-align:center;margin-bottom:28px}
.logo-text{font-size:30px;font-weight:800;letter-spacing:-.5px}.logo-text .engi{color:#111827}.logo-text .hub{color:#2563eb}
.admin-badge{display:inline-flex;align-items:center;gap:6px;margin-top:10px;padding:5px 14px;background:#0f172a;color:#60a5fa;border-radius:24px;font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase}
.admin-badge .shield{font-size:13px}

.login-heading{text-align:center;margin-bottom:6px;font-size:22px;font-weight:800;color:#111827}
.login-sub{text-align:center;font-size:13px;color:#6b7280;margin-bottom:26px;line-height:1.5}

.alert{padding:14px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:18px;display:flex;align-items:flex-start;gap:10px;line-height:1.4}
.alert-error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.alert-lockout{background:#fff7ed;color:#ea580c;border:1px solid #fed7aa}
.alert .alert-icon{font-size:16px;flex-shrink:0;margin-top:1px}

.form-group{margin-bottom:18px}
.form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:7px}

.input-wrap{position:relative}
.input-wrap .field-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:16px;color:#9ca3af;pointer-events:none;transition:color .2s}
.input-wrap input{width:100%;padding:13px 48px 13px 44px;border:2px solid #e5e7eb;border-radius:12px;font-size:14px;color:#111827;background:#f9fafb;outline:none;transition:all .2s;font-family:inherit}
.input-wrap input:focus{border-color:#2563eb;box-shadow:0 0 0 4px rgba(37,99,235,.08);background:#fff}
.input-wrap input:focus ~ .field-icon{color:#2563eb}
.input-wrap input.error-border{border-color:#dc2626}

.toggle-pw{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;padding:4px;transition:color .2s;user-select:none}
.toggle-pw:hover{color:#374151}

.options-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px}
.remember-wrap{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:#6b7280;user-select:none}
.remember-wrap input[type="checkbox"]{width:16px;height:16px;accent-color:#2563eb;cursor:pointer;border-radius:4px}
.forgot-link{font-size:13px;color:#2563eb;font-weight:600;text-decoration:none;transition:color .2s}
.forgot-link:hover{color:#1d4ed8;text-decoration:underline}

.login-btn{width:100%;padding:14px;background:linear-gradient(135deg,#1e40af,#2563eb);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:8px;position:relative;overflow:hidden;font-family:inherit}
.login-btn:hover{background:linear-gradient(135deg,#1d4ed8,#1e40af);transform:translateY(-1px);box-shadow:0 6px 20px rgba(37,99,235,.35)}
.login-btn:active{transform:translateY(0)}
.login-btn:disabled{opacity:.7;cursor:not-allowed;transform:none;box-shadow:none}
.login-btn .btn-text{transition:opacity .2s}
.login-btn .spinner{display:none;width:20px;height:20px;border:2.5px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}
.login-btn.loading .btn-text{opacity:0}
.login-btn.loading .spinner{display:block;position:absolute}
@keyframes spin{to{transform:rotate(360deg)}}

.divider{display:flex;align-items:center;gap:12px;margin:22px 0}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e5e7eb}
.divider span{font-size:12px;color:#9ca3af;font-weight:500}

.student-login{text-align:center}
.student-login a{display:inline-flex;align-items:center;gap:6px;padding:11px 20px;background:#f3f4f6;color:#374151;border-radius:12px;text-decoration:none;font-size:13px;font-weight:600;transition:all .2s;width:100%;justify-content:center;border:1px solid #e5e7eb}
.student-login a:hover{background:#e5e7eb;border-color:#d1d5db}
.student-login a .arrow{transition:transform .2s}
.student-login a:hover .arrow{transform:translateX(3px)}

.security-note{text-align:center;margin-top:20px;padding-top:18px;border-top:1px solid #f3f4f6}
.security-note p{font-size:11px;color:#9ca3af;display:flex;align-items:center;justify-content:center;gap:5px}

.corner-badge{position:fixed;bottom:20px;right:20px;z-index:1}
.corner-badge span{font-size:11px;color:rgba(255,255,255,.25);font-weight:500;letter-spacing:.5px}

@media(max-width:480px){
    .login-card{padding:32px 24px 28px;border-radius:16px}
    .logo-text{font-size:26px}
    .login-heading{font-size:19px}
    .login-sub{font-size:12px}
    .options-row{flex-direction:column;gap:12px;align-items:flex-start}
    .student-login a{font-size:12px;padding:10px 16px}
}
</style>
</head>
<body>
<div class="bg-grid"></div>

<div class="login-wrapper">
<div class="login-card">
    <div class="logo-section">
        <div class="logo-text"><span class="engi">Engi</span><span class="hub">Hub</span></div>
        <div class="admin-badge"><span class="shield">&#128737;</span> ADMIN PANEL</div>
    </div>

    <h2 class="login-heading">Admin Login</h2>
    <p class="login-sub">Secure access to the EngiHub administration panel.</p>

    <?php if ($error): ?>
        <div class="alert <?php echo $locked_out ? 'alert-lockout' : 'alert-error'; ?>">
            <span class="alert-icon">&#9888;</span>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" id="loginForm" autocomplete="off" novalidate>
        <?php echo csrfField(); ?>

        <div class="form-group">
            <label for="identifier">Email or Username</label>
            <div class="input-wrap">
                <span class="field-icon">&#128231;</span>
                <input type="text" id="identifier" name="identifier" placeholder="Enter your email or username" required autofocus autocomplete="username" value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrap">
                <span class="field-icon">&#128274;</span>
                <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                <button type="button" class="toggle-pw" onclick="togglePassword(this)" aria-label="Show password" tabindex="-1">&#128065;</button>
            </div>
        </div>

        <div class="options-row">
            <label class="remember-wrap">
                <input type="checkbox" name="remember" value="1"> Remember this device
            </label>
            <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
        </div>

        <button type="submit" class="login-btn" id="loginBtn">
            <span class="btn-text">Secure Admin Login &#10148;</span>
            <span class="spinner"></span>
        </button>
    </form>

    <div class="divider"><span>or</span></div>

    <div class="student-login">
        <a href="../login.html">
            <span>&#8592;</span> Back to Student Login
        </a>
    </div>

    <div class="security-note">
        <p>&#128274; Encrypted &amp; secured connection</p>
    </div>
</div>
</div>

<div class="corner-badge"><span>EngiHub v2.0</span></div>

<script>
function togglePassword(btn){
    const inp=document.getElementById('password');
    if(inp.type==='password'){inp.type='text';btn.innerHTML='&#128064;';btn.setAttribute('aria-label','Hide password')}
    else{inp.type='password';btn.innerHTML='&#128065;';btn.setAttribute('aria-label','Show password')}
}
document.getElementById('loginForm').addEventListener('submit',function(e){
    const btn=document.getElementById('loginBtn');
    if(!btn.classList.contains('loading')){
        btn.classList.add('loading');
        btn.disabled=true;
    }
});
</script>
</body>
</html>

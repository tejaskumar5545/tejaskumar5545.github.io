<?php
require_once '../db.php';
if (isAdmin()) { header("Location: index.php"); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM admin WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['username'];
                header("Location: index.php"); exit;
            } else { $error = 'Invalid username or password.'; }
        } else { $error = 'Invalid username or password.'; }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="theme-color" content="#0f172a">
<title>Admin Login - EngiHub</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#0f172a;color:#111827;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.login-card{background:white;border-radius:16px;padding:40px 36px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.3);animation:fadeUp .5s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.login-logo{text-align:center;margin-bottom:24px}
.login-logo .logo{font-size:28px;font-weight:800;color:#2563eb}.login-logo .logo span{color:#111827}
.login-logo .badge{display:inline-block;margin-top:8px;font-size:10px;background:#0f172a;color:#60a5fa;padding:3px 12px;border-radius:20px;font-weight:700;letter-spacing:.5px}
.login-title{text-align:center;font-size:20px;font-weight:800;margin-bottom:4px}
.login-sub{text-align:center;font-size:13px;color:#6b7280;margin-bottom:24px}
.alert{padding:12px 16px;border-radius:8px;font-size:13px;font-weight:500;margin-bottom:16px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;display:flex;align-items:center;gap:8px}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
.input-wrap{position:relative}
.input-wrap .icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:16px;color:#9ca3af;pointer-events:none}
.input-wrap input{width:100%;padding:12px 14px 12px 40px;border:2px solid #e5e7eb;border-radius:10px;font-size:14px;color:#111827;outline:none;transition:all .2s}
.input-wrap input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.login-btn{width:100%;padding:13px;background:#2563eb;color:white;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;transition:all .2s;margin-top:8px}
.login-btn:hover{background:#1d4ed8;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.3)}
.login-footer{text-align:center;margin-top:20px;font-size:13px;color:#6b7280}
.login-footer a{color:#2563eb;font-weight:600}
</style>
</head>
<body>
<div class="login-card">
<div class="login-logo"><span class="logo">Engi<span>Hub</span></span><div class="badge">ADMIN PANEL</div></div>
<h2 class="login-title">Admin Login</h2>
<p class="login-sub">Sign in to manage EngiHub content and students</p>
<?php if($error):?><div class="alert">&#9888; <?php echo htmlspecialchars($error);?></div><?php endif;?>
<form method="POST">
<div class="form-group"><label>Username</label><div class="input-wrap"><span class="icon">&#128100;</span><input type="text" name="username" placeholder="Enter admin username" required autofocus value="<?php echo htmlspecialchars($_POST['username']??''); ?>"></div></div>
<div class="form-group"><label>Password</label><div class="input-wrap"><span class="icon">&#128274;</span><input type="password" name="password" placeholder="Enter password" required></div></div>
<button type="submit" class="login-btn">Sign In &#10148;</button>
</form>
<div class="login-footer"><a href="../index.html">&#8592; Back to EngiHub</a></div>
</div>
</body>
</html>

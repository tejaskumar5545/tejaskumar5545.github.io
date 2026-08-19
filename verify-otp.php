<?php
require_once 'db.php';

if (isStudent()) { header("Location: dashboard.php"); exit; }
if (isAdmin()) { header("Location: admin/"); exit; }

if (empty($_SESSION['reg_pending'])) {
    header("Location: register.php");
    exit;
}

$csrf = generateCSRFToken();
$pending = $_SESSION['reg_pending'];
$email = $pending['email'];
$otp = $_SESSION['reg_otp'] ?? '';
$error = '';
$success = '';
$resendMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please try again.";
    } elseif (!rateLimit('otp_verify', 5, 300)) {
        $error = "Too many attempts. Please wait 5 minutes.";
    } else {
        $userOtp = trim($_POST['otp_code'] ?? '');
        if (empty($userOtp) || strlen($userOtp) !== 6) {
            $error = "Please enter the complete 6-digit OTP.";
        } elseif (time() > ($_SESSION['reg_otp_expires'] ?? 0)) {
            $error = "OTP has expired. Please request a new one.";
        } elseif (!hash_equals($otp, $userOtp)) {
            $error = "Invalid OTP. Please check and try again.";
        } else {
            $p = $_SESSION['reg_pending'];
            $stmt = $conn->prepare("INSERT INTO students (full_name, email, mobile, password, college_name, branch, semester, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("sssssss", $p['full_name'], $p['email'], $p['mobile'], $p['password'], $p['college_name'], $p['branch'], $p['semester']);
            if ($stmt->execute()) {
                unset($_SESSION['reg_pending'], $_SESSION['reg_otp'], $_SESSION['reg_otp_expires'], $_SESSION['csrf_token']);
                $_SESSION['success_msg'] = "Registration successful! Your account has been created. Please login to continue.";
                header("Location: login.php");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['resend']) && empty($error)) {
    if (!rateLimit('otp_resend', 3, 120)) {
        $resendMsg = "Please wait before requesting a new OTP.";
    } else {
        $newOtp = generateOTP();
        $_SESSION['reg_otp'] = $newOtp;
        $_SESSION['reg_otp_expires'] = time() + 600;
        $success = "A new OTP has been generated. For demo, your OTP is: <strong>$newOtp</strong>";
    }
}

$maskedEmail = preg_replace('/(.{2})(.*)(@.*)/', '$1****$3', $email);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#2563eb">
    <title>Verify Email - EngiHub</title>
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
        .auth-wrapper{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 6%}
        .verify-card{max-width:500px;width:100%;background:#fff;border-radius:20px;padding:44px;box-shadow:0 20px 60px rgba(0,0,0,.08);text-align:center;animation:fadeUp .6s cubic-bezier(.16,1,.3,1)}
        @keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
        @keyframes pop{0%{transform:scale(.95);opacity:0}100%{transform:scale(1);opacity:1}}
        @keyframes spin{to{transform:rotate(360deg)}}
        .verify-icon{width:80px;height:80px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:36px;box-shadow:0 8px 24px rgba(37,99,235,.12)}
        .verify-card h2{font-size:24px;font-weight:800;color:#0f172a;margin-bottom:8px;letter-spacing:-.3px}
        .verify-card .subtitle{font-size:14px;color:#64748b;margin-bottom:32px;line-height:1.6}
        .verify-card .subtitle strong{color:#2563eb;font-weight:700}
        .otp-inputs{display:flex;gap:12px;justify-content:center;margin-bottom:24px}
        .otp-inputs input{width:54px;height:60px;text-align:center;font-size:24px;font-weight:800;border:2px solid #e2e8f0;border-radius:14px;outline:none;transition:all .25s;font-family:inherit;color:#0f172a;background:#f8fafc}
        .otp-inputs input:focus{border-color:#2563eb;background:#fff;box-shadow:0 0 0 4px rgba(37,99,235,.1);transform:translateY(-2px)}
        .otp-inputs input.filled{border-color:#10b981;background:#f0fdf4;color:#10b981}
        .verify-btn{width:100%;padding:15px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 14px rgba(37,99,235,.3)}
        .verify-btn:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 8px 24px rgba(37,99,235,.35)}
        .verify-btn:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none}
        .verify-btn .spinner{display:none;width:20px;height:20px;border:2.5px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}
        .verify-btn.loading .spinner{display:block}.verify-btn.loading .btn-text{display:none}
        .resend-row{margin-top:20px;font-size:13px;color:#64748b}
        .resend-row a{color:#2563eb;font-weight:600;text-decoration:none;transition:color .2s}.resend-row a:hover{color:#1d4ed8;text-decoration:underline}
        .alert{padding:14px 16px;border-radius:10px;font-size:13px;margin-bottom:20px;font-weight:500;text-align:left;display:flex;align-items:flex-start;gap:8px;animation:pop .3s cubic-bezier(.16,1,.3,1);line-height:1.5}
        .alert-danger{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
        .alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
        .alert-icon{font-size:16px;flex-shrink:0;margin-top:1px}
        .demo-note{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px 16px;font-size:12px;color:#92400e;margin-top:20px;line-height:1.6;text-align:left}
        .demo-note strong{color:#b45309}
        .back-link{margin-top:20px;font-size:13px}.back-link a{color:#64748b;text-decoration:none;font-weight:500;transition:color .2s}.back-link a:hover{color:#2563eb}
        .footer{background:#0f172a;color:#fff;padding:20px 6%;text-align:center;margin-top:auto}.footer p{font-size:13px;color:#64748b}
        @media(max-width:768px){
            .navbar{height:60px;padding:0 20px}.logo{font-size:23px}
            .nav-links{display:none;position:absolute;top:60px;left:0;right:0;background:#fff;flex-direction:column;padding:16px 20px;box-shadow:0 8px 30px rgba(0,0,0,.08);gap:0}.nav-links.open{display:flex}.nav-links a{padding:14px 0;border-bottom:1px solid #f1f5f9}.menu-toggle{display:block}
            .verify-card{padding:28px 20px;border-radius:16px}.otp-inputs input{width:46px;height:52px;font-size:20px}
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
        <li><a href="login.php" class="btn-login">Login</a></li>
    </ul>
</nav>

<div class="auth-wrapper">
    <div class="verify-card">
        <div class="verify-icon">&#128274;</div>
        <h2>Verify Your Email</h2>
        <p class="subtitle">We've sent a 6-digit verification code to<br><strong><?php echo htmlspecialchars($maskedEmail); ?></strong></p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <span class="alert-icon">&#9888;</span>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">
                <span class="alert-icon">&#10003;</span>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($resendMsg): ?>
            <div class="alert alert-danger">
                <span class="alert-icon">&#9888;</span>
                <span><?php echo htmlspecialchars($resendMsg); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="verify-otp.php" id="otpForm">
            <?php csrfField(); ?>
            <div class="otp-inputs" id="otpInputs">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" id="otp0" autofocus>
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp1">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp2">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp3">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp4">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" id="otp5">
            </div>
            <input type="hidden" name="otp_code" id="otpHidden">
            <button type="submit" class="verify-btn" id="verifyBtn" disabled>
                <span class="btn-text">Verify &amp; Complete Registration &#10148;</span>
                <span class="spinner"></span>
            </button>
        </form>

        <div class="resend-row">Didn't receive the code? <a href="verify-otp.php?resend=1">Resend OTP</a></div>
        <div class="back-link"><a href="register.php">&#8592; Back to Registration</a></div>

        <div class="demo-note"><strong>&#9889; Demo Mode:</strong> Since this is a demo, the OTP is shown on screen. In production, it would be sent to your email or SMS.</div>
    </div>
</div>

<footer class="footer"><p>&copy; 2026 EngiHub. All rights reserved.</p></footer>

<script>
document.getElementById('menuToggle').addEventListener('click',function(){document.getElementById('navLinks').classList.toggle('open')});

var inputs=document.querySelectorAll('.otp-inputs input'),hidden=document.getElementById('otpHidden'),verifyBtn=document.getElementById('verifyBtn');
inputs.forEach(function(inp,i){
    inp.addEventListener('input',function(){
        this.value=this.value.replace(/[^0-9]/g,'');
        if(this.value.length===1){this.classList.add('filled');if(i<5)inputs[i+1].focus()}
        else{this.classList.remove('filled')}
        updateCode();
    });
    inp.addEventListener('keydown',function(e){
        if(e.key==='Backspace'&&!this.value&&i>0){inputs[i-1].focus();inputs[i-1].value='';inputs[i-1].classList.remove('filled');updateCode()}
        if(e.key==='Enter'){document.getElementById('otpForm').submit()}
    });
    inp.addEventListener('paste',function(e){
        e.preventDefault();
        var pasted=(e.clipboardData||window.clipboardData).getData('text').replace(/[^0-9]/g,'').slice(0,6);
        pasted.split('').forEach(function(c,j){if(inputs[j]){inputs[j].value=c;inputs[j].classList.add('filled')}});
        if(pasted.length>0)inputs[Math.min(pasted.length,5)].focus();
        updateCode();
    });
});
function updateCode(){
    var code='';inputs.forEach(function(i){code+=i.value});
    hidden.value=code;verifyBtn.disabled=code.length!==6;
}
document.getElementById('otpForm').addEventListener('submit',function(e){
    if(verifyBtn.disabled){e.preventDefault();return}
    verifyBtn.classList.add('loading');verifyBtn.disabled=true;
});
</script>
</body>
</html>

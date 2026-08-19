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
                $_SESSION['success_msg'] = "Registration successful! You can now login.";
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
    <title>Verify OTP - EngiHub</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex;flex-direction:column}
        .navbar{width:100%;height:70px;background:white;display:flex;align-items:center;justify-content:space-between;padding:0 6%;box-shadow:0 2px 10px rgba(0,0,0,.08);position:sticky;top:0;z-index:100}
        .logo{font-size:28px;font-weight:bold;color:#2563eb;text-decoration:none}.logo span{color:#111827}
        .nav-links{display:flex;gap:25px;list-style:none;align-items:center}.nav-links a{text-decoration:none;color:#333;font-weight:500;transition:color .2s;font-size:14px}.nav-links a:hover{color:#2563eb}
        .login-btn{background:#2563eb;color:white!important;padding:10px 20px;border-radius:8px}.login-btn:hover{background:#1d4ed8}
        .menu-toggle{display:none;background:none;border:none;font-size:28px;cursor:pointer;color:#111827}
        .auth-wrapper{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 6%}
        .verify-card{max-width:480px;width:100%;background:white;border-radius:18px;padding:40px;box-shadow:0 12px 40px rgba(0,0,0,.1);text-align:center;animation:fadeUp .5s ease-out}
        @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .verify-icon{width:72px;height:72px;background:#eff6ff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:32px}
        .verify-card h2{font-size:22px;font-weight:800;color:#111827;margin-bottom:6px}
        .verify-card .subtitle{font-size:14px;color:#6b7280;margin-bottom:28px;line-height:1.6}
        .verify-card .subtitle strong{color:#2563eb}
        .otp-inputs{display:flex;gap:10px;justify-content:center;margin-bottom:20px}
        .otp-inputs input{width:50px;height:56px;text-align:center;font-size:22px;font-weight:700;border:2px solid #e5e7eb;border-radius:10px;outline:none;transition:all .2s;font-family:inherit;color:#111827}
        .otp-inputs input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
        .otp-inputs input.filled{border-color:#10b981;background:#f0fdf4}
        .verify-btn{width:100%;padding:14px;background:#2563eb;color:white;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px}
        .verify-btn:hover:not(:disabled){background:#1d4ed8;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.3)}
        .verify-btn:disabled{opacity:.5;cursor:not-allowed}
        .verify-btn .spinner{display:none;width:18px;height:18px;border:2px solid rgba(255,255,255,.3);border-top-color:white;border-radius:50%;animation:spin .6s linear infinite}
        .verify-btn.loading .spinner{display:block}.verify-btn.loading .btn-text{display:none}
        @keyframes spin{to{transform:rotate(360deg)}}
        .resend-row{margin-top:18px;font-size:13px;color:#6b7280}
        .resend-row a{color:#2563eb;font-weight:600;text-decoration:none}.resend-row a:hover{text-decoration:underline}
        .alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:18px;font-weight:500;text-align:left}
        .alert-danger{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
        .alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
        .demo-note{background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;font-size:12px;color:#92400e;margin-top:16px;line-height:1.5}
        .demo-note strong{color:#b45309}
        .back-link{margin-top:18px;font-size:13px}.back-link a{color:#6b7280;text-decoration:none;font-weight:500}.back-link a:hover{color:#2563eb}
        .footer{background:#111827;color:white;padding:20px 6%;text-align:center;margin-top:auto}.footer p{font-size:13px;color:#6b7280}
        @media(max-width:768px){.navbar{height:60px;padding:0 20px}.logo{font-size:23px}.nav-links{display:none;position:absolute;top:60px;left:0;right:0;background:white;flex-direction:column;padding:16px 20px;box-shadow:0 8px 24px rgba(0,0,0,.1);gap:0}.nav-links.open{display:flex}.nav-links a{padding:14px 0;border-bottom:1px solid #f3f4f6}.menu-toggle{display:block}.verify-card{padding:28px 20px}.otp-inputs input{width:44px;height:50px;font-size:20px}}
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
        <li><a href="login.php" class="login-btn">Login</a></li>
    </ul>
</nav>

<div class="auth-wrapper">
    <div class="verify-card">
        <div class="verify-icon">&#128274;</div>
        <h2>Verify Your Email</h2>
        <p class="subtitle">We've sent a 6-digit verification code to<br><strong><?php echo htmlspecialchars($maskedEmail); ?></strong></p>

        <?php if ($error): ?><div class="alert alert-danger">&#9888; <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success">&#10003; <?php echo $success; ?></div><?php endif; ?>
        <?php if ($resendMsg): ?><div class="alert alert-danger"><?php echo htmlspecialchars($resendMsg); ?></div><?php endif; ?>

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

        <div class="resend-row">Didn't receive the code? <a href="verify-otp.php?resend=1" id="resendLink">Resend OTP</a></div>
        <div class="back-link"><a href="register.php">&#8592; Back to Registration</a></div>

        <div class="demo-note"><strong>Demo Mode:</strong> Since this is a demo, the OTP is shown on screen. In production, it would be sent to your email/SMS.</div>
    </div>
</div>

<footer class="footer"><p>&copy; 2026 EngiHub. All rights reserved.</p></footer>

<script>
document.getElementById('menuToggle').addEventListener('click',function(){document.getElementById('navLinks').classList.toggle('open')});

var inputs=document.querySelectorAll('.otp-inputs input'),hidden=document.getElementById('otpHidden'),verifyBtn=document.getElementById('verifyBtn');
inputs.forEach(function(inp,i){
    inp.addEventListener('input',function(){
        this.value=this.value.replace(/[^0-9]/g,'');
        if(this.value.length===1){
            this.classList.add('filled');
            if(i<5)inputs[i+1].focus();
        }else{this.classList.remove('filled')}
        updateCode();
    });
    inp.addEventListener('keydown',function(e){
        if(e.key==='Backspace'&&!this.value&&i>0){inputs[i-1].focus();inputs[i-1].value='';inputs[i-1].classList.remove('filled');updateCode()}
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
    hidden.value=code;
    verifyBtn.disabled=code.length!==6;
}

document.getElementById('otpForm').addEventListener('submit',function(e){
    if(verifyBtn.disabled){e.preventDefault();return}
    verifyBtn.classList.add('loading');verifyBtn.disabled=true;
});
</script>
</body></html>

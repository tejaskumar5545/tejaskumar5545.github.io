<?php
require_once '../db.php';
require_once __DIR__ . '/init-admin-tables.php';

if (isAdmin()) { header("Location: dashboard.php"); exit; }

$step     = 'email';
$error    = '';
$success  = '';
$emailMasked = '';

if (isset($_SESSION['admin_reset_verified']) && $_SESSION['admin_reset_verified'] === true && isset($_SESSION['admin_reset_admin_id'])) {
    $step = 'password';
} elseif (isset($_SESSION['admin_reset_token_sent'])) {
    $step = 'otp';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = 'Security validation failed.';
    } else {

        if (isset($_POST['action']) && $_POST['action'] === 'request_reset') {
            $email = trim($_POST['email'] ?? '');
            if (empty($email)) {
                $error = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                if (!rateLimit('admin_forgot', 3, 900)) {
                    $error = 'Too many requests. Please wait 15 minutes before trying again.';
                } else {
                    $stmt = $conn->prepare("SELECT id, email, username, account_status FROM admin_users WHERE email = ? LIMIT 1");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();

                    $token     = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);

                    if ($result->num_rows === 1) {
                        $admin = $result->fetch_assoc();
                        if ($admin['account_status'] === 'active') {
                            $ins = $conn->prepare("INSERT INTO admin_reset_tokens (admin_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
                            $ins->bind_param("is", $admin['id'], $tokenHash);
                            $ins->execute();
                            $ins->close();

                            $_SESSION['admin_reset_token']  = $token;
                            $_SESSION['admin_reset_admin_id'] = $admin['id'];
                            $_SESSION['admin_reset_token_sent'] = true;
                            $_SESSION['admin_reset_step'] = 'otp';
                        }
                    }

                    $parts  = explode('@', $email);
                    $name   = $parts[0];
                    $domain = $parts[1] ?? '***';
                    $emailMasked = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 2)) . '@' . $domain;

                    $success = "If an account with <strong>" . htmlspecialchars($emailMasked) . "</strong> exists, a verification code has been sent.";
                    $step = 'otp';
                }
            }
        }

        elseif (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
            $otp = trim($_POST['otp'] ?? '');
            if (empty($otp)) {
                $error = 'Verification code is required.';
            } elseif (strlen($otp) !== 6) {
                $error = 'Please enter the complete 6-digit code.';
            } elseif (!isset($_SESSION['admin_reset_token'])) {
                $error = 'Session expired. Please request a new code.';
                $step = 'email';
            } else {
                if (!rateLimit('admin_otp_verify', 5, 900)) {
                    $error = 'Too many attempts. Please request a new code.';
                    $step = 'email';
                } else {
                    $tokenHash = hash('sha256', $_SESSION['admin_reset_token']);
                    $stmt = $conn->prepare("SELECT id, admin_id, expires_at, used FROM admin_reset_tokens WHERE token_hash = ? AND used = 0 ORDER BY id DESC LIMIT 1");
                    $stmt->bind_param("s", $tokenHash);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();

                    if ($result->num_rows === 1) {
                        $record = $result->fetch_assoc();
                        if (strtotime($record['expires_at']) < time()) {
                            $error = 'Verification code has expired. Please request a new one.';
                            $step = 'email';
                            unset($_SESSION['admin_reset_token'], $_SESSION['admin_reset_token_sent'], $_SESSION['admin_reset_verified'], $_SESSION['admin_reset_admin_id']);
                        } else if ($otp !== substr($_SESSION['admin_reset_token'], 0, 6)) {
                            $error = 'Invalid verification code. Please try again.';
                        } else {
                            $_SESSION['admin_reset_verified'] = true;
                            $_SESSION['admin_reset_step'] = 'password';
                            unset($_SESSION['admin_reset_token_sent']);
                            $step = 'password';
                        }
                    } else {
                        $error = 'Invalid verification code.';
                        $step = 'email';
                    }
                }
            }
        }

        elseif (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
            $newPass     = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (empty($newPass) || empty($confirmPass)) {
                $error = 'Both password fields are required.';
            } else if ($newPass !== $confirmPass) {
                $error = 'Passwords do not match.';
            } else if (strlen($newPass) < 8) {
                $error = 'Password must be at least 8 characters.';
            } else if (!preg_match('/[A-Z]/', $newPass) || !preg_match('/[a-z]/', $newPass) || !preg_match('/[0-9]/', $newPass) || !preg_match('/[^A-Za-z0-9]/', $newPass)) {
                $error = 'Password must include uppercase, lowercase, number, and special character.';
            } else if (!isset($_SESSION['admin_reset_verified']) || !$_SESSION['admin_reset_verified'] || !isset($_SESSION['admin_reset_admin_id'])) {
                $error = 'Session expired. Please start over.';
                $step = 'email';
            } else {
                $adminId = $_SESSION['admin_reset_admin_id'];
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("UPDATE admin_users SET password_hash = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?");
                $stmt->bind_param("si", $newHash, $adminId);
                $stmt->execute();
                $stmt->close();

                $conn->prepare("UPDATE admin_reset_tokens SET used = 1 WHERE admin_id = ? AND used = 0")->execute([$adminId]);

                unset($_SESSION['admin_reset_token'], $_SESSION['admin_reset_token_sent'], $_SESSION['admin_reset_verified'], $_SESSION['admin_reset_admin_id'], $_SESSION['admin_reset_step']);

                $success = 'Password reset successfully! You can now log in with your new password.';
                $step = 'done';
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
<title>Reset Password - EngiHub Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#0b1120;color:#111827;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;overflow:hidden}
body::before{content:'';position:fixed;top:-40%;left:-20%;width:600px;height:600px;background:radial-gradient(circle,rgba(37,99,235,.08) 0%,transparent 70%);pointer-events:none}
body::after{content:'';position:fixed;bottom:-30%;right:-15%;width:500px;height:500px;background:radial-gradient(circle,rgba(96,165,250,.06) 0%,transparent 70%);pointer-events:none}
.bg-grid{position:fixed;top:0;left:0;right:0;bottom:0;background-image:linear-gradient(rgba(255,255,255,.02) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.02) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;z-index:0}
.card-wrap{position:relative;z-index:1;width:100%;max-width:440px;animation:fadeUp .6s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.card{background:#fff;border-radius:20px;padding:40px 36px 34px;box-shadow:0 25px 60px rgba(0,0,0,.4);position:relative;overflow:hidden}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#1e40af,#2563eb,#60a5fa)}
.logo-section{text-align:center;margin-bottom:24px}
.logo-text{font-size:28px;font-weight:800}.logo-text .engi{color:#111827}.logo-text .hub{color:#2563eb}
.badge{display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:4px 12px;background:#0f172a;color:#60a5fa;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:.6px;text-transform:uppercase}
.page-title{text-align:center;font-size:20px;font-weight:800;margin-bottom:4px}
.page-sub{text-align:center;font-size:13px;color:#6b7280;margin-bottom:24px;line-height:1.5}
.alert{padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:16px;display:flex;align-items:flex-start;gap:8px;line-height:1.4}
.alert-error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.form-group{margin-bottom:18px}
.form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
.input-wrap{position:relative}
.input-wrap .fi{position:absolute;left:13px;top:50%;transform:translateY(-50%);font-size:15px;color:#9ca3af;pointer-events:none;transition:color .2s}
.input-wrap input{width:100%;padding:12px 44px 12px 40px;border:2px solid #e5e7eb;border-radius:11px;font-size:14px;color:#111827;background:#f9fafb;outline:none;transition:all .2s;font-family:inherit}
.input-wrap input:focus{border-color:#2563eb;box-shadow:0 0 0 4px rgba(37,99,235,.08);background:#fff}
.input-wrap input:focus~.fi{color:#2563eb}
.toggle-pw{position:absolute;right:13px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:17px;color:#9ca3af;cursor:pointer;padding:4px;transition:color .2s;user-select:none}
.toggle-pw:hover{color:#374151}
.btn{width:100%;padding:13px;border:none;border-radius:11px;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;font-family:inherit}
.btn-primary{background:linear-gradient(135deg,#1e40af,#2563eb);color:#fff}
.btn-primary:hover{background:linear-gradient(135deg,#1d4ed8,#1e40af);transform:translateY(-1px);box-shadow:0 5px 16px rgba(37,99,235,.3)}
.btn-primary:disabled{opacity:.65;cursor:not-allowed;transform:none;box-shadow:none}
.btn-secondary{background:#f3f4f6;color:#374151;border:1px solid #e5e7eb}
.btn-secondary:hover{background:#e5e7eb}
.otp-inputs{display:flex;gap:10px;justify-content:center;margin-bottom:20px}
.otp-inputs input{width:50px;height:54px;text-align:center;font-size:22px;font-weight:700;border:2px solid #e5e7eb;border-radius:11px;background:#f9fafb;outline:none;transition:all .2s;font-family:inherit;color:#111827}
.otp-inputs input:focus{border-color:#2563eb;box-shadow:0 0 0 4px rgba(37,99,235,.08);background:#fff}
.otp-inputs input.filled{border-color:#2563eb;background:#eff6ff}
.resend-row{text-align:center;margin-top:14px}
.resend-row span{font-size:13px;color:#6b7280}
.resend-row a{color:#2563eb;font-weight:600;text-decoration:none;cursor:pointer}
.resend-row a:hover{text-decoration:underline}
.resend-row a.disabled{color:#9ca3af;cursor:not-allowed;pointer-events:none}
.success-icon{text-align:center;margin-bottom:16px;font-size:48px}
.success-icon .check{display:inline-flex;width:72px;height:72px;border-radius:50%;background:#f0fdf4;align-items:center;justify-content:center;font-size:36px;border:3px solid #bbf7d0}
.divider{display:flex;align-items:center;gap:12px;margin:20px 0}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e5e7eb}
.divider span{font-size:12px;color:#9ca3af}
.back-link{text-align:center}
.back-link a{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;background:#f3f4f6;color:#374151;border-radius:11px;text-decoration:none;font-size:13px;font-weight:600;transition:all .2s;width:100%;justify-content:center;border:1px solid #e5e7eb}
.back-link a:hover{background:#e5e7eb}
.strength-meter{height:4px;background:#e5e7eb;border-radius:4px;margin-top:8px;overflow:hidden}
.strength-meter .bar{height:100%;width:0;border-radius:4px;transition:all .3s}
.strength-reqs{margin-top:8px;display:grid;grid-template-columns:1fr 1fr;gap:4px}
.strength-reqs span{font-size:11px;color:#9ca3af;display:flex;align-items:center;gap:4px}
.strength-reqs span.met{color:#16a34a}
.step-indicator{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:24px}
.step-dot{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;background:#e5e7eb;color:#9ca3af;transition:all .3s}
.step-dot.active{background:#2563eb;color:#fff;box-shadow:0 2px 8px rgba(37,99,235,.3)}
.step-dot.done{background:#16a34a;color:#fff}
.step-line{width:40px;height:2px;background:#e5e7eb;border-radius:2px;transition:background .3s}
.step-line.done{background:#16a34a}
@media(max-width:480px){.card{padding:30px 22px 26px;border-radius:16px}.otp-inputs input{width:44px;height:48px;font-size:20px}.otp-inputs{gap:7px}}
</style>
</head>
<body>
<div class="bg-grid"></div>
<div class="card-wrap">
<div class="card">
    <div class="logo-section">
        <div class="logo-text"><span class="engi">Engi</span><span class="hub">Hub</span></div>
        <div class="badge">&#128737; ADMIN RESET</div>
    </div>

    <div class="step-indicator">
        <div class="step-dot <?php echo $step === 'email' ? 'active' : (($step === 'otp' || $step === 'password' || $step === 'done') ? 'done' : ''); ?>">
            <?php echo ($step === 'otp' || $step === 'password' || $step === 'done') ? '&#10003;' : '1'; ?>
        </div>
        <div class="step-line <?php echo ($step === 'otp' || $step === 'password' || $step === 'done') ? 'done' : ''; ?>"></div>
        <div class="step-dot <?php echo $step === 'otp' ? 'active' : (($step === 'password' || $step === 'done') ? 'done' : ''); ?>">
            <?php echo ($step === 'password' || $step === 'done') ? '&#10003;' : '2'; ?>
        </div>
        <div class="step-line <?php echo ($step === 'password' || $step === 'done') ? 'done' : ''; ?>"></div>
        <div class="step-dot <?php echo $step === 'password' ? 'active' : ($step === 'done' ? 'done' : ''); ?>">
            <?php echo $step === 'done' ? '&#10003;' : '3'; ?>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><span>&#9888;</span> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success && $step !== 'done'): ?>
        <div class="alert alert-success"><span>&#10003;</span> <?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($step === 'email'): ?>
    <h2 class="page-title">Forgot Password?</h2>
    <p class="page-sub">Enter your admin email address and we'll send you a verification code.</p>
    <form method="POST">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="request_reset">
        <div class="form-group">
            <label>Email Address</label>
            <div class="input-wrap">
                <span class="fi">&#128231;</span>
                <input type="email" name="email" placeholder="admin@engihub.com" required autofocus value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Send Verification Code &#10148;</button>
    </form>

    <?php elseif ($step === 'otp'): ?>
    <h2 class="page-title">Enter Verification Code</h2>
    <p class="page-sub">We sent a 6-digit code to <strong><?php echo htmlspecialchars($emailMasked); ?></strong>. Enter it below.</p>
    <form method="POST" id="otpForm">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="verify_otp">
        <div class="otp-inputs">
            <input type="text" maxlength="1" class="otp" data-index="0" inputmode="numeric" pattern="[0-9]" autocomplete="off">
            <input type="text" maxlength="1" class="otp" data-index="1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
            <input type="text" maxlength="1" class="otp" data-index="2" inputmode="numeric" pattern="[0-9]" autocomplete="off">
            <input type="text" maxlength="1" class="otp" data-index="3" inputmode="numeric" pattern="[0-9]" autocomplete="off">
            <input type="text" maxlength="1" class="otp" data-index="4" inputmode="numeric" pattern="[0-9]" autocomplete="off">
            <input type="text" maxlength="1" class="otp" data-index="5" inputmode="numeric" pattern="[0-9]" autocomplete="off">
        </div>
        <input type="hidden" name="otp" id="otpHidden">
        <button type="submit" class="btn btn-primary">Verify Code &#10148;</button>
    </form>
    <div class="resend-row" id="resendRow">
        <span>Didn't receive the code? </span><a href="#" id="resendLink" onclick="resendCode(event)">Resend Code</a>
    </div>
    <div class="resend-row" style="margin-top:10px">
        <a href="login.php" style="color:#6b7280;font-weight:500">&#8592; Back to Login</a>
    </div>

    <?php elseif ($step === 'password'): ?>
    <h2 class="page-title">Set New Password</h2>
    <p class="page-sub">Your identity has been verified. Create a new strong password.</p>
    <form method="POST" id="pwForm">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="reset_password">
        <div class="form-group">
            <label>New Password</label>
            <div class="input-wrap">
                <span class="fi">&#128274;</span>
                <input type="password" name="new_password" id="newPw" placeholder="Enter new password" required>
                <button type="button" class="toggle-pw" onclick="togglePw('newPw',this)" tabindex="-1">&#128065;</button>
            </div>
            <div class="strength-meter"><div class="bar" id="pwBar"></div></div>
            <div class="strength-reqs">
                <span id="rLen">&#9675; 8+ characters</span>
                <span id="rUp">&#9675; Uppercase</span>
                <span id="rLow">&#9675; Lowercase</span>
                <span id="rNum">&#9675; Number</span>
                <span id="rSpec">&#9675; Special char</span>
            </div>
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <div class="input-wrap">
                <span class="fi">&#128274;</span>
                <input type="password" name="confirm_password" id="confPw" placeholder="Confirm new password" required>
                <button type="button" class="toggle-pw" onclick="togglePw('confPw',this)" tabindex="-1">&#128065;</button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" id="resetBtn">Reset Password &#10148;</button>
    </form>

    <?php elseif ($step === 'done'): ?>
    <div class="success-icon"><span class="check">&#10003;</span></div>
    <h2 class="page-title">Password Reset Complete</h2>
    <p class="page-sub" style="margin-bottom:24px"><?php echo $success; ?></p>
    <a href="login.php" class="btn btn-primary" style="text-decoration:none;text-align:center">Proceed to Admin Login &#10148;</a>

    <?php endif; ?>
</div>
</div>

<script>
function togglePw(id,btn){const i=document.getElementById(id);if(i.type==='password'){i.type='text';btn.innerHTML='&#128064;'}else{i.type='password';btn.innerHTML='&#128065;'}}

<?php if ($step === 'otp'): ?>
(function(){
    const inputs=document.querySelectorAll('.otp');
    const hidden=document.getElementById('otpHidden');
    const form=document.getElementById('otpForm');
    inputs.forEach((inp,i)=>{
        inp.addEventListener('input',function(){
            this.value=this.value.replace(/[^0-9]/g,'');
            if(this.value&&i<inputs.length-1)inputs[i+1].focus();
            updateHidden();
            if([...inputs].every(x=>x.value.length===1))form.submit();
        });
        inp.addEventListener('keydown',function(e){
            if(e.key==='Backspace'&&!this.value&&i>0){inputs[i-1].focus();inputs[i-1].value='';updateHidden();}
        });
        inp.addEventListener('paste',function(e){
            e.preventDefault();
            const d=(e.clipboardData||window.clipboardData).getData('text').replace(/[^0-9]/g,'').slice(0,6);
            d.split('').forEach((c,j)=>{if(inputs[j])inputs[j].value=c});
            if(d.length>0)inputs[Math.min(d.length,5)].focus();
            updateHidden();
            if(d.length>=6)setTimeout(()=>form.submit(),200);
        });
    });
    function updateHidden(){hidden.value=[...inputs].map(x=>x.value).join('')}
    inputs[0].focus();
})();
let timer=30;
const link=document.getElementById('resendLink');
const row=document.getElementById('resendRow');
function startTimer(){
    link.classList.add('disabled');
    const iv=setInterval(()=>{
        timer--;
        if(timer<=0){clearInterval(iv);link.classList.remove('disabled');link.textContent='Resend Code';timer=30}
        else{link.textContent='Resend ('+timer+'s)'}
    },1000);
}
startTimer();
function resendCode(e){
    e.preventDefault();
    if(link.classList.contains('disabled'))return;
    fetch('forgot-password.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=request_reset&csrf_token=<?php echo $_SESSION['csrf_token']; ?>&email=<?php echo urlencode($_SESSION['admin_reset_email'] ?? ''); ?>'})
    .then(()=>{timer=30;startTimer()});
}
<?php endif; ?>

<?php if ($step === 'password'): ?>
document.getElementById('newPw').addEventListener('input',function(){
    const v=this.value;
    const checks={len:v.length>=8,up:/[A-Z]/.test(v),low:/[a-z]/.test(v),num:/[0-9]/.test(v),spec:/[^A-Za-z0-9]/.test(v)};
    const score=Object.values(checks).filter(Boolean).length;
    const bar=document.getElementById('pwBar');
    bar.style.width=(score/5*100)+'%';
    bar.style.background=score<=2?'#dc2626':score<=3?'#f59e0b':score<=4?'#2563eb':'#16a34a';
    document.getElementById('rLen').className=checks.len?'met':'';
    document.getElementById('rUp').className=checks.up?'met':'';
    document.getElementById('rLow').className=checks.low?'met':'';
    document.getElementById('rNum').className=checks.num?'met':'';
    document.getElementById('rSpec').className=checks.spec?'met':'';
    document.getElementById('rLen').innerHTML=(checks.len?'&#10003;':'&#9675;')+' 8+ characters';
    document.getElementById('rUp').innerHTML=(checks.up?'&#10003;':'&#9675;')+' Uppercase';
    document.getElementById('rLow').innerHTML=(checks.low?'&#10003;':'&#9675;')+' Lowercase';
    document.getElementById('rNum').innerHTML=(checks.num?'&#10003;':'&#9675;')+' Number';
    document.getElementById('rSpec').innerHTML=(checks.spec?'&#10003;':'&#9675;')+' Special char';
});
document.getElementById('pwForm').addEventListener('submit',function(e){
    const p=document.getElementById('newPw').value;
    const c=document.getElementById('confPw').value;
    if(p!==c){e.preventDefault();alert('Passwords do not match.');return}
    if(p.length<8){e.preventDefault();alert('Password must be at least 8 characters.');return}
    if(!/[A-Z]/.test(p)||!/[a-z]/.test(p)||!/[0-9]/.test(p)||!/[^A-Za-z0-9]/.test(p)){e.preventDefault();alert('Password must include all character types.');return}
    document.getElementById('resetBtn').disabled=true;
    document.getElementById('resetBtn').textContent='Resetting...';
});
<?php endif; ?>
</script>
</body>
</html>

<?php
require_once 'db.php';

if (empty($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit;
}

$csrfToken = generateCSRFToken();
$email = $_SESSION['reset_email'];
$maskedEmail = substr($email, 0, 3) . str_repeat('*', max(0, strlen($email) - 6)) . substr($email, -3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2563eb">
    <title>Verify Reset OTP - EngiHub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fb; color: #111827; min-height: 100vh; display: flex; flex-direction: column; }
        a { text-decoration: none; color: inherit; }
        .navbar { width: 100%; height: 70px; background: white; display: flex; align-items: center; justify-content: space-between; padding: 0 6%; box-shadow: 0 2px 10px rgba(0,0,0,0.08); position: sticky; top: 0; z-index: 100; }
        .logo { font-size: 28px; font-weight: bold; color: #2563eb; } .logo span { color: #111827; }
        .nav-links { display: flex; gap: 25px; list-style: none; align-items: center; }
        .nav-links a { text-decoration: none; color: #333; font-weight: 500; transition: color 0.2s; } .nav-links a:hover { color: #2563eb; }
        .login-btn { background: #2563eb; color: white !important; padding: 10px 20px; border-radius: 8px; } .login-btn:hover { background: #1d4ed8; }
        .menu-toggle { display: none; background: none; border: none; font-size: 28px; cursor: pointer; color: #111827; }
        .auth-section { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 6%; }
        .auth-card { max-width: 460px; width: 100%; background: white; border-radius: 16px; padding: 40px 36px; box-shadow: 0 4px 24px rgba(0,0,0,0.07); animation: fadeUp 0.5s ease-out; text-align: center; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .otp-icon { font-size: 56px; margin-bottom: 16px; }
        .auth-title { font-size: 22px; font-weight: 800; margin-bottom: 6px; }
        .auth-subtitle { font-size: 14px; color: #6b7280; margin-bottom: 28px; line-height: 1.5; }
        .otp-inputs { display: flex; gap: 10px; justify-content: center; margin-bottom: 24px; }
        .otp-inputs input { width: 50px; height: 58px; text-align: center; font-size: 24px; font-weight: 700; border: 2px solid #e5e7eb; border-radius: 12px; outline: none; transition: border-color 0.2s, box-shadow 0.2s; font-family: 'Courier New', monospace; color: #111827; }
        .otp-inputs input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .otp-inputs input.filled { border-color: #10b981; background: #f0fdf4; }
        .otp-inputs input.invalid { border-color: #ef4444; background: #fef2f2; }
        .resend-row { font-size: 13px; color: #6b7280; margin-bottom: 24px; }
        .resend-row a { color: #2563eb; font-weight: 600; cursor: pointer; } .resend-row a:hover { text-decoration: underline; }
        .resend-row .timer { color: #9ca3af; margin-top: 6px; }
        .attempts-warning { font-size: 12px; color: #f59e0b; margin-bottom: 16px; display: none; }
        .attempts-warning.show { display: block; }
        .submit-btn { width: 100%; padding: 14px; background: #2563eb; color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; font-family: inherit; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .submit-btn:hover:not(:disabled) { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
        .submit-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .submit-btn .spinner { display: none; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 0.6s linear infinite; }
        .submit-btn.loading .spinner { display: block; } .submit-btn.loading .btn-text { display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .auth-footer { text-align: center; font-size: 14px; color: #6b7280; margin-top: 20px; padding-top: 18px; border-top: 1px solid #f3f4f6; }
        .auth-footer a { color: #2563eb; font-weight: 700; } .auth-footer a:hover { text-decoration: underline; }
        .alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; font-weight: 500; display: none; align-items: center; gap: 8px; text-align: left; }
        .alert.show { display: flex; } .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; } .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .footer { background: #111827; color: white; padding: 50px 7% 0; }
        .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px; padding-bottom: 40px; }
        .footer-brand h3 { font-size: 24px; margin-bottom: 12px; } .footer-brand h3 span { color: #2563eb; }
        .footer-brand p { font-size: 14px; color: #9ca3af; line-height: 1.7; }
        .footer-col h4 { font-size: 15px; font-weight: 700; margin-bottom: 16px; }
        .footer-col ul { list-style: none; } .footer-col ul li { margin-bottom: 10px; }
        .footer-col ul li a { font-size: 14px; color: #9ca3af; transition: color 0.2s; } .footer-col ul li a:hover { color: #2563eb; }
        .footer-bottom { border-top: 1px solid #1f2937; padding: 18px 0; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
        .footer-bottom p { font-size: 13px; color: #6b7280; }
        @media (max-width: 768px) {
            .navbar { height: 60px; padding: 0 20px; } .logo { font-size: 23px; }
            .nav-links { display: none; position: absolute; top: 60px; left: 0; right: 0; background: white; flex-direction: column; padding: 16px 20px; box-shadow: 0 8px 24px rgba(0,0,0,0.1); gap: 0; max-height: 0; overflow: hidden; opacity: 0; visibility: hidden; transition: max-height 0.3s ease, opacity 0.25s ease, visibility 0.25s ease; }
            .nav-links.open { display: flex; max-height: 600px; opacity: 1; visibility: visible; }
            .nav-links a { padding: 14px 0; border-bottom: 1px solid #f3f4f6; } .menu-toggle { display: block; }
            .auth-section { padding: 20px 16px; } .auth-card { padding: 28px 20px; }
            .otp-inputs input { width: 44px; height: 52px; font-size: 20px; }
            .footer-grid { grid-template-columns: 1fr; gap: 28px; } .footer-bottom { flex-direction: column; text-align: center; }
            .nav-links li { width: 100%; } .nav-links li a { display: block; width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; } }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.html" class="logo" aria-label="EngiHub Home">Engi<span>Hub</span></a>
    <button class="menu-toggle" id="menuToggle" type="button" aria-label="Open navigation menu" aria-expanded="false">&#9776;</button>
    <ul class="nav-links" id="navLinks">
        <li><a href="index.html">Home</a></li>
        <li><a href="notes.html">Notes</a></li>
        <li><a href="syllabus.html">Syllabus</a></li>
        <li><a href="pyq.html">PYQ</a></li>
        <li><a href="practical.html">Practical</a></li>
        <li><a href="coding.html">Coding</a></li>
        <li><a href="projects.html">Projects</a></li>
        <li><a href="placement.html">Placement</a></li>
        <li><a href="register.php">Register</a></li>
        <li><a href="login.php" class="login-btn">Login</a></li>
    </ul>
</nav>

<section class="auth-section">
    <div class="auth-card">
        <div class="otp-icon">&#128274;</div>
        <h1 class="auth-title">Verify Reset OTP</h1>
        <p class="auth-subtitle">Enter the 6-digit code sent to <strong><?= htmlspecialchars($maskedEmail) ?></strong></p>

        <div class="alert alert-danger" id="alertBox"><span>&#9888;</span><span id="alertMsg"></span></div>
        <div class="alert alert-success" id="successBox"><span>&#10003;</span><span id="successMsg"></span></div>
        <div class="attempts-warning" id="attemptsWarning">&#9888; You have <strong id="attemptsLeft">5</strong> attempts remaining.</div>

        <form id="otpForm" novalidate>
            <?= csrfField() ?>
            <div class="otp-inputs" id="otpInputs">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 1" autocomplete="one-time-code">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 2">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 3">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 4">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 5">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" aria-label="Digit 6">
            </div>
            <div class="resend-row">
                <div>Didn't receive the code? <a id="resendLink" onclick="resendOTP()">Resend OTP</a></div>
                <div class="timer" id="timer">Resend available in <strong id="countdown">30</strong>s</div>
            </div>
            <button type="submit" class="submit-btn" id="submitBtn" disabled>
                <span class="btn-text">Verify OTP &#10148;</span>
                <span class="spinner"></span>
            </button>
        </form>
        <div class="auth-footer"><a href="forgot-password.php">&#8592; Back to Forgot Password</a></div>
    </div>
</section>

<footer class="footer">
    <div class="footer-grid">
        <div class="footer-brand"><h3>Engi<span>Hub</span></h3><p>Everything an Engineering Student Needs.</p></div>
        <div class="footer-col"><h4>Quick Links</h4><ul><li><a href="index.html">Home</a></li><li><a href="notes.html">Notes</a></li><li><a href="syllabus.html">Syllabus</a></li><li><a href="pyq.html">PYQ</a></li><li><a href="practical.html">Practicals</a></li></ul></div>
        <div class="footer-col"><h4>Resources</h4><ul><li><a href="coding.html">Coding</a></li><li><a href="projects.html">Projects</a></li><li><a href="placement.html">Placement</a></li><li><a href="notices.html">Notices</a></li></ul></div>
        <div class="footer-col"><h4>Account</h4><ul><li><a href="admission.html">Admissions</a></li><li><a href="login.php">Login</a></li><li><a href="register.php">Register</a></li></ul></div>
    </div>
    <div class="footer-bottom"><p>&copy; 2026 EngiHub. All rights reserved.</p><p>Built for engineering students, by engineering students.</p></div>
</footer>

<script>
var mt = document.getElementById("menuToggle"), nl = document.getElementById("navLinks");
mt.addEventListener("click", function(){ var o=nl.classList.toggle("open"); mt.setAttribute("aria-expanded",o?"true":"false"); mt.innerHTML=o?"&#10005;":"&#9776;"; });
document.querySelectorAll(".nav-links a").forEach(function(l){ l.addEventListener("click",function(){ nl.classList.remove("open"); mt.setAttribute("aria-expanded","false"); mt.innerHTML="&#9776;"; }); });

var otpInputs = document.querySelectorAll('#otpInputs input');
var countdown = 30, timerInterval;

otpInputs.forEach(function(input, idx) {
    input.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value && idx < 5) otpInputs[idx + 1].focus();
        this.classList.toggle('filled', this.value !== '');
        this.classList.remove('invalid');
        checkComplete();
    });
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value && idx > 0) { otpInputs[idx-1].value=''; otpInputs[idx-1].classList.remove('filled','invalid'); otpInputs[idx-1].focus(); }
    });
    input.addEventListener('paste', function(e) {
        e.preventDefault();
        var d = (e.clipboardData||window.clipboardData).getData('text').replace(/[^0-9]/g,'').slice(0,6);
        for (var i=0;i<d.length&&i<6;i++){otpInputs[i].value=d[i];otpInputs[i].classList.add('filled');otpInputs[i].classList.remove('invalid');}
        if(d.length>0)otpInputs[Math.min(d.length,5)].focus();
        checkComplete();
    });
});

function checkComplete(){var c='';otpInputs.forEach(function(i){c+=i.value;});document.getElementById('submitBtn').disabled=c.length!==6;}
function startTimer(){countdown=30;document.getElementById('countdown').textContent=countdown;document.getElementById('resendLink').style.pointerEvents='none';document.getElementById('resendLink').style.opacity='0.5';document.getElementById('timer').style.display='block';timerInterval=setInterval(function(){countdown--;document.getElementById('countdown').textContent=countdown;if(countdown<=0){clearInterval(timerInterval);document.getElementById('resendLink').style.pointerEvents='auto';document.getElementById('resendLink').style.opacity='1';document.getElementById('timer').style.display='none';}},1000);}
startTimer();

function resendOTP(){
    document.getElementById('alertBox').className='alert';
    document.getElementById('successMsg').textContent='New OTP sent!';
    document.getElementById('successBox').className='alert alert-success show';
    otpInputs.forEach(function(i){i.value='';i.classList.remove('filled','invalid');});
    checkComplete();startTimer();
    setTimeout(function(){document.getElementById('successBox').className='alert';},3000);
}

document.getElementById('otpForm').addEventListener('submit',function(e){
    e.preventDefault();
    var code='';otpInputs.forEach(function(i){code+=i.value;});
    if(code.length!==6)return;
    var btn=document.getElementById('submitBtn');btn.classList.add('loading');
    var fd=new FormData(this);
    fetch('verify-reset-otp-process.php',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
        btn.classList.remove('loading');
        if(d.success){
            document.getElementById('alertBox').className='alert';
            document.getElementById('successMsg').textContent=d.message;
            document.getElementById('successBox').className='alert alert-success show';
            setTimeout(function(){window.location.href='reset-password.php';},1500);
        }else{
            document.getElementById('alertMsg').textContent=d.message;
            document.getElementById('alertBox').className='alert alert-danger show';
            if(d.attempts_left!==undefined){document.getElementById('attemptsLeft').textContent=d.attempts_left;document.getElementById('attemptsWarning').className='attempts-warning show';}
            otpInputs.forEach(function(i){i.value='';i.classList.remove('filled');i.classList.add('invalid');});
            otpInputs[0].focus();checkComplete();
        }
    })
    .catch(function(){btn.classList.remove('loading');document.getElementById('alertMsg').textContent='Network error.';document.getElementById('alertBox').className='alert alert-danger show';});
});
</script>
</body>
</html>

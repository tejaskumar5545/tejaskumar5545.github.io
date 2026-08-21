<?php
require_once 'db.php';

if (empty($_SESSION['reset_verified']) || empty($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit;
}

if (time() - ($_SESSION['reset_verified_at'] ?? 0) > 600) {
    unset($_SESSION['reset_verified'], $_SESSION['reset_verified_at'], $_SESSION['reset_email']);
    header("Location: forgot-password.php");
    exit;
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2563eb">
    <title>Reset Password - EngiHub</title>
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
        .reset-icon { font-size: 56px; margin-bottom: 16px; }
        .auth-title { font-size: 22px; font-weight: 800; margin-bottom: 6px; }
        .auth-subtitle { font-size: 14px; color: #6b7280; margin-bottom: 28px; line-height: 1.5; }
        .form-group { margin-bottom: 18px; text-align: left; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .input-wrapper { position: relative; }
        .input-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 16px; color: #9ca3af; pointer-events: none; }
        .input-wrapper input { width: 100%; padding: 12px 14px 12px 42px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; font-family: inherit; color: #111827; outline: none; transition: border-color 0.2s, box-shadow 0.2s; background: white; }
        .input-wrapper input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .input-wrapper input.valid { border-color: #10b981; }
        .input-wrapper input.invalid { border-color: #ef4444; }
        .toggle-pass { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; font-size: 18px; cursor: pointer; color: #9ca3af; padding: 4px; transition: color 0.2s; }
        .toggle-pass:hover { color: #374151; }
        .password-strength { margin-top: 6px; }
        .strength-bar { height: 4px; background: #e5e7eb; border-radius: 4px; overflow: hidden; margin-bottom: 4px; }
        .strength-fill { height: 100%; border-radius: 4px; transition: all 0.4s; width: 0; }
        .strength-fill.weak { width: 33%; background: #ef4444; }
        .strength-fill.fair { width: 66%; background: #f59e0b; }
        .strength-fill.strong { width: 100%; background: #10b981; }
        .strength-text { font-size: 11px; font-weight: 600; }
        .field-msg { font-size: 12px; margin-top: 4px; display: none; align-items: center; gap: 4px; }
        .field-msg.show { display: flex; }
        .field-msg.error { color: #ef4444; }
        .submit-btn { width: 100%; padding: 14px; background: #2563eb; color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; font-family: inherit; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 8px; }
        .submit-btn:hover:not(:disabled) { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
        .submit-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .submit-btn .spinner { display: none; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 0.6s linear infinite; }
        .submit-btn.loading .spinner { display: block; } .submit-btn.loading .btn-text { display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .success-box { display: none; text-align: center; padding: 20px 0; }
        .success-box.show { display: block; }
        .success-icon { font-size: 64px; margin-bottom: 12px; }
        .success-title { font-size: 20px; font-weight: 800; color: #10b981; margin-bottom: 8px; }
        .success-msg { font-size: 14px; color: #6b7280; margin-bottom: 20px; }
        .login-btn-success { display: inline-block; padding: 12px 32px; background: #2563eb; color: white; border-radius: 10px; font-size: 15px; font-weight: 700; transition: all 0.2s; }
        .login-btn-success:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
        .auth-footer { text-align: center; font-size: 14px; color: #6b7280; margin-top: 20px; padding-top: 18px; border-top: 1px solid #f3f4f6; }
        .auth-footer a { color: #2563eb; font-weight: 700; } .auth-footer a:hover { text-decoration: underline; }
        .alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; font-weight: 500; display: none; align-items: center; gap: 8px; text-align: left; }
        .alert.show { display: flex; } .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
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
        <div id="resetForm">
            <div class="reset-icon">&#128274;</div>
            <h1 class="auth-title">Reset Your Password</h1>
            <p class="auth-subtitle">Create a strong new password for your EngiHub account.</p>
            <div class="alert alert-danger" id="alertBox"><span>&#9888;</span><span id="alertMsg"></span></div>
            <form id="passwordForm" novalidate>
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="fPass">New Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">&#128274;</span>
                        <input type="password" id="fPass" name="new_password" placeholder="Min 8 characters" required autocomplete="new-password">
                        <button type="button" class="toggle-pass" onclick="toggleP('fPass',this)" aria-label="Show password">&#128065;</button>
                    </div>
                    <div class="password-strength" id="passStrength" style="display:none">
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <span class="strength-text" id="strengthText"></span>
                    </div>
                    <div class="field-msg error" id="fPassMsg"></div>
                </div>
                <div class="form-group">
                    <label for="fPass2">Confirm New Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">&#128274;</span>
                        <input type="password" id="fPass2" name="confirm_password" placeholder="Re-enter new password" required autocomplete="new-password">
                        <button type="button" class="toggle-pass" onclick="toggleP('fPass2',this)" aria-label="Show password">&#128065;</button>
                    </div>
                    <div class="field-msg error" id="fPass2Msg"></div>
                </div>
                <button type="submit" class="submit-btn" id="submitBtn" disabled>
                    <span class="btn-text">Reset Password &#10148;</span>
                    <span class="spinner"></span>
                </button>
            </form>
            <div class="auth-footer"><a href="login.php">&#8592; Back to Login</a></div>
        </div>
        <div class="success-box" id="successBox">
            <div class="success-icon">&#10004;&#65039;</div>
            <h2 class="success-title">Password Changed Successfully!</h2>
            <p class="success-msg">Your password has been updated. You can now login with your new password.</p>
            <a href="login.php" class="login-btn-success">Login Now</a>
        </div>
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
var mt=document.getElementById("menuToggle"),nl=document.getElementById("navLinks");
mt.addEventListener("click",function(){var o=nl.classList.toggle("open");mt.setAttribute("aria-expanded",o?"true":"false");mt.innerHTML=o?"&#10005;":"&#9776;";});
document.querySelectorAll(".nav-links a").forEach(function(l){l.addEventListener("click",function(){nl.classList.remove("open");mt.setAttribute("aria-expanded","false");mt.innerHTML="&#9776;";});});
function toggleP(id,btn){var i=document.getElementById(id);i.type=i.type==='password'?'text':'password';btn.innerHTML=i.type==='password'?'&#128065;':'&#128064;';}
function showM(id,m,t){var e=document.getElementById(id);e.textContent=m;e.className='field-msg show '+t;}
function hideM(id){document.getElementById(id).className='field-msg';}
var fP=document.getElementById('fPass'),fP2=document.getElementById('fPass2'),submitBtn=document.getElementById('submitBtn');
function chkBtn(){var p=fP.value.length>=8&&/[A-Z]/.test(fP.value)&&/[a-z]/.test(fP.value)&&/[0-9]/.test(fP.value)&&/[^A-Za-z0-9]/.test(fP.value);submitBtn.disabled=!(p&&fP2.value===fP.value&&fP2.value.length>0);}
fP.addEventListener('input',function(){var v=this.value;var s=document.getElementById('passStrength');var f=document.getElementById('strengthFill');var t=document.getElementById('strengthText');if(!v){s.style.display='none';hideM('fPassMsg');this.classList.remove('valid','invalid');chkBtn();return;}s.style.display='block';var sc=0;if(v.length>=8)sc++;if(v.length>=12)sc++;if(/[A-Z]/.test(v)&&/[a-z]/.test(v))sc++;if(/[0-9]/.test(v))sc++;if(/[^A-Za-z0-9]/.test(v))sc++;f.className='strength-fill';t.className='strength-text';if(sc<=2){f.classList.add('weak');t.textContent='Weak';t.style.color='#ef4444';}else if(sc<=3){f.classList.add('fair');t.textContent='Fair';t.style.color='#f59e0b';}else{f.classList.add('strong');t.textContent='Strong';t.style.color='#10b981';}var msgs=[];if(v.length<8)msgs.push('Min 8 chars');if(!/[A-Z]/.test(v))msgs.push('1 uppercase');if(!/[a-z]/.test(v))msgs.push('1 lowercase');if(!/[0-9]/.test(v))msgs.push('1 number');if(!/[^A-Za-z0-9]/.test(v))msgs.push('1 special char');if(msgs.length){showM('fPassMsg','Needs: '+msgs.join(', '),'error');this.classList.add('invalid');this.classList.remove('valid');}else{hideM('fPassMsg');this.classList.add('valid');this.classList.remove('invalid');}if(fP2.value.length>0)validateConfirm();chkBtn();});
function validateConfirm(){var v=fP2.value;if(!v){hideM('fPass2Msg');fP2.classList.remove('valid','invalid');}else if(v!==fP.value){showM('fPass2Msg','Passwords do not match','error');fP2.classList.add('invalid');fP2.classList.remove('valid');}else{hideM('fPass2Msg');fP2.classList.add('valid');fP2.classList.remove('invalid');}}
fP2.addEventListener('input',function(){validateConfirm();chkBtn();});
document.getElementById('passwordForm').addEventListener('submit',function(e){e.preventDefault();if(submitBtn.disabled)return;submitBtn.classList.add('loading');var fd=new FormData(this);fetch('reset-password-process.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){submitBtn.classList.remove('loading');if(d.success){document.getElementById('resetForm').style.display='none';document.getElementById('successBox').className='success-box show';}else{document.getElementById('alertMsg').textContent=d.message;document.getElementById('alertBox').className='alert alert-danger show';}}).catch(function(){submitBtn.classList.remove('loading');document.getElementById('alertMsg').textContent='Network error.';document.getElementById('alertBox').className='alert alert-danger show';});});
</script>
</body>
</html>

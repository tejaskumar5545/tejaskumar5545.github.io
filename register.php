<?php
require_once 'db.php';
if (isStudent()) { header("Location: dashboard.php"); exit; }
if (isAdmin()) { header("Location: admin/"); exit; }
$csrf = generateCSRFToken();
$regErrors = $_SESSION['reg_errors'] ?? [];
$regData = $_SESSION['reg_data'] ?? [];
unset($_SESSION['reg_errors'], $_SESSION['reg_data']);

$a = random_int(10, 40);
$b = random_int(1, 20);
$ops = ['+','-','×'];
$op = $ops[array_rand($ops)];
switch($op) {
    case '+': $captchaAns = $a + $b; break;
    case '-': $captchaAns = $a - $b; break;
    case '×': $captchaAns = $a * $b; break;
}
$_SESSION['captcha_answer'] = $captchaAns;
$captchaQuestion = "$a $op $b = ?";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#2563eb">
    <title>Register - EngiHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter','Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex;flex-direction:column}

        /* ── Navbar ── */
        .navbar{width:100%;height:70px;background:#fff;display:flex;align-items:center;justify-content:space-between;padding:0 6%;box-shadow:0 1px 3px rgba(0,0,0,.06);position:sticky;top:0;z-index:100}
        .logo{font-size:28px;font-weight:800;color:#2563eb;text-decoration:none;letter-spacing:-.5px}.logo span{color:#0f172a}
        .nav-links{display:flex;gap:28px;list-style:none;align-items:center}.nav-links a{text-decoration:none;color:#475569;font-weight:500;transition:color .2s;font-size:14px}.nav-links a:hover{color:#2563eb}
        .nav-links .btn-login{background:#2563eb;color:#fff!important;padding:10px 22px;border-radius:10px;font-weight:600;box-shadow:0 2px 8px rgba(37,99,235,.25)}.nav-links .btn-login:hover{background:#1d4ed8}
        .menu-toggle{display:none;background:none;border:none;font-size:26px;cursor:pointer;color:#0f172a}

        /* ── Auth Layout ── */
        .auth-wrapper{flex:1;display:flex;align-items:center;justify-content:center;padding:32px 6%}
        .auth-container{display:flex;max-width:1020px;width:100%;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.08);background:#fff;animation:fadeUp .6s cubic-bezier(.16,1,.3,1)}
        @keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
        @keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}
        @keyframes pop{0%{transform:scale(.95);opacity:0}100%{transform:scale(1);opacity:1}}
        @keyframes shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-4px)}40%,80%{transform:translateX(4px)}}

        /* ── Left Panel ── */
        .auth-side{flex:0 0 380px;background:linear-gradient(160deg,#0f172a 0%,#1e3a5f 45%,#2563eb 100%);color:#fff;padding:48px 36px;display:flex;flex-direction:column;justify-content:center;position:relative;overflow:hidden}
        .auth-side::before{content:'';position:absolute;top:-60px;right:-60px;width:200px;height:200px;background:rgba(255,255,255,.04);border-radius:50%}
        .auth-side::after{content:'';position:absolute;bottom:-80px;left:-40px;width:240px;height:240px;background:rgba(56,189,248,.06);border-radius:50%}
        .auth-side h2{font-size:28px;font-weight:800;margin-bottom:12px;line-height:1.3;position:relative;letter-spacing:-.3px}
        .auth-side>p{font-size:14px;opacity:.8;line-height:1.7;margin-bottom:28px;position:relative}
        .side-features{list-style:none;position:relative}.side-features li{display:flex;align-items:center;gap:12px;font-size:14px;opacity:.85;padding:9px 0}.side-features li span{font-size:20px;width:30px;text-align:center;flex-shrink:0}
        .side-divider{height:1px;background:rgba(255,255,255,.1);margin:24px 0;position:relative}
        .side-stat{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;position:relative}
        .side-stat div{text-align:center;padding:12px 0}.side-stat .num{font-size:24px;font-weight:800;display:block}.side-stat .lbl{font-size:11px;opacity:.6;margin-top:3px;display:block}

        /* ── Right Panel ── */
        .auth-form{flex:1;padding:40px 42px;display:flex;flex-direction:column;justify-content:center;overflow:hidden}
        .auth-form h2{font-size:24px;font-weight:800;color:#0f172a;margin-bottom:4px;letter-spacing:-.3px}
        .auth-form .subtitle{font-size:14px;color:#64748b;margin-bottom:24px;line-height:1.5}

        /* ── Progress ── */
        .progress-bar{display:flex;align-items:center;gap:0;margin-bottom:28px}
        .progress-step{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#94a3b8;transition:color .3s}
        .progress-step.active{color:#2563eb}
        .progress-step.done{color:#10b981}
        .step-circle{width:32px;height:32px;border-radius:50%;border:2px solid #e2e8f0;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;transition:all .3s;flex-shrink:0}
        .progress-step.active .step-circle{border-color:#2563eb;background:#2563eb;color:#fff;box-shadow:0 4px 12px rgba(37,99,235,.3)}
        .progress-step.done .step-circle{border-color:#10b981;background:#10b981;color:#fff}
        .step-line{flex:1;height:3px;background:#e2e8f0;border-radius:2px;margin:0 12px;transition:background .4s}
        .step-line.done{background:#10b981}
        .step-label{white-space:nowrap}

        /* ── Form ── */
        .step-content{animation:slideIn .4s cubic-bezier(.16,1,.3,1)}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .form-group{margin-bottom:18px}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:7px}
        .form-group label .req{color:#ef4444;margin-left:1px}
        .input-wrapper{position:relative}
        .input-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:16px;color:#94a3b8;pointer-events:none;transition:color .2s;z-index:1}
        .input-wrapper input,.input-wrapper select{width:100%;padding:13px 14px 13px 42px;border:2px solid #e2e8f0;border-radius:12px;font-size:14px;font-family:inherit;color:#0f172a;outline:none;transition:all .25s;background:#fff}
        .input-wrapper input:focus,.input-wrapper select:focus{border-color:#2563eb;box-shadow:0 0 0 4px rgba(37,99,235,.08)}
        .input-wrapper input:focus ~ .input-icon,.input-wrapper input:focus + .input-icon{color:#2563eb}
        .input-wrapper input.valid{border-color:#10b981}
        .input-wrapper input.valid ~ .input-icon{color:#10b981}
        .input-wrapper input.invalid{border-color:#ef4444;animation:shake .4s}
        .input-wrapper input.invalid ~ .input-icon{color:#ef4444}
        .input-wrapper select{padding-left:14px;appearance:auto;cursor:pointer}
        .toggle-pass{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:18px;cursor:pointer;color:#94a3af;padding:4px;transition:color .2s;z-index:2}.toggle-pass:hover{color:#334155}

        /* ── Field Messages ── */
        .field-msg{font-size:12px;margin-top:5px;display:none;align-items:center;gap:4px;font-weight:500}.field-msg.show{display:flex}.field-msg.error{color:#ef4444}.field-msg.success{color:#10b981}

        /* ── Password Strength ── */
        .password-strength{margin-top:8px}
        .strength-bar{height:4px;background:#e2e8f0;border-radius:4px;overflow:hidden;margin-bottom:5px}
        .strength-fill{height:100%;border-radius:4px;transition:all .4s cubic-bezier(.16,1,.3,1);width:0}
        .strength-fill.weak{width:33%;background:linear-gradient(90deg,#ef4444,#f87171)}
        .strength-fill.fair{width:66%;background:linear-gradient(90deg,#f59e0b,#fbbf24)}
        .strength-fill.strong{width:100%;background:linear-gradient(90deg,#10b981,#34d399)}
        .strength-text{font-size:11px;font-weight:700;letter-spacing:.3px;text-transform:uppercase}

        /* ── CAPTCHA ── */
        .captcha-group{background:#f8fafc;border:2px solid #e2e8f0;border-radius:12px;padding:16px 18px;display:flex;align-items:center;gap:14px;margin-bottom:18px;transition:border-color .2s}
        .captcha-group:focus-within{border-color:#2563eb;box-shadow:0 0 0 4px rgba(37,99,235,.08)}
        .captcha-icon{font-size:24px;flex-shrink:0}
        .captcha-info{flex:1}
        .captcha-info .label{font-size:12px;font-weight:600;color:#475569;margin-bottom:4px}
        .captcha-question{font-size:16px;font-weight:800;color:#0f172a;font-family:'Courier New',monospace;letter-spacing:1px}
        .captcha-input{width:80px;padding:10px!important;text-align:center;font-size:16px!important;font-weight:700;letter-spacing:2px;border-radius:10px!important}
        .captcha-refresh{background:none;border:2px solid #e2e8f0;border-radius:10px;padding:8px 12px;cursor:pointer;font-size:18px;transition:all .2s;flex-shrink:0}
        .captcha-refresh:hover{border-color:#2563eb;color:#2563eb;background:#f0f9ff}

        /* ── Terms ── */
        .terms-row{display:flex;align-items:flex-start;gap:10px;margin:4px 0 20px;font-size:13px;color:#64748b;line-height:1.5}
        .terms-row input[type="checkbox"]{margin-top:3px;width:16px;height:16px;accent-color:#2563eb;cursor:pointer;flex-shrink:0;border-radius:4px}
        .terms-row a{color:#2563eb;font-weight:600;text-decoration:none}.terms-row a:hover{text-decoration:underline}

        /* ── Buttons ── */
        .btn-row{display:flex;gap:12px;margin-top:4px}
        .submit-btn{flex:1;padding:14px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 14px rgba(37,99,235,.3)}
        .submit-btn:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 8px 24px rgba(37,99,235,.35)}
        .submit-btn:active:not(:disabled){transform:translateY(0)}
        .submit-btn:disabled{opacity:.45;cursor:not-allowed;transform:none;box-shadow:none}
        .submit-btn .spinner{display:none;width:20px;height:20px;border:2.5px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite}
        .submit-btn.loading .spinner{display:block}.submit-btn.loading .btn-text{display:none}
        @keyframes spin{to{transform:rotate(360deg)}}
        .back-btn{padding:14px 22px;background:#fff;color:#64748b;border:2px solid #e2e8f0;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .2s}
        .back-btn:hover{border-color:#2563eb;color:#2563eb}

        /* ── Divider & Social ── */
        .divider{display:flex;align-items:center;gap:14px;margin:22px 0 18px;font-size:13px;color:#94a3b8;font-weight:500}.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e2e8f0}
        .social-btns{display:flex;gap:10px}
        .social-btn{flex:1;padding:12px;border:2px solid #e2e8f0;border-radius:12px;background:#fff;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;color:#334155}.social-btn:hover{border-color:#2563eb;background:#f8fafc;transform:translateY(-1px)}
        .guest-link{text-align:center;margin-top:16px;font-size:13px;color:#64748b}
        .guest-link a{color:#2563eb;font-weight:600;text-decoration:none;transition:color .2s}.guest-link a:hover{color:#1d4ed8;text-decoration:underline}

        /* ── Footer Link ── */
        .auth-footer{text-align:center;font-size:14px;color:#64748b;margin-top:20px;padding-top:18px;border-top:1px solid #f1f5f9}
        .auth-footer a{color:#2563eb;font-weight:700;text-decoration:none}.auth-footer a:hover{text-decoration:underline}

        /* ── Alert ── */
        .alert{padding:14px 16px;border-radius:10px;font-size:13px;margin-bottom:18px;font-weight:500;display:flex;align-items:flex-start;gap:8px;animation:pop .3s cubic-bezier(.16,1,.3,1);line-height:1.5}
        .alert-danger{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
        .alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
        .alert-icon{font-size:16px;flex-shrink:0;margin-top:1px}

        /* ── Footer ── */
        .footer{background:#0f172a;color:#fff;padding:20px 6%;text-align:center;margin-top:auto}.footer p{font-size:13px;color:#64748b}

        /* ── Mobile ── */
        @media(max-width:768px){
            .navbar{height:60px;padding:0 20px}.logo{font-size:23px}
            .nav-links{display:none;position:absolute;top:60px;left:0;right:0;background:#fff;flex-direction:column;padding:16px 20px;box-shadow:0 8px 30px rgba(0,0,0,.08);gap:0}.nav-links.open{display:flex}.nav-links a{padding:14px 0;border-bottom:1px solid #f1f5f9}.menu-toggle{display:block}
            .auth-wrapper{padding:16px 12px}.auth-container{flex-direction:column;border-radius:16px}
            .auth-side{flex:none;padding:32px 24px}.auth-side .side-stat{display:none}.auth-side .side-divider{display:none}
            .auth-form{padding:28px 20px}.form-row{grid-template-columns:1fr}
            .captcha-group{flex-wrap:wrap}.captcha-input{width:100%!important}
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
    <div class="auth-container">

        <!-- ── Left Side ── -->
        <div class="auth-side">
            <h2>Create Your EngiHub Account</h2>
            <p>Join thousands of engineering students accessing free study resources, notes, papers and more.</p>
            <ul class="side-features">
                <li><span>&#128218;</span> 500+ study notes &amp; resources</li>
                <li><span>&#128196;</span> Previous year question papers</li>
                <li><span>&#128295;</span> Lab manuals &amp; practicals</li>
                <li><span>&#128187;</span> Coding practice hub</li>
                <li><span>&#127919;</span> Placement preparation</li>
            </ul>
            <div class="side-divider"></div>
            <div class="side-stat">
                <div><span class="num">500+</span><span class="lbl">Students</span></div>
                <div><span class="num">50+</span><span class="lbl">Resources</span></div>
                <div><span class="num">100%</span><span class="lbl">Free</span></div>
            </div>
        </div>

        <!-- ── Right Side (Form) ── -->
        <div class="auth-form">
            <h2>Join EngiHub Today</h2>
            <p class="subtitle">Fill in your details to get started. It only takes a minute.</p>

            <?php if (!empty($regErrors)): ?>
                <div class="alert alert-danger">
                    <span class="alert-icon">&#9888;</span>
                    <span><?php echo implode('<br>', array_map('htmlspecialchars', $regErrors)); ?></span>
                </div>
            <?php endif; ?>

            <!-- Progress Bar -->
            <div class="progress-bar" id="progressBar">
                <div class="progress-step active" id="pStep1">
                    <span class="step-circle">1</span>
                    <span class="step-label">Basic Info</span>
                </div>
                <div class="step-line" id="stepLine1"></div>
                <div class="progress-step" id="pStep2">
                    <span class="step-circle">2</span>
                    <span class="step-label">Security</span>
                </div>
            </div>

            <form method="POST" action="register-process.php" novalidate id="regForm">
                <?php csrfField(); ?>
                <input type="hidden" name="step" id="currentStep" value="1">

                <!-- ── Step 1: Basic Info ── -->
                <div id="step1" class="step-content">
                    <div class="form-group">
                        <label>Full Name <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <span class="input-icon">&#128100;</span>
                            <input type="text" name="full_name" id="fName" placeholder="Enter your full name" value="<?php echo htmlspecialchars($regData['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="field-msg error" id="fNameMsg"></div>
                    </div>

                    <div class="form-group">
                        <label>Email Address <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <span class="input-icon">&#9993;</span>
                            <input type="email" name="email" id="fEmail" placeholder="you@example.com" value="<?php echo htmlspecialchars($regData['email'] ?? ''); ?>" required>
                        </div>
                        <div class="field-msg error" id="fEmailMsg"></div>
                    </div>

                    <div class="form-group">
                        <label>Mobile Number <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <span class="input-icon">&#128241;</span>
                            <input type="tel" name="mobile" id="fMobile" placeholder="10-digit mobile number" maxlength="10" value="<?php echo htmlspecialchars($regData['mobile'] ?? ''); ?>" required>
                        </div>
                        <div class="field-msg error" id="fMobileMsg"></div>
                    </div>

                    <div class="form-group">
                        <label>College Name <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <span class="input-icon">&#127979;</span>
                            <input type="text" name="college_name" id="fCollege" placeholder="Your college / university name" value="<?php echo htmlspecialchars($regData['college_name'] ?? ''); ?>" required>
                        </div>
                        <div class="field-msg error" id="fCollegeMsg"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Branch / Department <span class="req">*</span></label>
                            <div class="input-wrapper">
                                <select name="branch" id="fBranch" required>
                                    <?php $selBranch = $regData['branch'] ?? ''; ?>
                                    <option value="">Select Branch</option>
                                    <option value="CSE" <?php if ($selBranch === 'CSE') echo 'selected'; ?>>Computer Science Engineering</option>
                                    <option value="ECE" <?php if ($selBranch === 'ECE') echo 'selected'; ?>>Electronics Engineering</option>
                                    <option value="EE" <?php if ($selBranch === 'EE') echo 'selected'; ?>>Electrical Engineering</option>
                                    <option value="ME" <?php if ($selBranch === 'ME') echo 'selected'; ?>>Mechanical Engineering</option>
                                    <option value="CE" <?php if ($selBranch === 'CE') echo 'selected'; ?>>Civil Engineering</option>
                                    <option value="Other" <?php if ($selBranch === 'Other') echo 'selected'; ?>>Other</option>
                                </select>
                            </div>
                            <div class="field-msg error" id="fBranchMsg"></div>
                        </div>
                        <div class="form-group">
                            <label>Semester <span class="req">*</span></label>
                            <div class="input-wrapper">
                                <select name="semester" id="fSemester" required>
                                    <?php $selSem = $regData['semester'] ?? ''; ?>
                                    <option value="">Select Semester</option>
                                    <option value="1" <?php if ($selSem === '1') echo 'selected'; ?>>1st Semester</option>
                                    <option value="2" <?php if ($selSem === '2') echo 'selected'; ?>>2nd Semester</option>
                                    <option value="3" <?php if ($selSem === '3') echo 'selected'; ?>>3rd Semester</option>
                                    <option value="4" <?php if ($selSem === '4') echo 'selected'; ?>>4th Semester</option>
                                    <option value="5" <?php if ($selSem === '5') echo 'selected'; ?>>5th Semester</option>
                                    <option value="6" <?php if ($selSem === '6') echo 'selected'; ?>>6th Semester</option>
                                    <option value="7" <?php if ($selSem === '7') echo 'selected'; ?>>7th Semester</option>
                                    <option value="8" <?php if ($selSem === '8') echo 'selected'; ?>>8th Semester</option>
                                </select>
                            </div>
                            <div class="field-msg error" id="fSemMsg"></div>
                        </div>
                    </div>

                    <div class="btn-row">
                        <button type="button" class="submit-btn" id="nextBtn" disabled>
                            <span class="btn-text">Continue &#10148;</span>
                        </button>
                    </div>

                    <div class="divider"><span>or</span></div>
                    <div class="social-btns">
                        <button type="button" class="social-btn" onclick="alert('Google sign-up coming soon!')">&#128269; Continue with Google</button>
                        <button type="button" class="social-btn" onclick="alert('GitHub sign-up coming soon!')">&#128187; Continue with GitHub</button>
                    </div>
                    <div class="guest-link"><a href="student_dashboard.html">Continue as Guest &#8594;</a></div>
                </div>

                <!-- ── Step 2: Security ── -->
                <div id="step2" class="step-content" style="display:none">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password <span class="req">*</span></label>
                            <div class="input-wrapper">
                                <span class="input-icon">&#128274;</span>
                                <input type="password" id="fPass" name="password" placeholder="Min 6 characters" required>
                                <button type="button" class="toggle-pass" onclick="toggleP('fPass',this)" aria-label="Show password">&#128065;</button>
                            </div>
                            <div class="password-strength" id="passStrength" style="display:none">
                                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                                <span class="strength-text" id="strengthText"></span>
                            </div>
                            <div class="field-msg error" id="fPassMsg"></div>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password <span class="req">*</span></label>
                            <div class="input-wrapper">
                                <span class="input-icon">&#128274;</span>
                                <input type="password" id="fPass2" name="confirm_password" placeholder="Re-enter password" required>
                                <button type="button" class="toggle-pass" onclick="toggleP('fPass2',this)" aria-label="Show password">&#128065;</button>
                            </div>
                            <div class="field-msg error" id="fPass2Msg"></div>
                        </div>
                    </div>

                    <!-- CAPTCHA -->
                    <div class="captcha-group">
                        <span class="captcha-icon">&#128270;</span>
                        <div class="captcha-info">
                            <div class="label">Solve to verify you're human</div>
                            <div class="captcha-question" id="captchaQ"><?php echo htmlspecialchars($captchaQuestion); ?></div>
                        </div>
                        <input type="number" name="captcha_answer" id="fCaptcha" class="input-wrapper captcha-input" placeholder="?" required autocomplete="off">
                        <button type="button" class="captcha-refresh" onclick="location.reload()" title="New question">&#8635;</button>
                    </div>
                    <div class="field-msg error" id="fCaptchaMsg" style="margin-top:-12px;margin-bottom:12px"></div>

                    <!-- Terms -->
                    <div class="terms-row">
                        <input type="checkbox" id="fTerms" name="terms" required>
                        <label for="fTerms">I agree to the <a href="#">Terms &amp; Conditions</a> and <a href="#">Privacy Policy</a></label>
                    </div>
                    <div class="field-msg error" id="fTermsMsg" style="margin-top:-14px;margin-bottom:12px"></div>

                    <div class="btn-row">
                        <button type="button" class="back-btn" id="backBtn">&#8592; Back</button>
                        <button type="submit" class="submit-btn" id="submitBtn" disabled>
                            <span class="btn-text">Create Account &#10148;</span>
                            <span class="spinner"></span>
                        </button>
                    </div>
                </div>
            </form>

            <div class="auth-footer">Already have an account? <a href="login.php">Login here</a></div>
        </div>
    </div>
</div>

<footer class="footer"><p>&copy; 2026 EngiHub. All rights reserved. Built with &#10084; for engineering students.</p></footer>

<script>
document.getElementById('menuToggle').addEventListener('click',function(){document.getElementById('navLinks').classList.toggle('open')});
function toggleP(id,btn){var i=document.getElementById(id);i.type=i.type==='password'?'text':'password';btn.innerHTML=i.type==='password'?'&#128065;':'&#128064;'}

var fN=document.getElementById('fName'),fE=document.getElementById('fEmail'),fM=document.getElementById('fMobile'),
    fC=document.getElementById('fCollege'),fB=document.getElementById('fBranch'),fS=document.getElementById('fSemester'),
    fP=document.getElementById('fPass'),fP2=document.getElementById('fPass2'),
    fCa=document.getElementById('fCaptcha'),fT=document.getElementById('fTerms'),
    nextBtn=document.getElementById('nextBtn'),submitBtn=document.getElementById('submitBtn');

function showM(id,m,t){var e=document.getElementById(id);e.textContent=m;e.className='field-msg show '+t}
function hideM(id){document.getElementById(id).className='field-msg'}

function step1Valid(){
    return fN.value.trim().length>=2&&/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(fE.value.trim())&&/^[6-9]\d{9}$/.test(fM.value.trim())&&fC.value.trim().length>=2&&fB.value!==''&&fS.value!=='';
}
function step2Valid(){
    return fP.value.length>=6&&fP2.value===fP.value&&fCa.value!==''&&fT.checked;
}

function goToStep2(){
    document.getElementById('step1').style.display='none';
    document.getElementById('step2').style.display='block';
    document.getElementById('currentStep').value='2';
    document.getElementById('pStep1').className='progress-step done';
    document.getElementById('stepLine1').className='step-line done';
    document.getElementById('pStep2').className='progress-step active';
    submitBtn.disabled=!step2Valid();
}
function goToStep1(){
    document.getElementById('step2').style.display='none';
    document.getElementById('step1').style.display='block';
    document.getElementById('currentStep').value='1';
    document.getElementById('pStep1').className='progress-step active';
    document.getElementById('stepLine1').className='step-line';
    document.getElementById('pStep2').className='progress-step';
    nextBtn.disabled=!step1Valid();
}

nextBtn.addEventListener('click',goToStep2);
document.getElementById('backBtn').addEventListener('click',goToStep1);

fN.addEventListener('input',function(){
    var v=this.value.trim();
    if(!v){hideM('fNameMsg');this.classList.remove('valid','invalid')}
    else if(v.length<2){showM('fNameMsg','At least 2 characters required','error');this.classList.add('invalid');this.classList.remove('valid')}
    else{hideM('fNameMsg');this.classList.add('valid');this.classList.remove('invalid')}
    nextBtn.disabled=!step1Valid();
});
fE.addEventListener('input',function(){
    var v=this.value.trim();
    if(!v){hideM('fEmailMsg');this.classList.remove('valid','invalid')}
    else if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)){showM('fEmailMsg','Enter a valid email address','error');this.classList.add('invalid');this.classList.remove('valid')}
    else{hideM('fEmailMsg');this.classList.add('valid');this.classList.remove('invalid')}
    nextBtn.disabled=!step1Valid();
});
fM.addEventListener('input',function(){
    this.value=this.value.replace(/[^0-9]/g,'');
    var v=this.value;
    if(!v){hideM('fMobileMsg');this.classList.remove('valid','invalid')}
    else if(v.length<10){showM('fMobileMsg','Must be exactly 10 digits','error');this.classList.add('invalid');this.classList.remove('valid')}
    else if(!/^[6-9]/.test(v)){showM('fMobileMsg','Must start with 6, 7, 8 or 9','error');this.classList.add('invalid');this.classList.remove('valid')}
    else{hideM('fMobileMsg');this.classList.add('valid');this.classList.remove('invalid')}
    nextBtn.disabled=!step1Valid();
});
fC.addEventListener('input',function(){
    var v=this.value.trim();
    if(!v){hideM('fCollegeMsg');this.classList.remove('valid','invalid')}
    else if(v.length<2){showM('fCollegeMsg','Enter your college name','error');this.classList.add('invalid');this.classList.remove('valid')}
    else{hideM('fCollegeMsg');this.classList.add('valid');this.classList.remove('invalid')}
    nextBtn.disabled=!step1Valid();
});
fB.addEventListener('change',function(){this.value?hideM('fBranchMsg'):showM('fBranchMsg','Please select a branch','error');nextBtn.disabled=!step1Valid()});
fS.addEventListener('change',function(){this.value?hideM('fSemMsg'):showM('fSemMsg','Please select a semester','error');nextBtn.disabled=!step1Valid()});

fP.addEventListener('input',function(){
    var v=this.value,s=document.getElementById('passStrength'),f=document.getElementById('strengthFill'),t=document.getElementById('strengthText');
    if(!v){s.style.display='none';hideM('fPassMsg');this.classList.remove('valid','invalid');submitBtn.disabled=!step2Valid();return}
    s.style.display='block';
    var sc=0;if(v.length>=6)sc++;if(v.length>=10)sc++;if(/[A-Z]/.test(v)&&/[a-z]/.test(v))sc++;if(/[0-9]/.test(v))sc++;if(/[^A-Za-z0-9]/.test(v))sc++;
    f.className='strength-fill';t.className='strength-text';
    if(sc<=2){f.classList.add('weak');t.textContent='Weak';t.style.color='#ef4444'}
    else if(sc<=3){f.classList.add('fair');t.textContent='Fair';t.style.color='#f59e0b'}
    else{f.classList.add('strong');t.textContent='Strong';t.style.color='#10b981'}
    if(v.length<6){showM('fPassMsg','Minimum 6 characters required','error');this.classList.add('invalid');this.classList.remove('valid')}
    else{hideM('fPassMsg');this.classList.add('valid');this.classList.remove('invalid')}
    if(fP2.value.length>0)validateConfirm();
    submitBtn.disabled=!step2Valid();
});
function validateConfirm(){
    var v=fP2.value;
    if(!v){hideM('fPass2Msg');fP2.classList.remove('valid','invalid')}
    else if(v!==fP.value){showM('fPass2Msg','Passwords do not match','error');fP2.classList.add('invalid');fP2.classList.remove('valid')}
    else{hideM('fPass2Msg');fP2.classList.add('valid');fP2.classList.remove('invalid')}
}
fP2.addEventListener('input',function(){validateConfirm();submitBtn.disabled=!step2Valid()});
fCa.addEventListener('input',function(){submitBtn.disabled=!step2Valid()});
fT.addEventListener('change',function(){if(!this.checked){showM('fTermsMsg','You must accept the terms','error')}else{hideM('fTermsMsg')}submitBtn.disabled=!step2Valid()});

document.getElementById('regForm').addEventListener('submit',function(e){
    if(submitBtn.disabled){e.preventDefault();return}
    submitBtn.classList.add('loading');submitBtn.disabled=true;
});
</script>
</body>
</html>

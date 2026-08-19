<?php
require_once 'db.php';
if (isStudent()) { header("Location: dashboard.php"); exit; }
if (isAdmin()) { header("Location: admin/"); exit; }

$errors = [];
$step = intval($_GET['step'] ?? 1);

$states = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Delhi','Jammu & Kashmir','Ladakh','Chandigarh','Puducherry','Andaman & Nicobar','Dadra & Nagar Haveli','Daman & Diu','Lakshadweep'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';

    if ($act === 'send_otp') {
        $email = sanitize($conn, $_POST['email'] ?? '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['reg_otp'] = $otp;
            $_SESSION['reg_otp_email'] = $email;
            $_SESSION['reg_otp_time'] = time();
            $_SESSION['reg_otp_data'] = [
                'full_name' => sanitize($conn, $_POST['full_name'] ?? ''),
                'email' => $email,
                'mobile' => sanitize($conn, $_POST['mobile'] ?? ''),
                'college_name' => sanitize($conn, $_POST['college_name'] ?? ''),
                'student_id' => sanitize($conn, $_POST['student_id'] ?? ''),
                'branch' => sanitize($conn, $_POST['branch'] ?? ''),
                'semester' => sanitize($conn, $_POST['semester'] ?? ''),
                'password' => $_POST['password'] ?? '',
            ];
            header("Location: register.php?step=2&sent=1");
            exit;
        }
    }

    if ($act === 'verify_otp') {
        $entered = trim($_POST['otp'] ?? '');
        $stored = $_SESSION['reg_otp'] ?? '';
        $otp_time = $_SESSION['reg_otp_time'] ?? 0;
        if (empty($entered)) { $errors[] = "Please enter the OTP"; }
        elseif (time() - $otp_time > 300) { $errors[] = "OTP expired. Please request a new one."; unset($_SESSION['reg_otp']); }
        elseif ($entered !== $stored) { $errors[] = "Invalid OTP. Please try again."; }
        else {
            $d = $_SESSION['reg_otp_data'] ?? [];
            $dob = sanitize($conn, $_POST['dob'] ?? '');
            $state = sanitize($conn, $_POST['state'] ?? '');
            $city = sanitize($conn, $_POST['city'] ?? '');
            $hashed = password_hash($d['password'], PASSWORD_DEFAULT);
            $photo_name = '';
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                    $photo_name = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '', $_FILES['profile_photo']['name']);
                    $dir = 'uploads/students/';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dir . $photo_name);
                }
            }
            $stmt = $conn->prepare("INSERT INTO students (full_name, email, mobile, password, college_name, student_id, branch, semester, dob, state, city, profile_photo, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssssssssssss", $d['full_name'], $d['email'], $d['mobile'], $hashed, $d['college_name'], $d['student_id'], $d['branch'], $d['semester'], $dob, $state, $city, $photo_name);
            if ($stmt->execute()) {
                unset($_SESSION['reg_otp'], $_SESSION['reg_otp_email'], $_SESSION['reg_otp_time'], $_SESSION['reg_otp_data']);
                $_SESSION['success_msg'] = "Registration successful! Please login.";
                header("Location: login.php");
                exit;
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
    }

    if ($act === 'resend_otp') {
        $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['reg_otp'] = $otp;
        $_SESSION['reg_otp_time'] = time();
        header("Location: register.php?step=2&sent=1&resend=1");
        exit;
    }
}

$captcha_a = rand(1, 15); $captcha_b = rand(1, 15); $captcha_op = rand(0,1) ? '+' : '-';
$_SESSION['captcha_a'] = $captcha_a; $_SESSION['captcha_b'] = $captcha_b; $_SESSION['captcha_op'] = $captcha_op;
$captcha_answer = $captcha_op === '+' ? $captcha_a + $captcha_b : $captcha_a - $captcha_b;
$_SESSION['captcha_ans'] = $captcha_answer;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#2563eb">
    <title>Register - EngiHub</title>
    <style>
        :root{--bg:#f5f7fb;--card:#fff;--text:#111827;--text2:#6b7280;--text3:#374151;--border:#e5e7eb;--blue:#2563eb;--blue-d:#1d4ed8;--green:#10b981;--red:#ef4444;--yellow:#f59e0b;--side-from:#0f172a;--side-to:#1e3a5f;--side-end:#2563eb;--shadow:0 12px 40px rgba(0,0,0,.1)}
        html.dark{--bg:#0f172a;--card:#1e293b;--text:#f1f5f9;--text2:#94a3b8;--text3:#cbd5e1;--border:#334155;--shadow:0 12px 40px rgba(0,0,0,.4)}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column;transition:background .3s,color .3s}

        .navbar{width:100%;height:70px;background:var(--card);display:flex;align-items:center;justify-content:space-between;padding:0 6%;box-shadow:0 2px 10px rgba(0,0,0,.08);position:sticky;top:0;z-index:100;transition:background .3s}
        .logo{font-size:28px;font-weight:bold;color:var(--blue);text-decoration:none}.logo span{color:var(--text)}
        .nav-links{display:flex;gap:20px;list-style:none;align-items:center}.nav-links a{text-decoration:none;color:var(--text3);font-weight:500;transition:color .2s;font-size:14px}.nav-links a:hover{color:var(--blue)}
        .nav-btn{background:var(--blue);color:white!important;padding:10px 20px;border-radius:8px;font-weight:600}.nav-btn:hover{background:var(--blue-d)}
        .menu-toggle{display:none;background:none;border:none;font-size:28px;cursor:pointer;color:var(--text)}
        .dark-toggle{background:none;border:2px solid var(--border);border-radius:8px;padding:8px 12px;cursor:pointer;font-size:16px;transition:all .2s;color:var(--text)}.dark-toggle:hover{border-color:var(--blue);color:var(--blue)}

        .auth-wrapper{flex:1;display:flex;align-items:center;justify-content:center;padding:24px 6%}
        .auth-container{display:flex;max-width:1050px;width:100%;border-radius:20px;overflow:hidden;box-shadow:var(--shadow);background:var(--card);animation:fadeUp .5s ease-out;transition:background .3s}
        @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

        .auth-side{flex:0 0 380px;background:linear-gradient(135deg,var(--side-from),var(--side-to) 50%,var(--side-end));color:white;padding:48px 36px;display:flex;flex-direction:column;justify-content:center;position:relative;overflow:hidden}
        .auth-side::before{content:'';position:absolute;top:-40px;right:-40px;width:160px;height:160px;background:rgba(255,255,255,.06);border-radius:50%}
        .auth-side::after{content:'';position:absolute;bottom:-60px;left:-30px;width:200px;height:200px;background:rgba(37,211,102,.08);border-radius:50%}
        .auth-side h2{font-size:28px;font-weight:800;margin-bottom:12px;line-height:1.3;position:relative}
        .auth-side>p{font-size:14px;opacity:.85;line-height:1.7;margin-bottom:28px;position:relative}
        .side-features{list-style:none;position:relative}.side-features li{display:flex;align-items:center;gap:12px;font-size:14px;opacity:.9;padding:9px 0}.side-features li span{font-size:20px;width:28px;text-align:center}
        .side-stat{display:flex;gap:24px;margin-top:28px;padding-top:24px;border-top:1px solid rgba(255,255,255,.15);position:relative}
        .side-stat div{text-align:center}.side-stat .num{font-size:22px;font-weight:800;display:block}.side-stat .lbl{font-size:11px;opacity:.7;margin-top:2px}

        .auth-form-section{flex:1;padding:36px 40px;display:flex;flex-direction:column;justify-content:center;max-height:88vh;overflow-y:auto}
        .auth-form-section::-webkit-scrollbar{width:4px}.auth-form-section::-webkit-scrollbar-thumb{background:#d1d5db;border-radius:4px}
        .auth-form-section h2{font-size:24px;font-weight:800;color:var(--text);margin-bottom:4px;transition:color .3s}
        .auth-form-section .subtitle{font-size:14px;color:var(--text2);margin-bottom:20px;transition:color .3s}

        .progress-bar{display:flex;align-items:center;gap:0;margin-bottom:24px}
        .progress-step{display:flex;align-items:center;gap:8px;flex:1}
        .progress-step .p-circle{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;transition:all .3s;flex-shrink:0}
        .progress-step .p-circle.active{background:var(--blue);color:white}.progress-step .p-circle.done{background:var(--green);color:white}.progress-step .p-circle.pending{background:var(--border);color:var(--text2)}
        .progress-step .p-label{font-size:12px;font-weight:600;color:var(--text2);transition:color .3s}.progress-step .p-label.active{color:var(--blue)}
        .progress-line{width:40px;height:2px;background:var(--border);flex-shrink:0;margin:0 4px;transition:background .3s}.progress-line.done{background:var(--green)}

        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .form-group{margin-bottom:16px}
        .form-group label{display:block;font-size:13px;font-weight:600;color:var(--text3);margin-bottom:6px;transition:color .3s}
        .form-group label .req{color:var(--red);margin-left:2px}
        .form-group label .opt{color:var(--text2);font-weight:400;font-size:11px;margin-left:4px}
        .input-wrapper{position:relative}
        .input-wrapper input,.input-wrapper select{width:100%;padding:12px 14px;border:2px solid var(--border);border-radius:10px;font-size:14px;font-family:inherit;color:var(--text);outline:none;transition:all .2s;background:var(--card);appearance:auto}
        .input-wrapper input:focus,.input-wrapper select:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
        .input-wrapper input.valid{border-color:var(--green)}.input-wrapper input.invalid{border-color:var(--red)}
        .input-wrapper input::placeholder{color:var(--text2)}
        .toggle-pass{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:18px;cursor:pointer;color:var(--text2);padding:4px;transition:color .2s}.toggle-pass:hover{color:var(--text3)}

        .field-msg{font-size:11px;margin-top:4px;display:none;align-items:center;gap:4px}.field-msg.show{display:flex}.field-msg.error{color:var(--red)}.field-msg.success{color:var(--green)}

        .password-strength{margin-top:6px}.strength-bar{height:4px;background:var(--border);border-radius:4px;overflow:hidden;margin-bottom:4px;transition:background .3s}.strength-fill{height:100%;border-radius:4px;transition:all .3s;width:0}.strength-fill.weak{width:33%;background:var(--red)}.strength-fill.fair{width:66%;background:var(--yellow)}.strength-fill.strong{width:100%;background:var(--green)}.strength-text{font-size:11px}

        .photo-upload{display:flex;align-items:center;gap:16px;margin-bottom:16px}
        .photo-preview{width:72px;height:72px;border-radius:50%;background:var(--border);display:flex;align-items:center;justify-content:center;font-size:28px;color:var(--text2);overflow:hidden;flex-shrink:0;border:3px solid var(--border);transition:border-color .3s}
        .photo-preview img{width:100%;height:100%;object-fit:cover}
        .photo-btns{display:flex;flex-direction:column;gap:6px}
        .photo-label{display:inline-block;padding:8px 16px;background:var(--blue);color:white;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s;text-align:center}
        .photo-label:hover{background:var(--blue-d)}
        .photo-remove{background:none;border:none;color:var(--red);font-size:12px;font-weight:600;cursor:pointer;padding:0;text-align:left}
        .photo-remove:hover{text-decoration:underline}

        .terms-row{display:flex;align-items:flex-start;gap:10px;margin-bottom:20px}
        .terms-row input[type=checkbox]{width:18px;height:18px;margin-top:2px;accent-color:var(--blue);flex-shrink:0;cursor:pointer}
        .terms-row label{font-size:13px;color:var(--text2);cursor:pointer;line-height:1.5;transition:color .3s}
        .terms-row label a{color:var(--blue);font-weight:600;text-decoration:none}.terms-row label a:hover{text-decoration:underline}

        .submit-btn{width:100%;padding:14px;background:var(--blue);color:white;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px}
        .submit-btn:hover:not(:disabled){background:var(--blue-d);transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.3)}
        .submit-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
        .submit-btn .spinner{display:none;width:18px;height:18px;border:2px solid rgba(255,255,255,.3);border-top-color:white;border-radius:50%;animation:spin .6s linear infinite}
        .submit-btn.loading .spinner{display:block}.submit-btn.loading .btn-text{display:none}
        @keyframes spin{to{transform:rotate(360deg)}}

        .divider{display:flex;align-items:center;gap:12px;margin:20px 0;font-size:13px;color:var(--text2)}.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border)}

        .social-btns{display:flex;gap:10px;margin-bottom:16px}
        .social-btn{flex:1;padding:11px;border:2px solid var(--border);border-radius:10px;background:var(--card);font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;color:var(--text3)}
        .social-btn:hover{border-color:var(--blue);background:var(--card)}
        .social-btn svg{width:18px;height:18px}

        .guest-link{text-align:center;margin-top:8px}.guest-link a{font-size:13px;color:var(--text2);text-decoration:none;font-weight:500;transition:color .2s}.guest-link a:hover{color:var(--blue)}

        .auth-footer{text-align:center;font-size:14px;color:var(--text2);margin-top:16px;padding-top:14px;border-top:1px solid var(--border);transition:color .3s,border-color .3s}
        .auth-footer a{color:var(--blue);font-weight:700;text-decoration:none}.auth-footer a:hover{text-decoration:underline}

        .alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px;font-weight:500;display:flex;align-items:center;gap:8px;animation:fadeUp .3s ease-out}
        .alert-danger{background:#fef2f2;color:var(--red);border:1px solid #fecaca}
        .alert-success{background:#f0fdf4;color:var(--green);border:1px solid #bbf7d0}
        html.dark .alert-danger{background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.2)}
        html.dark .alert-success{background:rgba(16,185,129,.1);border-color:rgba(16,185,129,.2)}

        .otp-box{background:var(--bg);border:2px dashed var(--border);border-radius:14px;padding:24px;text-align:center;margin-bottom:20px;transition:background .3s,border-color .3s}
        .otp-box .otp-icon{font-size:40px;margin-bottom:8px}
        .otp-box h3{font-size:16px;font-weight:700;color:var(--text);margin-bottom:4px}
        .otp-box p{font-size:13px;color:var(--text2)}
        .otp-box .otp-email{font-weight:700;color:var(--blue)}
        .otp-inputs{display:flex;gap:10px;justify-content:center;margin:20px 0}
        .otp-inputs input{width:50px;height:56px;text-align:center;font-size:22px;font-weight:700;border:2px solid var(--border);border-radius:10px;background:var(--card);color:var(--text);outline:none;transition:all .2s;font-family:inherit}
        .otp-inputs input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(37,99,235,.15)}
        .resend-row{text-align:center;font-size:13px;color:var(--text2)}
        .resend-row a{color:var(--blue);font-weight:600;text-decoration:none;cursor:pointer}.resend-row a:hover{text-decoration:underline}

        .captcha-row{display:flex;gap:12px;align-items:flex-end}
        .captcha-row .captcha-display{padding:12px 20px;background:var(--bg);border:2px solid var(--border);border-radius:10px;font-size:18px;font-weight:800;color:var(--text);font-family:'Courier New',monospace;letter-spacing:2px;min-width:130px;text-align:center;transition:background .3s,border-color .3s}
        .captcha-refresh{background:none;border:none;font-size:20px;cursor:pointer;color:var(--blue);padding:4px;transition:transform .3s}.captcha-refresh:hover{transform:rotate(180deg)}

        .footer{background:#111827;color:white;padding:20px 6%;text-align:center;margin-top:auto}.footer p{font-size:13px;color:#6b7280}

        @media(max-width:768px){
            .navbar{height:60px;padding:0 20px}.logo{font-size:23px}
            .nav-links{display:none;position:absolute;top:60px;left:0;right:0;background:var(--card);flex-direction:column;padding:16px 20px;box-shadow:0 8px 24px rgba(0,0,0,.1);gap:0}.nav-links.open{display:flex}.nav-links a{padding:12px 0;border-bottom:1px solid var(--border)}.menu-toggle{display:block}
            .auth-wrapper{padding:12px}.auth-container{flex-direction:column}
            .auth-side{flex:none;padding:28px 24px}.auth-side .side-stat{display:none}
            .auth-form-section{padding:20px 18px;max-height:none}
            .form-row{grid-template-columns:1fr}
            .otp-inputs input{width:44px;height:50px;font-size:20px}
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
        <li><a href="login.php">Login</a></li>
        <li><a href="register.php" class="nav-btn">Register</a></li>
    </ul>
    <button class="dark-toggle" id="darkToggle" title="Toggle dark mode">&#127769;</button>
</nav>

<div class="auth-wrapper">
    <div class="auth-container">
        <div class="auth-side">
            <h2>Join EngiHub Today</h2>
            <p>Create your free account and start accessing everything an engineering student needs.</p>
            <ul class="side-features">
                <li><span>&#128218;</span> 500+ study notes &amp; resources</li>
                <li><span>&#128196;</span> Previous year question papers</li>
                <li><span>&#128295;</span> Lab manuals &amp; practicals</li>
                <li><span>&#128187;</span> Coding practice hub</li>
                <li><span>&#127919;</span> Placement preparation</li>
            </ul>
            <div class="side-stat">
                <div><span class="num">500+</span><span class="lbl">Students</span></div>
                <div><span class="num">50+</span><span class="lbl">Resources</span></div>
                <div><span class="num">100%</span><span class="lbl">Free</span></div>
            </div>
        </div>

        <div class="auth-form-section">
            <div class="progress-bar">
                <div class="progress-step"><div class="p-circle <?php echo $step===1?'active':($step===2?'done':'pending'); ?>"><?php echo $step===2?'&#10003;':'1'; ?></div><span class="p-label <?php echo $step===1?'active':''; ?>">Account</span></div>
                <div class="progress-line <?php echo $step===2?'done':''; ?>"></div>
                <div class="progress-step"><div class="p-circle <?php echo $step===2?'active':'pending'; ?>">2</div><span class="p-label <?php echo $step===2?'active':''; ?>">Verify</span></div>
            </div>

            <?php if ($step === 1): ?>

            <h2>Create Your Account</h2>
            <p class="subtitle">Join EngiHub and start your learning journey.</p>

            <?php if (!empty($errors)): ?><div class="alert alert-danger">&#9888; <?php echo implode('<br>',$errors); ?></div><?php endif; ?>

            <div class="social-btns">
                <button type="button" class="social-btn" onclick="alert('Google Sign-Up coming soon!')">
                    <svg viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    Google
                </button>
                <button type="button" class="social-btn" onclick="alert('GitHub Sign-Up coming soon!')">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                    GitHub
                </button>
            </div>
            <div class="divider">or continue with email</div>

            <form method="POST" action="register.php" novalidate id="regForm">
                <input type="hidden" name="act" value="send_otp">

                <div class="photo-upload">
                    <div class="photo-preview" id="photoPreview">&#128100;</div>
                    <div class="photo-btns">
                        <label class="photo-label" for="photoInput">Choose Photo</label>
                        <input type="file" id="photoInput" accept="image/*" style="display:none">
                        <button type="button" class="photo-remove" id="photoRemove" style="display:none">Remove photo</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Full Name <span class="req">*</span></label>
                    <div class="input-wrapper"><input type="text" name="full_name" id="fName" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required></div>
                    <div class="field-msg error" id="fNameMsg"></div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email Address <span class="req">*</span></label>
                        <div class="input-wrapper"><input type="email" name="email" id="fEmail" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required></div>
                        <div class="field-msg error" id="fEmailMsg"></div>
                    </div>
                    <div class="form-group">
                        <label>Mobile Number <span class="req">*</span></label>
                        <div class="input-wrapper"><input type="tel" name="mobile" id="fMobile" placeholder="10-digit number" maxlength="10" value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>" required></div>
                        <div class="field-msg error" id="fMobileMsg"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>College Name <span class="opt">(optional)</span></label>
                    <div class="input-wrapper"><input type="text" name="college_name" placeholder="Your college name" value="<?php echo htmlspecialchars($_POST['college_name'] ?? ''); ?>"></div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Student ID / Roll No <span class="opt">(optional)</span></label>
                        <div class="input-wrapper"><input type="text" name="student_id" placeholder="e.g. 21CS001" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>"></div>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth <span class="opt">(optional)</span></label>
                        <div class="input-wrapper"><input type="date" name="dob" value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Branch / Department <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <select name="branch" id="fBranch" required>
                                <option value="">Select Branch</option>
                                <option value="CSE" <?php if(($_POST['branch']??'')==='CSE') echo'selected';?>>Computer Science Engineering</option>
                                <option value="ECE" <?php if(($_POST['branch']??'')==='ECE') echo'selected';?>>Electronics Engineering</option>
                                <option value="EE" <?php if(($_POST['branch']??'')==='EE') echo'selected';?>>Electrical Engineering</option>
                                <option value="ME" <?php if(($_POST['branch']??'')==='ME') echo'selected';?>>Mechanical Engineering</option>
                                <option value="CE" <?php if(($_POST['branch']??'')==='CE') echo'selected';?>>Civil Engineering</option>
                                <option value="Other" <?php if(($_POST['branch']??'')==='Other') echo'selected';?>>Other</option>
                            </select>
                        </div>
                        <div class="field-msg error" id="fBranchMsg"></div>
                    </div>
                    <div class="form-group">
                        <label>Semester <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <select name="semester" id="fSemester" required>
                                <option value="">Select Semester</option>
                                <?php for($i=1;$i<=8;$i++):?><option value="<?php echo $i;?>" <?php if(($_POST['semester']??'')===(string)$i) echo'selected';?>><?php echo $i;?><?php echo $i===1?'st':($i===2?'nd':($i===3?'rd':'th')); ?> Semester</option><?php endfor;?>
                            </select>
                        </div>
                        <div class="field-msg error" id="fSemMsg"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Password <span class="req">*</span></label>
                        <div class="input-wrapper">
                            <input type="password" id="fPass" name="password" placeholder="Min 6 characters" required>
                            <button type="button" class="toggle-pass" onclick="toggleP('fPass',this)" aria-label="Toggle password">&#128065;</button>
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
                            <input type="password" id="fPass2" name="confirm_password" placeholder="Re-enter password" required>
                            <button type="button" class="toggle-pass" onclick="toggleP('fPass2',this)" aria-label="Toggle password">&#128065;</button>
                        </div>
                        <div class="field-msg error" id="fPass2Msg"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Captcha <span class="req">*</span></label>
                    <div class="captcha-row">
                        <div class="captcha-display" id="captchaDisplay"><?php echo "$captcha_a $captcha_op $captcha_b = ?"; ?></div>
                        <button type="button" class="captcha-refresh" onclick="location.reload()" title="New captcha">&#8635;</button>
                        <div class="input-wrapper" style="flex:1"><input type="number" name="captcha" id="fCaptcha" placeholder="Answer" required></div>
                    </div>
                    <div class="field-msg error" id="fCaptchaMsg"></div>
                </div>

                <div class="terms-row">
                    <input type="checkbox" name="terms" id="fTerms" required>
                    <label for="fTerms">I agree to the <a href="#" onclick="event.preventDefault()">Terms and Conditions</a> and <a href="#" onclick="event.preventDefault()">Privacy Policy</a></label>
                </div>
                <div class="field-msg error" id="fTermsMsg" style="margin-top:-12px;margin-bottom:12px"></div>

                <button type="submit" class="submit-btn" id="submitBtn" disabled>
                    <span class="btn-text">Continue &#10148;</span>
                    <span class="spinner"></span>
                </button>
            </form>

            <div class="guest-link"><a href="student_dashboard.html">&#128100; Continue as Guest</a></div>
            <div class="auth-footer">Already have an account? <a href="login.php">Login here</a></div>

            <?php else: ?>

            <h2>Verify Your Email</h2>
            <p class="subtitle">Enter the 6-digit OTP sent to your email.</p>

            <?php if (!empty($errors)): ?><div class="alert alert-danger">&#9888; <?php echo implode('<br>',$errors); ?></div><?php endif; ?>
            <?php if (isset($_GET['sent'])): ?><div class="alert alert-success">&#10003; OTP sent successfully! Check your inbox.</div><?php endif; ?>
            <?php if (isset($_GET['resend'])): ?><div class="alert alert-success">&#10003; New OTP sent!</div><?php endif; ?>

            <div class="otp-box">
                <div class="otp-icon">&#128231;</div>
                <h3>Check Your Email</h3>
                <p>We sent a verification code to <span class="otp-email"><?php echo htmlspecialchars($_SESSION['reg_otp_email'] ?? ''); ?></span></p>
            </div>

            <form method="POST" action="register.php?step=2" novalidate id="otpForm">
                <input type="hidden" name="act" value="verify_otp">

                <div class="otp-inputs" id="otpInputs">
                    <input type="text" maxlength="1" inputmode="numeric" class="otp-digit" data-idx="0" autofocus>
                    <input type="text" maxlength="1" inputmode="numeric" class="otp-digit" data-idx="1">
                    <input type="text" maxlength="1" inputmode="numeric" class="otp-digit" data-idx="2">
                    <input type="text" maxlength="1" inputmode="numeric" class="otp-digit" data-idx="3">
                    <input type="text" maxlength="1" inputmode="numeric" class="otp-digit" data-idx="4">
                    <input type="text" maxlength="1" inputmode="numeric" class="otp-digit" data-idx="5">
                </div>
                <input type="hidden" name="otp" id="otpHidden">

                <div class="form-row">
                    <div class="form-group">
                        <label>State <span class="opt">(optional)</span></label>
                        <div class="input-wrapper">
                            <select name="state">
                                <option value="">Select State</option>
                                <?php foreach($states as $s):?><option value="<?php echo $s;?>"><?php echo $s;?></option><?php endforeach;?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>City <span class="opt">(optional)</span></label>
                        <div class="input-wrapper"><input type="text" name="city" placeholder="Your city"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Profile Photo <span class="opt">(optional)</span></label>
                    <div class="input-wrapper"><input type="file" name="profile_photo" accept="image/*" style="padding:10px;font-size:13px"></div>
                </div>

                <button type="submit" class="submit-btn" id="verifyBtn">
                    <span class="btn-text">Verify &amp; Create Account &#10003;</span>
                    <span class="spinner"></span>
                </button>
            </form>

            <div class="resend-row" style="margin-top:16px">Didn't receive the code? <a onclick="document.getElementById('resendForm').submit()">Resend OTP</a></div>
            <form method="POST" action="register.php?step=2" id="resendForm" style="display:none"><input type="hidden" name="act" value="resend_otp"></form>

            <div class="auth-footer"><a href="register.php">&#8592; Back to Step 1</a></div>

            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="footer"><p>&copy; 2026 EngiHub. All rights reserved.</p></footer>

<script>
document.getElementById('menuToggle').addEventListener('click',function(){document.getElementById('navLinks').classList.toggle('open')});
function toggleP(id,btn){var i=document.getElementById(id);i.type=i.type==='password'?'text':'password';btn.innerHTML=i.type==='password'?'&#128065;':'&#128064;'}

var isDark=localStorage.getItem('engihub-dark')==='1';
if(isDark)document.documentElement.classList.add('dark');
document.getElementById('darkToggle').addEventListener('click',function(){
    document.documentElement.classList.toggle('dark');
    isDark=document.documentElement.classList.contains('dark');
    localStorage.setItem('engihub-dark',isDark?'1':'0');
    this.innerHTML=isDark?'&#9728;':'&#127769;';
});
if(isDark)document.getElementById('darkToggle').innerHTML='&#9728;';

var photoInput=document.getElementById('photoInput'),photoPreview=document.getElementById('photoPreview'),photoRemove=document.getElementById('photoRemove');
if(photoInput){photoInput.addEventListener('change',function(){
    if(this.files&&this.files[0]){
        var r=new FileReader();r.onload=function(e){photoPreview.innerHTML='<img src="'+e.target.result+'" alt="Photo">'};
        r.readAsDataURL(this.files[0]);photoRemove.style.display='block';
    }
});photoRemove.addEventListener('click',function(){photoInput.value='';photoPreview.innerHTML='&#128100;';this.style.display='none'})}

<?php if($step===1):?>
var fName=document.getElementById('fName'),fEmail=document.getElementById('fEmail'),fMobile=document.getElementById('fMobile'),
    fBranch=document.getElementById('fBranch'),fSemester=document.getElementById('fSemester'),
    fPass=document.getElementById('fPass'),fPass2=document.getElementById('fPass2'),fTerms=document.getElementById('fTerms'),
    fCaptcha=document.getElementById('fCaptcha'),submitBtn=document.getElementById('submitBtn');

function showMsg(id,msg,type){var el=document.getElementById(id);el.textContent=msg;el.className='field-msg show '+type}
function hideMsg(id){document.getElementById(id).className='field-msg'}
function checkValid(){
    submitBtn.disabled=!(fName.value.trim().length>=2&&/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(fEmail.value.trim())&&/^[6-9]\d{9}$/.test(fMobile.value.trim())&&fBranch.value!==''&&fSemester.value!==''&&fPass.value.length>=6&&fPass2.value===fPass.value&&fTerms.checked&&fCaptcha.value!=='');
}
fName.addEventListener('input',function(){var v=this.value.trim();if(v.length===0){hideMsg('fNameMsg');this.classList.remove('valid','invalid')}else if(v.length<2){showMsg('fNameMsg','Min 2 characters','error');this.classList.add('invalid');this.classList.remove('valid')}else{hideMsg('fNameMsg');this.classList.add('valid');this.classList.remove('invalid')}checkValid()});
fEmail.addEventListener('input',function(){var v=this.value.trim();if(v.length===0){hideMsg('fEmailMsg');this.classList.remove('valid','invalid')}else if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)){showMsg('fEmailMsg','Enter a valid email','error');this.classList.add('invalid');this.classList.remove('valid')}else{hideMsg('fEmailMsg');this.classList.add('valid');this.classList.remove('invalid')}checkValid()});
fMobile.addEventListener('input',function(){this.value=this.value.replace(/[^0-9]/g,'');var v=this.value;if(v.length===0){hideMsg('fMobileMsg');this.classList.remove('valid','invalid')}else if(v.length<10){showMsg('fMobileMsg','Must be 10 digits','error');this.classList.add('invalid');this.classList.remove('valid')}else if(!/^[6-9]/.test(v)){showMsg('fMobileMsg','Must start with 6-9','error');this.classList.add('invalid');this.classList.remove('valid')}else{hideMsg('fMobileMsg');this.classList.add('valid');this.classList.remove('invalid')}checkValid()});
fBranch.addEventListener('change',function(){if(this.value==='')showMsg('fBranchMsg','Select a branch','error');else hideMsg('fBranchMsg');checkValid()});
fSemester.addEventListener('change',function(){if(this.value==='')showMsg('fSemMsg','Select a semester','error');else hideMsg('fSemMsg');checkValid()});
fPass.addEventListener('input',function(){
    var v=this.value,strength=document.getElementById('passStrength'),fill=document.getElementById('strengthFill'),text=document.getElementById('strengthText');
    if(v.length===0){strength.style.display='none';hideMsg('fPassMsg');this.classList.remove('valid','invalid');checkValid();return}
    strength.style.display='block';var s=0;if(v.length>=6)s++;if(v.length>=10)s++;if(/[A-Z]/.test(v)&&/[a-z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
    fill.className='strength-fill';text.className='strength-text';
    if(s<=2){fill.classList.add('weak');text.textContent='Weak';text.style.color='var(--red)'}
    else if(s<=3){fill.classList.add('fair');text.textContent='Fair';text.style.color='var(--yellow)'}
    else{fill.classList.add('strong');text.textContent='Strong';text.style.color='var(--green)'}
    if(v.length<6){showMsg('fPassMsg','Min 6 characters','error');this.classList.add('invalid');this.classList.remove('valid')}else{hideMsg('fPassMsg');this.classList.add('valid');this.classList.remove('invalid')}
    if(fPass2.value.length>0)validatePass2();checkValid();
});
function validatePass2(){var v=fPass2.value;if(v.length===0){hideMsg('fPass2Msg');fPass2.classList.remove('valid','invalid')}else if(v!==fPass.value){showMsg('fPass2Msg','Passwords do not match','error');fPass2.classList.add('invalid');fPass2.classList.remove('valid')}else{hideMsg('fPass2Msg');fPass2.classList.add('valid');fPass2.classList.remove('invalid')}}
fPass2.addEventListener('input',function(){validatePass2();checkValid()});
fTerms.addEventListener('change',function(){if(!this.checked)showMsg('fTermsMsg','You must agree to the terms','error');else hideMsg('fTermsMsg');checkValid()});
fCaptcha.addEventListener('input',function(){checkValid()});
document.getElementById('regForm').addEventListener('submit',function(e){if(submitBtn.disabled){e.preventDefault();return}document.getElementById('submitBtn').classList.add('loading');document.getElementById('submitBtn').disabled=true});
<?php endif; ?>

<?php if($step===2):?>
document.querySelectorAll('.otp-digit').forEach(function(input,idx,inputArr){
    input.addEventListener('input',function(){
        this.value=this.value.replace(/[^0-9]/g,'');
        if(this.value.length===1&&idx<inputArr.length-1)inputArr[idx+1].focus();
        var otp='';inputArr.forEach(function(i){otp+=i.value});document.getElementById('otpHidden').value=otp;
    });
    input.addEventListener('keydown',function(e){
        if(e.key==='Backspace'&&this.value===''&&idx>0){inputArr[idx-1].focus()}
    });
    input.addEventListener('paste',function(e){
        e.preventDefault();var d=(e.clipboardData||window.clipboardData).getData('text').replace(/[^0-9]/g,'');
        for(var i=0;i<Math.min(d.length,inputArr.length);i++){inputArr[i].value=d[i]}
        if(d.length>0)inputArr[Math.min(d.length,inputArr.length)-1].focus();
        var otp='';inputArr.forEach(function(i){otp+=i.value});document.getElementById('otpHidden').value=otp;
    });
});
document.getElementById('otpForm').addEventListener('submit',function(e){
    var otp='';document.querySelectorAll('.otp-digit').forEach(function(i){otp+=i.value});
    document.getElementById('otpHidden').value=otp;
    if(otp.length!==6){e.preventDefault();alert('Please enter the complete 6-digit OTP');return}
    document.getElementById('verifyBtn').classList.add('loading');document.getElementById('verifyBtn').disabled=true;
});
<?php endif; ?>
</script>
</body></html>

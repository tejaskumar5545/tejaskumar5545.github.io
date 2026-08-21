<?php
require_once 'db.php';
requireLogin();

$studentId = $_SESSION['student_id'];
$student = null;
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) { session_destroy(); header("Location: login.php"); exit; }

$profilePhoto = !empty($student['profile_photo']) ? 'uploads/profiles/' . htmlspecialchars($student['profile_photo']) : '';
$initials = strtoupper(substr($student['full_name'], 0, 1));

$unreadNotifs = 0;
$r = $conn->query("SELECT COUNT(*) as c FROM user_notifications WHERE student_id = $studentId AND is_read = 0");
if ($r) $unreadNotifs = $r->fetch_assoc()['c'];
$recentNotifs = [];
$r = $conn->query("SELECT * FROM user_notifications WHERE student_id = $studentId ORDER BY created_at DESC LIMIT 8");
if ($r) { while ($row = $r->fetch_assoc()) $recentNotifs[] = $row; }

$profileFields = ['profile_photo', 'dob', 'mobile', 'city', 'student_id'];
$filled = 0;
foreach ($profileFields as $f) { if (!empty($student[$f])) $filled++; }
$profileComplete = round(($filled / count($profileFields)) * 100);

$sessions = [];
$r = $conn->query("SELECT * FROM user_activity WHERE student_id = $studentId ORDER BY created_at DESC LIMIT 5");
if ($r) { while ($row = $r->fetch_assoc()) $sessions[] = $row; }

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2563eb">
    <title>My Profile - EngiHub</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex}
        a{text-decoration:none;color:inherit}
        button{cursor:pointer;font-family:inherit}
        input,select,textarea{font-family:inherit}

        .sidebar{width:260px;background:white;height:100vh;position:fixed;top:0;left:0;z-index:200;display:flex;flex-direction:column;border-right:1px solid #f3f4f6;transition:transform .3s ease}
        .sidebar-header{padding:20px 24px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;gap:12px}
        .sidebar-header .logo{font-size:24px;font-weight:bold;color:#2563eb}.sidebar-header .logo span{color:#111827}
        .sidebar-header .version{font-size:10px;background:#eff6ff;color:#2563eb;padding:2px 8px;border-radius:20px;font-weight:600;margin-left:4px}
        .sidebar-nav{flex:1;overflow-y:auto;padding:12px 0}
        .sidebar-nav::-webkit-scrollbar{width:4px}.sidebar-nav::-webkit-scrollbar-thumb{background:#e5e7eb;border-radius:4px}
        .nav-section-title{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.8px;padding:16px 24px 6px}
        .sidebar-nav a,.sidebar-nav button{display:flex;align-items:center;gap:12px;padding:11px 24px;font-size:14px;color:#4b5563;font-weight:500;transition:all .15s;border:none;background:none;width:100%;text-align:left}
        .sidebar-nav a:hover,.sidebar-nav button:hover{background:#f5f7fb;color:#2563eb}
        .sidebar-nav a.active{background:#eff6ff;color:#2563eb;font-weight:600;border-right:3px solid #2563eb}
        .sidebar-nav .nav-icon{width:20px;text-align:center;font-size:16px;flex-shrink:0}
        .sidebar-nav .nav-badge{margin-left:auto;background:#ef4444;color:white;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;min-width:18px;text-align:center}
        .sidebar-footer{padding:16px 24px;border-top:1px solid #f3f4f6}
        .sidebar-footer .logout-btn{display:flex;align-items:center;gap:10px;padding:10px 0;font-size:14px;color:#ef4444;font-weight:600;width:100%;border:none;background:none;transition:color .2s}
        .sidebar-footer .logout-btn:hover{color:#dc2626}

        .main-content{flex:1;margin-left:260px;min-height:100vh;display:flex;flex-direction:column}
        .top-navbar{height:64px;background:white;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;padding:0 32px;position:sticky;top:0;z-index:150}
        .top-nav-left{display:flex;align-items:center;gap:16px}
        .sidebar-toggle{display:none;background:none;border:none;font-size:22px;color:#4b5563;padding:6px;border-radius:8px}
        .sidebar-toggle:hover{background:#f3f4f6}
        .search-box{position:relative}
        .search-box input{width:320px;padding:9px 14px 9px 38px;border:1px solid #e5e7eb;border-radius:10px;font-size:13px;color:#111827;outline:none;transition:border-color .2s,box-shadow .2s;background:#f9fafb}
        .search-box input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.08);background:white}
        .search-box input::placeholder{color:#9ca3af}
        .search-box .search-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:14px;pointer-events:none}
        .top-nav-right{display:flex;align-items:center;gap:8px}
        .top-nav-btn{background:none;border:none;width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#6b7280;position:relative;transition:all .15s}
        .top-nav-btn:hover{background:#f3f4f6;color:#2563eb}
        .top-nav-btn .badge{position:absolute;top:6px;right:6px;background:#ef4444;color:white;font-size:9px;font-weight:700;padding:1px 5px;border-radius:20px;min-width:16px;text-align:center;line-height:14px}
        .profile-chip{display:flex;align-items:center;gap:10px;padding:5px 14px 5px 5px;border-radius:50px;cursor:pointer;transition:background .15s;margin-left:4px}
        .profile-chip:hover{background:#f3f4f6}
        .profile-avatar{width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid #e5e7eb}
        .profile-avatar-placeholder{width:34px;height:34px;border-radius:50%;background:#e0e7ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;border:2px solid #e5e7eb;flex-shrink:0}
        .profile-name{font-size:13px;font-weight:600;color:#374151}
        .profile-role{font-size:11px;color:#9ca3af}
        .profile-dropdown{position:absolute;top:52px;right:24px;background:white;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.12);border:1px solid #f3f4f6;min-width:200px;padding:8px;display:none;animation:dropIn .2s ease}
        .profile-dropdown.show{display:block}
        @keyframes dropIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
        .profile-dropdown a,.profile-dropdown button{display:flex;align-items:center;gap:10px;padding:10px 14px;font-size:13px;color:#374151;border-radius:8px;border:none;background:none;width:100%;text-align:left;transition:background .1s}
        .profile-dropdown a:hover,.profile-dropdown button:hover{background:#f5f7fb}
        .profile-dropdown .dd-divider{height:1px;background:#f3f4f6;margin:4px 8px}
        .profile-dropdown .dd-label{font-size:11px;color:#9ca3af;padding:8px 14px 4px;text-transform:uppercase;letter-spacing:.5px;font-weight:600}

        .notification-panel{position:absolute;top:52px;right:72px;background:white;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.12);border:1px solid #f3f4f6;width:380px;max-height:480px;display:none;animation:dropIn .2s ease;z-index:160}
        .notification-panel.show{display:block}
        .notif-header{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #f3f4f6}
        .notif-header h3{font-size:15px;font-weight:700}
        .notif-header .mark-read{font-size:12px;color:#2563eb;font-weight:600;cursor:pointer;background:none;border:none}
        .notif-header .mark-read:hover{text-decoration:underline}
        .notif-list{max-height:380px;overflow-y:auto}
        .notif-item{display:flex;gap:12px;padding:14px 20px;border-bottom:1px solid #f9fafb;transition:background .1s;cursor:pointer}
        .notif-item:hover{background:#f9fafb}
        .notif-item.unread{background:#f0f5ff}
        .notif-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
        .notif-icon.blue{background:#eff6ff;color:#2563eb}.notif-icon.green{background:#f0fdf4;color:#16a34a}.notif-icon.amber{background:#fffbeb;color:#f59e0b}
        .notif-content{flex:1;min-width:0}
        .notif-title{font-size:13px;font-weight:600;color:#111827;margin-bottom:2px}
        .notif-msg{font-size:12px;color:#6b7280;line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .notif-time{font-size:11px;color:#9ca3af;margin-top:3px}
        .notif-empty{text-align:center;padding:40px 20px;color:#9ca3af;font-size:13px}

        .page-content{padding:28px 32px;flex:1}

        .profile-hero{background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 50%,#3b82f6 100%);border-radius:16px;padding:36px;display:flex;align-items:center;gap:28px;margin-bottom:28px;position:relative;overflow:hidden}
        .profile-hero::before{content:'';position:absolute;right:-30px;top:-30px;width:180px;height:180px;background:rgba(255,255,255,.06);border-radius:50%}
        .profile-hero::after{content:'';position:absolute;right:80px;bottom:-50px;width:140px;height:140px;background:rgba(255,255,255,.04);border-radius:50%}
        .hero-avatar-wrap{position:relative;flex-shrink:0}
        .hero-avatar{width:100px;height:100px;border-radius:50%;object-fit:cover;border:4px solid rgba(255,255,255,.3);background:#e0e7ff}
        .hero-avatar-placeholder{width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,.15);border:4px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:800;color:white}
        .hero-photo-btn{position:absolute;bottom:0;right:0;width:32px;height:32px;background:white;border-radius:50%;border:2px solid #e5e7eb;display:flex;align-items:center;justify-content:center;font-size:14px;cursor:pointer;transition:all .15s;color:#2563eb}
        .hero-photo-btn:hover{background:#2563eb;color:white;border-color:#2563eb}
        .hero-info{flex:1;position:relative;z-index:1;color:white}
        .hero-info h1{font-size:24px;font-weight:800;margin-bottom:4px}
        .hero-info .hero-email{font-size:14px;opacity:.85;margin-bottom:10px}
        .hero-meta{display:flex;gap:16px;flex-wrap:wrap}
        .hero-meta-item{display:flex;align-items:center;gap:6px;font-size:12px;opacity:.9;background:rgba(255,255,255,.12);padding:5px 12px;border-radius:20px}
        .hero-actions{position:relative;z-index:1;display:flex;flex-direction:column;gap:8px}
        .hero-btn{padding:10px 20px;border-radius:10px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .2s;text-align:center}
        .hero-btn-primary{background:white;color:#2563eb}.hero-btn-primary:hover{background:#f0f5ff;transform:translateY(-1px)}
        .hero-btn-outline{background:transparent;color:white;border:2px solid rgba(255,255,255,.3)}.hero-btn-outline:hover{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.5)}

        .profile-completion-bar{background:white;border-radius:14px;padding:22px 28px;box-shadow:0 1px 6px rgba(0,0,0,.04);border:1px solid #f3f4f6;margin-bottom:28px;display:flex;align-items:center;gap:20px}
        .pcb-icon{width:44px;height:44px;border-radius:12px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
        .pcb-info{flex:1}
        .pcb-info h4{font-size:14px;font-weight:700;margin-bottom:4px}
        .pcb-info p{font-size:12px;color:#6b7280}
        .pcb-bar-wrap{flex:1;max-width:300px}
        .pcb-bar-outer{background:#e5e7eb;border-radius:8px;height:8px;overflow:hidden;margin-bottom:4px}
        .pcb-bar-inner{height:100%;border-radius:8px;background:linear-gradient(90deg,#2563eb,#3b82f6);transition:width .8s ease}
        .pcb-pct{font-size:12px;color:#6b7280;font-weight:600}
        .pcb-btn{padding:10px 20px;background:#2563eb;color:white;border:none;border-radius:10px;font-size:13px;font-weight:600;transition:all .15s;white-space:nowrap}
        .pcb-btn:hover{background:#1d4ed8}

        .profile-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px}

        .card-panel{background:white;border-radius:14px;box-shadow:0 1px 6px rgba(0,0,0,.04);border:1px solid #f3f4f6;overflow:hidden}
        .panel-header{padding:20px 24px;border-bottom:1px solid #f3f4f6}
        .panel-header h3{font-size:16px;font-weight:700;color:#111827}
        .panel-header p{font-size:12px;color:#6b7280;margin-top:2px}
        .panel-body{padding:24px}

        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .form-group{margin-bottom:16px}
        .form-group.full{grid-column:1 / -1}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
        .form-group .optional{font-weight:400;color:#9ca3af;font-size:12px}
        .input-wrapper{position:relative}
        .input-wrapper input,.input-wrapper select{width:100%;padding:11px 14px;border:2px solid #e5e7eb;border-radius:10px;font-size:14px;color:#111827;outline:none;transition:border-color .2s,box-shadow .2s;background:white}
        .input-wrapper input:focus,.input-wrapper select:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.08)}
        .input-wrapper input:read-only{background:#f9fafb;color:#6b7280}
        .input-wrapper .input-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:14px;pointer-events:none}
        .input-wrapper .input-icon+input{padding-left:36px}
        .toggle-vis{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;font-size:16px;color:#9ca3af;padding:4px}
        .toggle-vis:hover{color:#374151}

        .form-actions{display:flex;gap:12px;margin-top:8px}
        .btn{padding:11px 24px;border-radius:10px;font-size:14px;font-weight:600;border:none;transition:all .2s;display:inline-flex;align-items:center;gap:8px}
        .btn-primary{background:#2563eb;color:white}.btn-primary:hover{background:#1d4ed8;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.25)}
        .btn-primary:disabled{opacity:.5;cursor:not-allowed;transform:none;box-shadow:none}
        .btn-secondary{background:#f3f4f6;color:#374151}.btn-secondary:hover{background:#e5e7eb}
        .btn-danger{background:#fef2f2;color:#dc2626;border:2px solid #fecaca}.btn-danger:hover{background:#dc2626;color:white;border-color:#dc2626}
        .btn-outline{background:white;color:#ef4444;border:2px solid #ef4444}.btn-outline:hover{background:#ef4444;color:white}
        .btn .spinner{display:none;width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:white;border-radius:50%;animation:spin .6s linear infinite}
        .btn.loading .spinner{display:block}.btn.loading .btn-text{display:none}
        @keyframes spin{to{transform:rotate(360deg)}}

        .password-strength{margin-top:6px}
        .strength-bar{height:4px;background:#e5e7eb;border-radius:4px;overflow:hidden;margin-bottom:3px}
        .strength-fill{height:100%;border-radius:4px;transition:all .4s;width:0}
        .strength-fill.weak{width:33%;background:#ef4444}.strength-fill.fair{width:66%;background:#f59e0b}.strength-fill.strong{width:100%;background:#10b981}
        .strength-text{font-size:11px;font-weight:600}
        .field-msg{font-size:12px;margin-top:4px;display:none;align-items:center;gap:4px}
        .field-msg.show{display:flex}
        .field-msg.error{color:#ef4444}.field-msg.success{color:#10b981}

        .alert-toast{position:fixed;top:24px;right:24px;padding:14px 20px;border-radius:12px;font-size:13px;font-weight:600;z-index:9999;display:flex;align-items:center;gap:10px;animation:slideIn .3s ease;box-shadow:0 8px 24px rgba(0,0,0,.12);max-width:400px}
        .alert-toast.success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
        .alert-toast.error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
        @keyframes slideIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}}

        .avatar-upload-area{display:flex;align-items:center;gap:20px;padding:20px;background:#f9fafb;border-radius:12px;border:2px dashed #e5e7eb;margin-bottom:20px}
        .avatar-preview{width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #e5e7eb;flex-shrink:0}
        .avatar-preview-placeholder{width:80px;height:80px;border-radius:50%;background:#e0e7ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:700;border:3px solid #e5e7eb;flex-shrink:0}
        .avatar-upload-info h4{font-size:14px;color:#374151;margin-bottom:4px}
        .avatar-upload-info p{font-size:12px;color:#9ca3af;margin-bottom:8px}
        .avatar-upload-btns{display:flex;gap:8px;flex-wrap:wrap}
        .avatar-btn{padding:7px 14px;background:white;border:2px solid #2563eb;border-radius:8px;color:#2563eb;font-size:12px;font-weight:600;transition:all .15s}
        .avatar-btn:hover{background:#2563eb;color:white}
        .avatar-btn.remove{border-color:#fecaca;color:#dc2626}.avatar-btn.remove:hover{background:#dc2626;color:white;border-color:#dc2626}

        .info-row{display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid #f9fafb}
        .info-row:last-child{border-bottom:none}
        .info-row .info-label{font-size:13px;color:#6b7280}
        .info-row .info-value{font-size:13px;font-weight:600;color:#111827}
        .info-row .info-value.verified{color:#10b981}
        .info-row .info-value.active{color:#10b981}
        .info-row .info-value.pending{color:#f59e0b}

        .danger-zone{border:2px solid #fecaca;border-radius:14px;padding:24px;background:#fef2f2}
        .danger-zone h3{font-size:16px;font-weight:700;color:#dc2626;margin-bottom:6px}
        .danger-zone p{font-size:13px;color:#6b7280;margin-bottom:16px;line-height:1.5}
        .danger-actions{display:flex;gap:12px;flex-wrap:wrap}

        .modal-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:1000;display:none;align-items:center;justify-content:center;padding:20px}
        .modal-overlay.show{display:flex}
        .modal{background:white;border-radius:16px;padding:32px;max-width:440px;width:100%;animation:dropIn .2s ease}
        .modal h3{font-size:18px;font-weight:700;margin-bottom:8px}
        .modal p{font-size:13px;color:#6b7280;margin-bottom:20px;line-height:1.5}
        .modal .form-group{margin-bottom:16px}
        .modal .form-actions{justify-content:flex-end}

        .mobile-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.4);z-index:190}
        .mobile-overlay.show{display:block}
        .footer-dashboard{background:#111827;color:white;padding:30px 32px;text-align:center;font-size:13px;color:#6b7280}
        .footer-dashboard a{color:#2563eb}.footer-dashboard a:hover{text-decoration:underline}

        @media(max-width:1024px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0)}
            .mobile-overlay.show{display:block}
            .main-content{margin-left:0}
            .sidebar-toggle{display:flex}
            .profile-grid{grid-template-columns:1fr}
            .profile-hero{flex-direction:column;text-align:center;padding:28px 20px}
            .hero-meta{justify-content:center}
            .hero-actions{flex-direction:row;justify-content:center}
        }
        @media(max-width:768px){
            .top-navbar{padding:0 16px;height:56px}
            .search-box input{width:160px}
            .page-content{padding:20px 16px}
            .profile-hero{padding:24px 16px}
            .hero-avatar,.hero-avatar-placeholder{width:80px;height:80px;font-size:28px}
            .hero-info h1{font-size:20px}
            .hero-meta-item{font-size:11px;padding:4px 10px}
            .form-grid{grid-template-columns:1fr}
            .avatar-upload-area{flex-direction:column;text-align:center}
            .profile-completion-bar{flex-direction:column;text-align:center}
            .pcb-bar-wrap{max-width:100%;width:100%}
            .notification-panel{width:calc(100vw - 32px);right:-40px}
            .profile-chip .profile-name,.profile-chip .profile-role{display:none}
            .profile-chip{padding:4px}
            .profile-avatar,.profile-avatar-placeholder{width:32px;height:32px}
            .form-actions{flex-direction:column}
            .form-actions .btn{width:100%;justify-content:center}
        }
        @media(max-width:480px){
            .search-box{display:none}
            .danger-actions{flex-direction:column}
        }
        @media(prefers-reduced-motion:reduce){*{animation-duration:.01ms!important;transition-duration:.01ms!important}}
    </style>
</head>
<body>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="index.html" class="logo">Engi<span>Hub</span></a>
        <span class="version">Student</span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-title">Main</div>
        <a href="student-dashboard.php"><span class="nav-icon">&#127968;</span>Dashboard</a>
        <a href="profile.php" class="active"><span class="nav-icon">&#128100;</span>My Profile</a>
        <a href="notes.html"><span class="nav-icon">&#128218;</span>Notes</a>
        <a href="syllabus.html"><span class="nav-icon">&#128209;</span>Syllabus</a>
        <a href="pyq.html"><span class="nav-icon">&#128196;</span>Previous Year Questions</a>
        <a href="practical.html"><span class="nav-icon">&#128300;</span>Practical</a>
        <div class="nav-section-title">Learn & Build</div>
        <a href="coding.html"><span class="nav-icon">&#128187;</span>Coding</a>
        <a href="projects.html"><span class="nav-icon">&#128640;</span>Projects</a>
        <a href="placement.html"><span class="nav-icon">&#127919;</span>Placement</a>
        <div class="nav-section-title">Personal</div>
        <a href="student-dashboard.php#saved"><span class="nav-icon">&#128278;</span>Saved Resources</a>
        <a href="student-dashboard.php#downloads"><span class="nav-icon">&#11015;</span>Download History</a>
        <a href="student-dashboard.php#notifications"><span class="nav-icon">&#128276;</span>Notifications<?php if ($unreadNotifs > 0): ?><span class="nav-badge"><?php echo $unreadNotifs; ?></span><?php endif; ?></a>
        <div class="nav-section-title">Info</div>
        <a href="notices.html"><span class="nav-icon">&#128227;</span>Notices</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn">&#10148; Logout</a>
    </div>
</aside>

<div class="mobile-overlay" id="mobileOverlay" onclick="closeSidebar()"></div>

<main class="main-content">
    <header class="top-navbar">
        <div class="top-nav-left">
            <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">&#9776;</button>
            <div class="search-box">
                <span class="search-icon">&#128269;</span>
                <input type="text" placeholder="Search notes, PYQ, subjects..." aria-label="Search">
            </div>
        </div>
        <div class="top-nav-right">
            <button class="top-nav-btn" onclick="toggleNotifications()" id="notifBtn">
                &#128276;
                <?php if ($unreadNotifs > 0): ?><span class="badge"><?php echo $unreadNotifs; ?></span><?php endif; ?>
            </button>
            <div class="profile-chip" onclick="toggleProfileDropdown()" id="profileChip">
                <?php if ($profilePhoto): ?>
                    <img src="<?php echo $profilePhoto; ?>" alt="Profile" class="profile-avatar" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="profile-avatar-placeholder" style="display:none"><?php echo $initials; ?></div>
                <?php else: ?>
                    <div class="profile-avatar-placeholder"><?php echo $initials; ?></div>
                <?php endif; ?>
                <div>
                    <div class="profile-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                    <div class="profile-role"><?php echo htmlspecialchars($student['branch'] ?? 'Student'); ?></div>
                </div>
            </div>
        </div>
        <div class="notification-panel" id="notifPanel">
            <div class="notif-header"><h3>Notifications</h3><button class="mark-read" onclick="markAllRead()">Mark all read</button></div>
            <div class="notif-list">
                <?php if (empty($recentNotifs)): ?>
                    <div class="notif-empty"><span style="font-size:36px;display:block;margin-bottom:8px">&#128276;</span>No notifications yet.</div>
                <?php else: foreach ($recentNotifs as $notif): ?>
                    <div class="notif-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                        <div class="notif-icon <?php echo $notif['icon'] === 'upload' ? 'green' : 'blue'; ?>"><?php echo $notif['icon'] === 'upload' ? '&#128194;' : '&#128276;'; ?></div>
                        <div class="notif-content"><div class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></div><div class="notif-msg"><?php echo htmlspecialchars($notif['message'] ?? ''); ?></div><div class="notif-time"><?php echo date('M j, g:i A', strtotime($notif['created_at'])); ?></div></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
        <div class="profile-dropdown" id="profileDropdown">
            <div class="dd-label">Account</div>
            <a href="profile.php">&#128100; My Profile</a>
            <a href="student-dashboard.php#saved">&#128278; Saved Resources</a>
            <div class="dd-divider"></div>
            <a href="logout.php" style="color:#ef4444">&#10148; Logout</a>
        </div>
    </header>

    <div class="page-content">
        <div class="profile-hero">
            <div class="hero-avatar-wrap" id="heroAvatarWrap" onclick="document.getElementById('heroPhotoInput').click()">
                <?php if ($profilePhoto): ?>
                    <img src="<?php echo $profilePhoto; ?>" alt="Profile" class="hero-avatar" id="heroAvatarImg" onerror="this.style.display='none';document.getElementById('heroAvatarPH').style.display='flex'">
                    <div class="hero-avatar-placeholder" id="heroAvatarPH" style="display:none"><?php echo $initials; ?></div>
                <?php else: ?>
                    <div class="hero-avatar-placeholder" id="heroAvatarPH"><?php echo $initials; ?></div>
                <?php endif; ?>
                <div class="hero-photo-btn">&#128247;</div>
                <input type="file" id="heroPhotoInput" accept="image/jpeg,image/png,image/gif" style="display:none" onchange="handlePhotoUpload(this)">
            </div>
            <div class="hero-info">
                <h1><?php echo htmlspecialchars($student['full_name']); ?></h1>
                <div class="hero-email"><?php echo htmlspecialchars($student['email']); ?></div>
                <div class="hero-meta">
                    <?php if (!empty($student['college_name'])): ?><div class="hero-meta-item">&#127979; <?php echo htmlspecialchars($student['college_name']); ?></div><?php endif; ?>
                    <?php if (!empty($student['branch'])): ?><div class="hero-meta-item">&#128218; <?php echo htmlspecialchars($student['branch']); ?></div><?php endif; ?>
                    <?php if (!empty($student['semester'])): ?><div class="hero-meta-item">&#128197; Sem <?php echo htmlspecialchars($student['semester']); ?></div><?php endif; ?>
                </div>
            </div>
            <div class="hero-actions">
                <button class="hero-btn hero-btn-primary" onclick="document.getElementById('profileForm').scrollIntoView({behavior:'smooth'})">&#9998; Edit Profile</button>
                <label class="hero-btn hero-btn-outline" style="cursor:pointer" for="heroPhotoInput2">&#128247; Change Photo
                    <input type="file" id="heroPhotoInput2" accept="image/jpeg,image/png,image/gif" style="display:none" onchange="handlePhotoUpload(this)">
                </label>
            </div>
        </div>

        <div class="profile-completion-bar" id="completionBar">
            <div class="pcb-icon">&#128200;</div>
            <div class="pcb-info">
                <h4>Profile Completion</h4>
                <p>Complete your profile to unlock personalized features and recommendations.</p>
            </div>
            <div class="pcb-bar-wrap">
                <div class="pcb-bar-outer"><div class="pcb-bar-inner" id="pcbBar" style="width:<?php echo $profileComplete; ?>%"></div></div>
                <div class="pcb-pct" id="pcbPct"><?php echo $profileComplete; ?>% complete</div>
            </div>
            <button class="pcb-btn" onclick="document.getElementById('profileForm').scrollIntoView({behavior:'smooth'})">Complete Profile</button>
        </div>

        <form id="profileForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <div class="profile-grid">
            <div class="card-panel">
                <div class="panel-header">
                    <h3>&#128100; Personal Information</h3>
                    <p>Manage your personal details and contact information</p>
                </div>
                <div class="panel-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <div class="input-wrapper"><input type="text" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required></div>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <div class="input-wrapper"><input type="email" value="<?php echo htmlspecialchars($student['email']); ?>" readonly></div>
                        </div>
                        <div class="form-group">
                            <label>Mobile Number <span class="optional">(optional)</span></label>
                            <div class="input-wrapper"><input type="tel" name="mobile" value="<?php echo htmlspecialchars($student['mobile'] ?? ''); ?>" maxlength="15" placeholder="+91 0000000000"></div>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth <span class="optional">(optional)</span></label>
                            <div class="input-wrapper"><input type="date" name="dob" value="<?php echo !empty($student['dob']) ? htmlspecialchars($student['dob']) : ''; ?>"></div>
                        </div>
                        <div class="form-group">
                            <label>State <span class="optional">(optional)</span></label>
                            <div class="input-wrapper">
                                <select name="state">
                                    <option value="">Select State</option>
                                    <?php
                                    $states = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Delhi','Jammu and Kashmir','Ladakh','Chandigarh','Puducherry','Andaman and Nicobar Islands','Dadra and Nagar Haveli and Daman and Diu','Lakshadweep'];
                                    foreach ($states as $st): ?>
                                        <option value="<?php echo $st; ?>" <?php echo ($student['state'] ?? '') === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>City <span class="optional">(optional)</span></label>
                            <div class="input-wrapper"><input type="text" name="city" value="<?php echo htmlspecialchars($student['city'] ?? ''); ?>" placeholder="Your city"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-panel">
                <div class="panel-header">
                    <h3>&#127891; Academic Information</h3>
                    <p>Your college and academic details</p>
                </div>
                <div class="panel-body">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label>College Name</label>
                            <div class="input-wrapper"><input type="text" name="college_name" value="<?php echo htmlspecialchars($student['college_name'] ?? ''); ?>" placeholder="Your college name"></div>
                        </div>
                        <div class="form-group">
                            <label>Student ID / Roll Number</label>
                            <div class="input-wrapper"><input type="text" name="student_id_field" value="<?php echo htmlspecialchars($student['student_id'] ?? ''); ?>" placeholder="e.g. 2024001"></div>
                        </div>
                        <div class="form-group">
                            <label>Branch / Department</label>
                            <div class="input-wrapper">
                                <select name="branch">
                                    <option value="">Select Branch</option>
                                    <?php
                                    $branches = ['Computer Science','Information Technology','Electronics','Electrical','Mechanical','Civil','Chemical','Biotechnology','Textile','Automobile','Other'];
                                    foreach ($branches as $br): ?>
                                        <option value="<?php echo $br; ?>" <?php echo ($student['branch'] ?? '') === $br ? 'selected' : ''; ?>><?php echo $br; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Current Semester</label>
                            <div class="input-wrapper">
                                <select name="semester">
                                    <option value="">Select Semester</option>
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($student['semester'] ?? '') == $i ? 'selected' : ''; ?>><?php echo $i; ?><?php echo $i == 1 ? 'st' : ($i == 2 ? 'nd' : ($i == 3 ? 'rd' : 'th')); ?> Semester</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions" style="margin-bottom:28px">
            <button type="submit" class="btn btn-primary" id="saveProfileBtn"><span class="btn-text">&#10003; Save Changes</span><span class="spinner"></span></button>
            <button type="button" class="btn btn-secondary" onclick="resetProfileForm()">Cancel</button>
        </div>
        </form>

        <div class="profile-grid">
            <div class="card-panel">
                <div class="panel-header">
                    <h3>&#128274; Change Password</h3>
                    <p>Update your account password for better security</p>
                </div>
                <div class="panel-body">
                    <form id="passwordForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <div class="form-group">
                            <label>Current Password</label>
                            <div class="input-wrapper">
                                <span class="input-icon">&#128274;</span>
                                <input type="password" name="current_password" id="cpCurrent" placeholder="Enter current password" required autocomplete="current-password">
                                <button type="button" class="toggle-vis" onclick="toggleVis('cpCurrent',this)">&#128065;</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <div class="input-wrapper">
                                <span class="input-icon">&#128272;</span>
                                <input type="password" name="new_password" id="cpNew" placeholder="Min 8 characters" required autocomplete="new-password">
                                <button type="button" class="toggle-vis" onclick="toggleVis('cpNew',this)">&#128065;</button>
                            </div>
                            <div class="password-strength" id="cpStrength" style="display:none">
                                <div class="strength-bar"><div class="strength-fill" id="cpStrengthFill"></div></div>
                                <span class="strength-text" id="cpStrengthText"></span>
                            </div>
                            <div class="field-msg" id="cpNewMsg"></div>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <div class="input-wrapper">
                                <span class="input-icon">&#128272;</span>
                                <input type="password" name="confirm_password" id="cpConfirm" placeholder="Re-enter new password" required autocomplete="new-password">
                                <button type="button" class="toggle-vis" onclick="toggleVis('cpConfirm',this)">&#128065;</button>
                            </div>
                            <div class="field-msg" id="cpConfirmMsg"></div>
                        </div>
                        <div class="alert-toast error" id="cpAlert" style="position:static;max-width:none;display:none"><span id="cpAlertMsg"></span></div>
                        <div class="alert-toast success" id="cpSuccess" style="position:static;max-width:none;display:none"><span id="cpSuccessMsg"></span></div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary" id="changePassBtn"><span class="btn-text">&#128274; Update Password</span><span class="spinner"></span></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-panel">
                <div class="panel-header">
                    <h3>&#9881; Account Settings</h3>
                    <p>Your account status and security details</p>
                </div>
                <div class="panel-body">
                    <div class="info-row">
                        <div class="info-label">Email Verification</div>
                        <div class="info-value <?php echo $student['is_verified'] ? 'verified' : 'pending'; ?>"><?php echo $student['is_verified'] ? '&#10003; Verified' : '&#9888; Pending'; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Account Status</div>
                        <div class="info-value <?php echo $student['is_active'] ? 'active' : 'pending'; ?>"><?php echo $student['is_active'] ? '&#10003; Active' : '&#10007; Suspended'; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Member Since</div>
                        <div class="info-value"><?php echo date('M d, Y', strtotime($student['created_at'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Last Activity</div>
                        <div class="info-value"><?php echo !empty($sessions) ? date('M d, g:i A', strtotime($sessions[0]['created_at'])) : 'N/A'; ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Dark Mode</div>
                        <div class="info-value"><?php echo $student['dark_mode'] ? 'Enabled' : 'Disabled'; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top:8px">
            <div class="danger-zone">
                <h3>&#9888; Danger Zone</h3>
                <p>Deactivating your account will hide your profile and data. Deleting your account will permanently remove all your information and cannot be undone.</p>
                <div class="danger-actions">
                    <button class="btn btn-outline" onclick="showDeactivateModal()">&#128683; Deactivate Account</button>
                    <button class="btn btn-danger" onclick="showDeleteModal()">&#128465; Delete Account</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer-dashboard">
        &copy; 2026 EngiHub. All rights reserved. &middot; <a href="student-dashboard.php">&#8592; Back to Dashboard</a> &middot; <a href="index.html">Back to Home</a>
    </footer>
</main>

<div class="modal-overlay" id="deactivateModal">
    <div class="modal">
        <h3>&#128683; Deactivate Account</h3>
        <p>Your account will be temporarily disabled. Your data will be preserved but you won't be able to log in until you reactivate.</p>
        <form id="deactivateForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="form-group">
                <label>Enter your password to confirm</label>
                <div class="input-wrapper"><input type="password" name="password" placeholder="Your password" required></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deactivateModal')">Cancel</button>
                <button type="submit" class="btn btn-outline"><span class="btn-text">&#128683; Deactivate</span><span class="spinner"></span></button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3>&#128465; Delete Account</h3>
        <p><strong style="color:#dc2626">This action is permanent and cannot be undone.</strong> All your data, downloads, saved resources, and profile information will be permanently deleted.</p>
        <form id="deleteForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="form-group">
                <label>Type <strong>DELETE</strong> to confirm</label>
                <div class="input-wrapper"><input type="text" name="confirm_text" placeholder="Type DELETE" required></div>
            </div>
            <div class="form-group">
                <label>Enter your password</label>
                <div class="input-wrapper"><input type="password" name="password" placeholder="Your password" required></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="btn btn-danger"><span class="btn-text">&#128465; Permanently Delete</span><span class="spinner"></span></button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('mobileOverlay').classList.toggle('show')}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('mobileOverlay').classList.remove('show')}
function toggleNotifications(){var p=document.getElementById('notifPanel'),d=document.getElementById('profileDropdown');d.classList.remove('show');p.classList.toggle('show')}
function toggleProfileDropdown(){var d=document.getElementById('profileDropdown'),p=document.getElementById('notifPanel');p.classList.remove('show');d.classList.toggle('show')}
document.addEventListener('click',function(e){
    var nb=document.getElementById('notifBtn'),np=document.getElementById('notifPanel'),pc=document.getElementById('profileChip'),pd=document.getElementById('profileDropdown');
    if(np.classList.contains('show')&&!np.contains(e.target)&&!nb.contains(e.target))np.classList.remove('show');
    if(pd.classList.contains('show')&&!pd.contains(e.target)&&!pc.contains(e.target))pd.classList.remove('show');
});
function markAllRead(){fetch('api/mark-notifications-read.php',{method:'POST'}).then(function(r){return r.json()}).then(function(d){if(d.success){document.querySelectorAll('.notif-item.unread').forEach(function(el){el.classList.remove('unread')});var b=document.querySelector('.top-nav-btn .badge');if(b)b.remove();}})}
function toggleVis(id,btn){var i=document.getElementById(id);i.type=i.type==='password'?'text':'password';btn.innerHTML=i.type==='password'?'&#128065;':'&#128064;'}
function closeModal(id){document.getElementById(id).classList.remove('show')}
function showDeactivateModal(){document.getElementById('deactivateModal').classList.add('show')}
function showDeleteModal(){document.getElementById('deleteModal').classList.add('show')}
function showToast(msg,type){var t=document.createElement('div');t.className='alert-toast '+type;t.innerHTML='<span>'+msg+'</span>';document.body.appendChild(t);setTimeout(function(){t.style.opacity='0';t.style.transform='translateX(40px)';setTimeout(function(){t.remove()},300)},3500)}

function handlePhotoUpload(input){
    if(!input.files||!input.files[0])return;
    var file=input.files[0];
    if(!file.type.match('image/(jpeg|png|gif)')){showToast('Please select a JPG, PNG, or GIF image.','error');return;}
    if(file.size>5*1024*1024){showToast('Image must be under 5MB.','error');return;}
    var fd=new FormData();fd.append('photo',file);fd.append('csrf_token','<?php echo $csrfToken; ?>');
    var btn=document.querySelector('.hero-photo-btn');btn.innerHTML='&#8987;';
    fetch('upload-profile-photo.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        btn.innerHTML='&#128247;';
        if(d.success){
            showToast('Profile photo updated!','success');
            var url=URL.createObjectURL(file);
            var img=document.getElementById('heroAvatarImg');
            if(img){img.src=url;img.style.display='block';var ph=document.getElementById('heroAvatarPH');if(ph)ph.style.display='none';}
            else{var wrap=document.getElementById('heroAvatarWrap');var ni=document.createElement('img');ni.src=url;ni.alt='Profile';ni.className='hero-avatar';ni.id='heroAvatarImg';wrap.insertBefore(ni,wrap.firstChild);var ph2=document.getElementById('heroAvatarPH');if(ph2)ph2.style.display='none';}
        }else showToast(d.message||'Upload failed','error');
    }).catch(function(){btn.innerHTML='&#128247;';showToast('Network error.','error');});
}

document.getElementById('profileForm').addEventListener('submit',function(e){
    e.preventDefault();
    var btn=document.getElementById('saveProfileBtn');btn.classList.add('loading');
    var fd=new FormData(this);
    fetch('profile-update.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        btn.classList.remove('loading');
        if(d.success){showToast('Profile updated successfully!','success');updateCompletion(d.completion);}
        else showToast(d.message||'Update failed','error');
    }).catch(function(){btn.classList.remove('loading');showToast('Network error.','error');});
});

document.getElementById('passwordForm').addEventListener('submit',function(e){
    e.preventDefault();
    var np=document.getElementById('cpNew').value;
    var cp=document.getElementById('cpConfirm').value;
    if(np.length<8){showCpError('Password must be at least 8 characters');return;}
    if(!/[A-Z]/.test(np)||!/[a-z]/.test(np)||!/[0-9]/.test(np)||!/[^A-Za-z0-9]/.test(np)){showCpError('Password needs uppercase, lowercase, number, and special character');return;}
    if(np!==cp){showCpError('Passwords do not match');return;}
    var btn=document.getElementById('changePassBtn');btn.classList.add('loading');
    var fd=new FormData(this);
    fetch('change-password.php',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        btn.classList.remove('loading');
        if(d.success){showCpSuccess(d.message);document.getElementById('passwordForm').reset();hideCpAlerts();}
        else showCpError(d.message||'Failed to change password');
    }).catch(function(){btn.classList.remove('loading');showCpError('Network error');});
});
function showCpError(m){var a=document.getElementById('cpAlert');document.getElementById('cpAlertMsg').textContent=m;a.style.display='flex';document.getElementById('cpSuccess').style.display='none';}
function showCpSuccess(m){var s=document.getElementById('cpSuccess');document.getElementById('cpSuccessMsg').textContent=m;s.style.display='flex';document.getElementById('cpAlert').style.display='none';}
function hideCpAlerts(){document.getElementById('cpAlert').style.display='none';document.getElementById('cpSuccess').style.display='none';}

var cpNew=document.getElementById('cpNew');
cpNew.addEventListener('input',function(){
    var v=this.value,s=document.getElementById('cpStrength'),f=document.getElementById('cpStrengthFill'),t=document.getElementById('cpStrengthText');
    if(!v){s.style.display='none';return;}
    s.style.display='block';var sc=0;
    if(v.length>=8)sc++;if(v.length>=12)sc++;if(/[A-Z]/.test(v)&&/[a-z]/.test(v))sc++;if(/[0-9]/.test(v))sc++;if(/[^A-Za-z0-9]/.test(v))sc++;
    f.className='strength-fill';t.className='strength-text';
    if(sc<=2){f.classList.add('weak');t.textContent='Weak';t.style.color='#ef4444';}
    else if(sc<=3){f.classList.add('fair');t.textContent='Fair';t.style.color='#f59e0b';}
    else{f.classList.add('strong');t.textContent='Strong';t.style.color='#10b981';}
    var msgs=[];if(v.length<8)msgs.push('8+ chars');if(!/[A-Z]/.test(v))msgs.push('uppercase');if(!/[a-z]/.test(v))msgs.push('lowercase');if(!/[0-9]/.test(v))msgs.push('number');if(!/[^A-Za-z0-9]/.test(v))msgs.push('special char');
    var msg=document.getElementById('cpNewMsg');
    if(msgs.length){msg.textContent='Needs: '+msgs.join(', ');msg.className='field-msg show error';}
    else{msg.className='field-msg';}
    validateCpConfirm();
});
document.getElementById('cpConfirm').addEventListener('input',validateCpConfirm);
function validateCpConfirm(){var v=document.getElementById('cpConfirm').value,np=document.getElementById('cpNew').value,msg=document.getElementById('cpConfirmMsg');if(!v){msg.className='field-msg';return;}if(v!==np){msg.textContent='Passwords do not match';msg.className='field-msg show error';}else{msg.className='field-msg';}}

document.getElementById('deactivateForm').addEventListener('submit',function(e){
    e.preventDefault();var btn=this.querySelector('button[type="submit"]');btn.classList.add('loading');
    fetch('api/account-action.php',{method:'POST',body:new FormData(this)}).then(function(r){return r.json()}).then(function(d){
        btn.classList.remove('loading');
        if(d.success){showToast('Account deactivated. Redirecting...','success');setTimeout(function(){window.location.href='logout.php'},2000);}
        else{showToast(d.message||'Failed','error');}
    }).catch(function(){btn.classList.remove('loading');showToast('Network error','error');});
});
document.getElementById('deleteForm').addEventListener('submit',function(e){
    e.preventDefault();
    var ct=this.querySelector('[name="confirm_text"]').value;
    if(ct!=='DELETE'){showToast('Please type DELETE to confirm','error');return;}
    var btn=this.querySelector('button[type="submit"]');btn.classList.add('loading');
    fetch('api/account-action.php',{method:'POST',body:new URLSearchParams(new FormData(this)+'&action=delete')}).then(function(r){return r.json()}).then(function(d){
        btn.classList.remove('loading');
        if(d.success){showToast('Account deleted. Goodbye!','success');setTimeout(function(){window.location.href='logout.php'},2000);}
        else showToast(d.message||'Failed','error');
    }).catch(function(){btn.classList.remove('loading');showToast('Network error','error');});
});

function resetProfileForm(){document.getElementById('profileForm').reset();showToast('Form reset','success');}
function updateCompletion(pct){var bar=document.getElementById('pcbBar');var txt=document.getElementById('pcbPct');if(bar)bar.style.width=pct+'%';if(txt)txt.textContent=pct+'% complete';}
</script>
</body>
</html>

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

if (!$student) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$firstName = explode(' ', $student['full_name'])[0];
$profilePhoto = !empty($student['profile_photo']) ? 'uploads/profiles/' . htmlspecialchars($student['profile_photo']) : '';
$initials = strtoupper(substr($student['full_name'], 0, 1));

$conn->query("CREATE TABLE IF NOT EXISTS `saved_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `resource_type` enum('notes','pyq','practical','coding','projects') NOT NULL,
  `resource_id` int(11) NOT NULL,
  `saved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_save` (`student_id`,`resource_type`,`resource_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `saved_resources_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$conn->query("CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'bell',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `user_notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$conn->query("CREATE TABLE IF NOT EXISTS `user_activity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `resource_type` varchar(50) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `resource_title` varchar(255) DEFAULT NULL,
  `resource_subject` varchar(100) DEFAULT NULL,
  `resource_semester` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `user_activity_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$conn->query("CREATE TABLE IF NOT EXISTS `user_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `items_completed` int(11) DEFAULT 0,
  `total_items` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_progress` (`student_id`,`category`),
  CONSTRAINT `user_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

function safeQuery($conn, $sql) {
    $result = $conn->query($sql);
    return $result && $result->num_rows > 0 ? $result : false;
}

$notesCount = 0;
$r = $conn->query("SELECT COUNT(*) as c FROM notes");
if ($r) $notesCount = $r->fetch_assoc()['c'];

$pyqCount = 0;
$r = $conn->query("SELECT COUNT(*) as c FROM pyq");
if ($r) $pyqCount = $r->fetch_assoc()['c'];

$savedCount = 0;
$r = $conn->query("SELECT COUNT(*) as c FROM saved_resources WHERE student_id = $studentId");
if ($r) $savedCount = $r->fetch_assoc()['c'];

$downloadCount = 0;
$r = $conn->query("SELECT COUNT(*) as c FROM downloads WHERE student_id = $studentId");
if ($r) $downloadCount = $r->fetch_assoc()['c'];

$unreadNotifs = 0;
$r = $conn->query("SELECT COUNT(*) as c FROM user_notifications WHERE student_id = $studentId AND is_read = 0");
if ($r) $unreadNotifs = $r->fetch_assoc()['c'];

$recentNotifs = [];
$r = $conn->query("SELECT * FROM user_notifications WHERE student_id = $studentId ORDER BY created_at DESC LIMIT 8");
if ($r) { while ($row = $r->fetch_assoc()) $recentNotifs[] = $row; }

$recentActivity = [];
$r = $conn->query("SELECT * FROM user_activity WHERE student_id = $studentId ORDER BY created_at DESC LIMIT 6");
if ($r) { while ($row = $r->fetch_assoc()) $recentActivity[] = $row; }

$recentDownloads = [];
$r = $conn->query("SELECT d.*, n.title as note_title, n.subject as note_subject FROM downloads d LEFT JOIN notes n ON d.note_id = n.id WHERE d.student_id = $studentId ORDER BY d.downloaded_at DESC LIMIT 5");
if ($r) { while ($row = $r->fetch_assoc()) $recentDownloads[] = $row; }

$savedResources = [];
$r = $conn->query("SELECT s.*, 
  CASE s.resource_type
    WHEN 'notes' THEN (SELECT title FROM notes WHERE id = s.resource_id)
    WHEN 'pyq' THEN (SELECT title FROM pyq WHERE id = s.resource_id)
    WHEN 'practical' THEN (SELECT title FROM practicals WHERE id = s.resource_id)
    WHEN 'coding' THEN (SELECT title FROM coding WHERE id = s.resource_id)
    WHEN 'projects' THEN (SELECT title FROM projects WHERE id = s.resource_id)
  END as resource_title
  FROM saved_resources s WHERE s.student_id = $studentId ORDER BY s.saved_at DESC LIMIT 5");
if ($r) { while ($row = $r->fetch_assoc()) $savedResources[] = $row; }

$profileComplete = 0;
$fields = ['full_name','email','mobile','college_name','branch','semester','dob','state','city','profile_photo','student_id'];
$filled = 0;
foreach ($fields as $f) {
    if (!empty($student[$f])) $filled++;
}
$profileComplete = round(($filled / count($fields)) * 100);

$progress = ['resources' => ['done' => 0, 'total' => 20], 'coding' => ['done' => 0, 'total' => 15], 'practice' => ['done' => 0, 'total' => 10]];
$r = $conn->query("SELECT * FROM user_progress WHERE student_id = $studentId");
if ($r) { while ($row = $r->fetch_assoc()) {
    if (isset($progress[$row['category']])) {
        $progress[$row['category']]['done'] = $row['items_completed'];
        $progress[$row['category']]['total'] = max(1, $row['total_items']);
    }
}}
$overallProgress = 0;
$progDivisor = 0;
foreach ($progress as $p) { $overallProgress += $p['done']; $progDivisor += $p['total']; }
$overallPct = $progDivisor > 0 ? min(100, round(($overallProgress / $progDivisor) * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2563eb">
    <title>Student Dashboard - EngiHub</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex}
        a{text-decoration:none;color:inherit}
        button{cursor:pointer;font-family:inherit}

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
        .search-box input{width:320px;padding:9px 14px 9px 38px;border:1px solid #e5e7eb;border-radius:10px;font-size:13px;font-family:inherit;color:#111827;outline:none;transition:border-color .2s,box-shadow .2s;background:#f9fafb}
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
        .notif-header .mark-read{font-size:12px;color:#2563eb;font-weight:600;cursor:pointer;background:none;border:none}.notif-header .mark-read:hover{text-decoration:underline}
        .notif-list{max-height:380px;overflow-y:auto}
        .notif-list::-webkit-scrollbar{width:4px}.notif-list::-webkit-scrollbar-thumb{background:#e5e7eb;border-radius:4px}
        .notif-item{display:flex;gap:12px;padding:14px 20px;border-bottom:1px solid #f9fafb;transition:background .1s;cursor:pointer}
        .notif-item:hover{background:#f9fafb}
        .notif-item.unread{background:#f0f5ff}
        .notif-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
        .notif-icon.blue{background:#eff6ff;color:#2563eb}.notif-icon.green{background:#f0fdf4;color:#16a34a}.notif-icon.amber{background:#fffbeb;color:#f59e0b}.notif-icon.red{background:#fef2f2;color:#ef4444}
        .notif-content{flex:1;min-width:0}
        .notif-title{font-size:13px;font-weight:600;color:#111827;margin-bottom:2px}
        .notif-msg{font-size:12px;color:#6b7280;line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .notif-time{font-size:11px;color:#9ca3af;margin-top:3px}
        .notif-empty{text-align:center;padding:40px 20px;color:#9ca3af;font-size:13px}

        .page-content{padding:28px 32px;flex:1}
        .welcome-banner{background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 50%,#3b82f6 100%);border-radius:16px;padding:32px 36px;color:white;margin-bottom:28px;position:relative;overflow:hidden}
        .welcome-banner::before{content:'';position:absolute;right:-40px;top:-40px;width:200px;height:200px;background:rgba(255,255,255,.06);border-radius:50%}
        .welcome-banner::after{content:'';position:absolute;right:60px;bottom:-60px;width:160px;height:160px;background:rgba(255,255,255,.04);border-radius:50%}
        .welcome-banner h1{font-size:26px;font-weight:800;margin-bottom:6px;position:relative;z-index:1}
        .welcome-banner p{font-size:14px;opacity:.85;position:relative;z-index:1;max-width:500px;line-height:1.6}
        .welcome-banner .welcome-meta{display:flex;gap:20px;margin-top:16px;position:relative;z-index:1;flex-wrap:wrap}
        .welcome-banner .meta-item{display:flex;align-items:center;gap:6px;font-size:12px;opacity:.9}
        .welcome-banner .meta-item .meta-dot{width:6px;height:6px;background:rgba(255,255,255,.6);border-radius:50%}

        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
        .stat-card{background:white;border-radius:14px;padding:22px;box-shadow:0 1px 6px rgba(0,0,0,.04);transition:all .25s;border:1px solid #f3f4f6}
        .stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.08);border-color:#e0e7ff}
        .stat-card .stat-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px}
        .stat-card .stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px}
        .stat-icon.blue{background:#eff6ff;color:#2563eb}.stat-icon.green{background:#f0fdf4;color:#16a34a}.stat-icon.amber{background:#fffbeb;color:#f59e0b}.stat-icon.purple{background:#f5f3ff;color:#7c3aed}
        .stat-card .stat-change{font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px}
        .stat-change.up{background:#f0fdf4;color:#16a34a}.stat-change.down{background:#fef2f2;color:#ef4444}
        .stat-card .stat-value{font-size:30px;font-weight:800;color:#111827;margin-bottom:2px}
        .stat-card .stat-label{font-size:13px;color:#6b7280;font-weight:500}

        .section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
        .section-header h2{font-size:18px;font-weight:700;color:#111827}
        .section-header a{font-size:13px;color:#2563eb;font-weight:600}.section-header a:hover{text-decoration:underline}

        .quick-access{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px}
        .qa-card{background:white;border-radius:14px;padding:24px;box-shadow:0 1px 6px rgba(0,0,0,.04);transition:all .25s;border:1px solid #f3f4f6;text-align:center;cursor:pointer}
        .qa-card:hover{transform:translateY(-4px);box-shadow:0 12px 28px rgba(0,0,0,.08);border-color:#c7d2fe}
        .qa-card .qa-icon{font-size:36px;margin-bottom:12px}
        .qa-card h3{font-size:15px;font-weight:700;color:#111827;margin-bottom:4px}
        .qa-card p{font-size:12px;color:#6b7280;line-height:1.5}

        .two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px}
        .card-panel{background:white;border-radius:14px;box-shadow:0 1px 6px rgba(0,0,0,.04);border:1px solid #f3f4f6;overflow:hidden}
        .card-panel .panel-header{padding:18px 22px;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center}
        .card-panel .panel-header h3{font-size:15px;font-weight:700;color:#111827}
        .card-panel .panel-header a{font-size:12px;color:#2563eb;font-weight:600}.card-panel .panel-header a:hover{text-decoration:underline}
        .card-panel .panel-body{padding:6px 0}
        .panel-empty{text-align:center;padding:36px 20px;color:#9ca3af;font-size:13px}
        .panel-empty .empty-icon{font-size:36px;margin-bottom:10px;display:block}

        .activity-item{display:flex;gap:14px;padding:14px 22px;border-bottom:1px solid #f9fafb;transition:background .1s}
        .activity-item:last-child{border-bottom:none}
        .activity-item:hover{background:#f9fafb}
        .activity-icon-wrap{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
        .activity-info{flex:1;min-width:0}
        .activity-title{font-size:13px;font-weight:600;color:#111827;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .activity-meta{font-size:12px;color:#9ca3af;display:flex;align-items:center;gap:8px}
        .activity-meta .dot{width:3px;height:3px;background:#d1d5db;border-radius:50%}
        .activity-link{font-size:12px;color:#2563eb;font-weight:600;white-space:nowrap}.activity-link:hover{text-decoration:underline}

        .download-item{display:flex;align-items:center;gap:14px;padding:14px 22px;border-bottom:1px solid #f9fafb;transition:background .1s}
        .download-item:last-child{border-bottom:none}
        .download-item:hover{background:#f9fafb}
        .download-icon-wrap{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;background:#fef2f2;color:#ef4444}
        .download-info{flex:1;min-width:0}
        .download-title{font-size:13px;font-weight:600;color:#111827;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .download-meta{font-size:12px;color:#9ca3af}
        .download-btn{padding:6px 14px;background:#eff6ff;color:#2563eb;border:none;border-radius:8px;font-size:12px;font-weight:600;transition:all .15s;white-space:nowrap}
        .download-btn:hover{background:#2563eb;color:white}

        .saved-item{display:flex;align-items:center;gap:14px;padding:14px 22px;border-bottom:1px solid #f9fafb;transition:background .1s}
        .saved-item:last-child{border-bottom:none}
        .saved-item:hover{background:#f9fafb}
        .saved-icon-wrap{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;background:#f5f3ff;color:#7c3aed}
        .saved-info{flex:1;min-width:0}
        .saved-title{font-size:13px;font-weight:600;color:#111827;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .saved-meta{font-size:12px;color:#9ca3af;text-transform:capitalize}
        .saved-remove{padding:6px 14px;background:#fef2f2;color:#ef4444;border:none;border-radius:8px;font-size:12px;font-weight:600;transition:all .15s;white-space:nowrap}
        .saved-remove:hover{background:#ef4444;color:white}

        .progress-section{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px}
        .prog-card{background:white;border-radius:14px;padding:24px;box-shadow:0 1px 6px rgba(0,0,0,.04);border:1px solid #f3f4f6;text-align:center}
        .prog-circle{position:relative;width:80px;height:80px;margin:0 auto 14px}
        .prog-circle svg{width:80px;height:80px;transform:rotate(-90deg)}
        .prog-circle .prog-bg{fill:none;stroke:#e5e7eb;stroke-width:6}
        .prog-circle .prog-fill{fill:none;stroke-width:6;stroke-linecap:round;transition:stroke-dashoffset .8s ease}
        .prog-circle .prog-pct{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:18px;font-weight:800;color:#111827}
        .prog-card h4{font-size:14px;font-weight:700;color:#111827;margin-bottom:4px}
        .prog-card p{font-size:12px;color:#9ca3af}

        .profile-complete-card{background:white;border-radius:14px;padding:28px;box-shadow:0 1px 6px rgba(0,0,0,.04);border:1px solid #f3f4f6;margin-bottom:28px;display:flex;align-items:center;gap:28px}
        .pcc-left{flex:1}
        .pcc-left h3{font-size:17px;font-weight:700;margin-bottom:6px}
        .pcc-left p{font-size:13px;color:#6b7280;margin-bottom:14px;line-height:1.5}
        .pcc-bar-wrap{background:#e5e7eb;border-radius:8px;height:8px;overflow:hidden;margin-bottom:6px}
        .pcc-bar{height:100%;border-radius:8px;transition:width .8s ease;background:linear-gradient(90deg,#2563eb,#3b82f6)}
        .pcc-pct{font-size:12px;color:#6b7280;font-weight:600}
        .pcc-right{flex-shrink:0}
        .pcc-btn{padding:12px 28px;background:#2563eb;color:white;border:none;border-radius:10px;font-size:14px;font-weight:700;transition:all .2s;white-space:nowrap}
        .pcc-btn:hover{background:#1d4ed8;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.3)}

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
            .stats-grid{grid-template-columns:repeat(2,1fr)}
            .quick-access{grid-template-columns:repeat(2,1fr)}
            .two-col{grid-template-columns:1fr}
            .progress-section{grid-template-columns:repeat(3,1fr)}
        }
        @media(max-width:768px){
            .top-navbar{padding:0 16px;height:56px}
            .search-box input{width:160px}
            .page-content{padding:20px 16px}
            .welcome-banner{padding:24px 20px}
            .welcome-banner h1{font-size:22px}
            .welcome-banner .welcome-meta{gap:12px}
            .stats-grid{grid-template-columns:1fr 1fr;gap:12px}
            .stat-card{padding:16px}
            .stat-card .stat-value{font-size:24px}
            .quick-access{grid-template-columns:1fr}
            .progress-section{grid-template-columns:1fr}
            .profile-complete-card{flex-direction:column;text-align:center}
            .notification-panel{width:calc(100vw - 32px);right:-40px}
            .profile-chip .profile-name,.profile-chip .profile-role{display:none}
            .profile-chip{padding:4px}
            .profile-avatar,.profile-avatar-placeholder{width:32px;height:32px}
        }
        @media(max-width:480px){
            .stats-grid{grid-template-columns:1fr}
            .search-box{display:none}
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
        <a href="#dashboard" class="active"><span class="nav-icon">&#127968;</span>Dashboard</a>
        <a href="profile.php"><span class="nav-icon">&#128100;</span>My Profile</a>
        <a href="notes.html"><span class="nav-icon">&#128218;</span>Notes</a>
        <a href="syllabus.html"><span class="nav-icon">&#128209;</span>Syllabus</a>
        <a href="pyq.html"><span class="nav-icon">&#128196;</span>Previous Year Questions</a>
        <a href="practical.html"><span class="nav-icon">&#128300;</span>Practical</a>

        <div class="nav-section-title">Learn & Build</div>
        <a href="coding.html"><span class="nav-icon">&#128187;</span>Coding</a>
        <a href="projects.html"><span class="nav-icon">&#128640;</span>Projects</a>
        <a href="placement.html"><span class="nav-icon">&#127919;</span>Placement</a>

        <div class="nav-section-title">Personal</div>
        <a href="#saved"><span class="nav-icon">&#128278;</span>Saved Resources</a>
        <a href="#downloads"><span class="nav-icon">&#11015;</span>Download History</a>
        <a href="#notifications"><span class="nav-icon">&#128276;</span>Notifications<?php if ($unreadNotifs > 0): ?><span class="nav-badge"><?php echo $unreadNotifs; ?></span><?php endif; ?></a>

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
            <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">&#9776;</button>
            <div class="search-box">
                <span class="search-icon">&#128269;</span>
                <input type="text" id="globalSearch" placeholder="Search notes, PYQ, subjects..." aria-label="Search resources">
            </div>
        </div>
        <div class="top-nav-right">
            <button class="top-nav-btn" onclick="toggleNotifications()" aria-label="Notifications" id="notifBtn">
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
            <div class="notif-header">
                <h3>Notifications</h3>
                <button class="mark-read" onclick="markAllRead()">Mark all read</button>
            </div>
            <div class="notif-list" id="notifList">
                <?php if (empty($recentNotifs)): ?>
                    <div class="notif-empty">
                        <span style="font-size:36px;display:block;margin-bottom:8px">&#128276;</span>
                        No notifications yet.<br>You'll see updates here when new content is available.
                    </div>
                <?php else: ?>
                    <?php foreach ($recentNotifs as $notif): ?>
                    <div class="notif-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>" onclick="markNotifRead(<?php echo $notif['id']; ?>, this)">
                        <div class="notif-icon <?php echo $notif['icon'] === 'upload' ? 'green' : ($notif['icon'] === 'alert' ? 'amber' : 'blue'); ?>">
                            <?php echo $notif['icon'] === 'upload' ? '&#128194;' : ($notif['icon'] === 'alert' ? '&#9888;' : '&#128276;'); ?>
                        </div>
                        <div class="notif-content">
                            <div class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                            <div class="notif-msg"><?php echo htmlspecialchars($notif['message'] ?? ''); ?></div>
                            <div class="notif-time"><?php echo date('M j, g:i A', strtotime($notif['created_at'])); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="profile-dropdown" id="profileDropdown">
            <div class="dd-label">Account</div>
            <a href="profile.php">&#128100; My Profile</a>
            <a href="#saved">&#128278; Saved Resources</a>
            <a href="#downloads">&#11015; Download History</a>
            <div class="dd-divider"></div>
            <div class="dd-label">Settings</div>
            <a href="#notifications">&#128276; Notifications</a>
            <div class="dd-divider"></div>
            <a href="logout.php" style="color:#ef4444">&#10148; Logout</a>
        </div>
    </header>

    <div class="page-content">
        <div class="welcome-banner">
            <h1>Welcome back, <?php echo htmlspecialchars($firstName); ?> &#128075;</h1>
            <p>Continue your engineering learning journey. Access notes, PYQs, practicals, coding practice, and placement resources &mdash; all in one place.</p>
            <div class="welcome-meta">
                <div class="meta-item"><span class="meta-dot"></span><?php echo htmlspecialchars($student['branch'] ?? 'Engineering'); ?></div>
                <div class="meta-item"><span class="meta-dot"></span>Semester <?php echo htmlspecialchars($student['semester'] ?? '1'); ?></div>
                <div class="meta-item"><span class="meta-dot"></span><?php echo htmlspecialchars($student['college_name'] ?? 'College'); ?></div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon blue">&#128218;</div>
                </div>
                <div class="stat-value"><?php echo number_format($notesCount); ?>+</div>
                <div class="stat-label">Available Notes</div>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon green">&#128196;</div>
                </div>
                <div class="stat-value"><?php echo number_format($pyqCount); ?>+</div>
                <div class="stat-label">PYQ Resources</div>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon amber">&#128278;</div>
                </div>
                <div class="stat-value"><?php echo number_format($savedCount); ?></div>
                <div class="stat-label">Saved Resources</div>
            </div>
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon purple">&#11015;</div>
                </div>
                <div class="stat-value"><?php echo number_format($downloadCount); ?></div>
                <div class="stat-label">Total Downloads</div>
            </div>
        </div>

        <div class="section-header">
            <h2>Quick Access</h2>
        </div>
        <div class="quick-access">
            <a href="notes.html" style="text-decoration:none;color:inherit">
                <div class="qa-card">
                    <div class="qa-icon">&#128218;</div>
                    <h3>Browse Notes</h3>
                    <p>Subject-wise study notes uploaded by toppers and faculty</p>
                </div>
            </a>
            <a href="pyq.html" style="text-decoration:none;color:inherit">
                <div class="qa-card">
                    <div class="qa-icon">&#128196;</div>
                    <h3>Previous Year Questions</h3>
                    <p>Practice with real exam papers from 2018 to 2025</p>
                </div>
            </a>
            <a href="practical.html" style="text-decoration:none;color:inherit">
                <div class="qa-card">
                    <div class="qa-icon">&#128300;</div>
                    <h3>Practical Resources</h3>
                    <p>Lab manuals, experiments, and viva preparation guides</p>
                </div>
            </a>
            <a href="coding.html" style="text-decoration:none;color:inherit">
                <div class="qa-card">
                    <div class="qa-icon">&#128187;</div>
                    <h3>Learn Coding</h3>
                    <p>C, C++, Python, Java, JavaScript tutorials and practice</p>
                </div>
            </a>
            <a href="projects.html" style="text-decoration:none;color:inherit">
                <div class="qa-card">
                    <div class="qa-icon">&#128640;</div>
                    <h3>Explore Projects</h3>
                    <p>Mini project ideas across Web, Android, Python, IoT</p>
                </div>
            </a>
            <a href="placement.html" style="text-decoration:none;color:inherit">
                <div class="qa-card">
                    <div class="qa-icon">&#127919;</div>
                    <h3>Placement Preparation</h3>
                    <p>Aptitude, coding tests, interview questions, company prep</p>
                </div>
            </a>
        </div>

        <div class="two-col">
            <div class="card-panel">
                <div class="panel-header">
                    <h3>Recently Viewed</h3>
                    <a href="#activity">View All</a>
                </div>
                <div class="panel-body">
                    <?php if (empty($recentActivity)): ?>
                        <div class="panel-empty">
                            <span class="empty-icon">&#128270;</span>
                            No resources viewed yet.<br>Start exploring to see your history here.
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentActivity as $act): ?>
                        <div class="activity-item">
                            <div class="activity-icon-wrap" style="background:<?php
                                $colors = ['notes'=>['#eff6ff','#2563eb'],'pyq'=>['#f0fdf4','#16a34a'],'practical'=>['#fffbeb','#f59e0b'],'coding'=>['#f5f3ff','#7c3aed'],'projects'=>['#fef2f2','#ef4444']];
                                $c = $colors[$act['resource_type']] ?? ['#eff6ff','#2563eb'];
                                echo 'background:'.$c[0].';color:'.$c[1];
                            ?>">
                                <?php
                                $icons = ['notes'=>'&#128218;','pyq'=>'&#128196;','practical'=>'&#128300;','coding'=>'&#128187;','projects'=>'&#128640;'];
                                echo $icons[$act['resource_type']] ?? '&#128196;';
                                ?>
                            </div>
                            <div class="activity-info">
                                <div class="activity-title"><?php echo htmlspecialchars($act['resource_title'] ?? 'Resource'); ?></div>
                                <div class="activity-meta">
                                    <span style="text-transform:capitalize"><?php echo htmlspecialchars($act['resource_type'] ?? ''); ?></span>
                                    <span class="dot"></span>
                                    <span><?php echo date('M j, g:i A', strtotime($act['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-panel">
                <div class="panel-header">
                    <h3>Recent Downloads</h3>
                    <a href="#downloads">View All</a>
                </div>
                <div class="panel-body">
                    <?php if (empty($recentDownloads)): ?>
                        <div class="panel-empty">
                            <span class="empty-icon">&#11015;</span>
                            No downloads yet.<br>Download notes and resources to track them here.
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentDownloads as $dl): ?>
                        <div class="download-item">
                            <div class="download-icon-wrap">&#128196;</div>
                            <div class="download-info">
                                <div class="download-title"><?php echo htmlspecialchars($dl['note_title'] ?? 'File'); ?></div>
                                <div class="download-meta"><?php echo date('M j, Y', strtotime($dl['downloaded_at'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="two-col">
            <div class="card-panel">
                <div class="panel-header">
                    <h3>Saved Resources</h3>
                    <a href="#saved">View All</a>
                </div>
                <div class="panel-body">
                    <?php if (empty($savedResources)): ?>
                        <div class="panel-empty">
                            <span class="empty-icon">&#128278;</span>
                            No saved resources yet.<br>Bookmark resources to access them quickly later.
                        </div>
                    <?php else: ?>
                        <?php foreach ($savedResources as $sv): ?>
                        <div class="saved-item">
                            <div class="saved-icon-wrap">
                                <?php echo $sv['resource_type'] === 'notes' ? '&#128218;' : ($sv['resource_type'] === 'pyq' ? '&#128196;' : '&#128187;'); ?>
                            </div>
                            <div class="saved-info">
                                <div class="saved-title"><?php echo htmlspecialchars($sv['resource_title'] ?? 'Resource'); ?></div>
                                <div class="saved-meta"><?php echo htmlspecialchars($sv['resource_type']); ?> &middot; Saved <?php echo date('M j', strtotime($sv['saved_at'])); ?></div>
                            </div>
                            <button class="saved-remove" onclick="removeSaved(<?php echo $sv['id']; ?>, this)">Remove</button>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-panel">
                <div class="panel-header">
                    <h3>Notifications</h3>
                    <a href="#notifications">View All</a>
                </div>
                <div class="panel-body">
                    <?php if (empty($recentNotifs)): ?>
                        <div class="panel-empty">
                            <span class="empty-icon">&#128276;</span>
                            No notifications yet.<br>You'll see updates here when new content is available.
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($recentNotifs, 0, 4) as $notif): ?>
                        <div class="activity-item" style="cursor:pointer">
                            <div class="activity-icon-wrap" style="background:<?php echo $notif['icon'] === 'upload' ? '#f0fdf4;color:#16a34a' : ($notif['icon'] === 'alert' ? '#fffbeb;color:#f59e0b' : '#eff6ff;color:#2563eb'); ?>">
                                <?php echo $notif['icon'] === 'upload' ? '&#128194;' : ($notif['icon'] === 'alert' ? '&#9888;' : '&#128276;'); ?>
                            </div>
                            <div class="activity-info">
                                <div class="activity-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                <div class="activity-meta">
                                    <span><?php echo date('M j, g:i A', strtotime($notif['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="section-header" style="margin-top:8px">
            <h2>Learning Progress</h2>
        </div>
        <div class="progress-section">
            <div class="prog-card">
                <div class="prog-circle">
                    <svg viewBox="0 0 36 36">
                        <circle class="prog-bg" cx="18" cy="18" r="14"/>
                        <circle class="prog-fill" cx="18" cy="18" r="14" stroke="#2563eb"
                            stroke-dasharray="88" stroke-dashoffset="<?php echo 88 - (88 * min(100, $progress['resources']['total'] > 0 ? round(($progress['resources']['done']/$progress['resources']['total'])*100) : 0) / 100); ?>"/>
                    </svg>
                    <span class="prog-pct"><?php echo $progress['resources']['total'] > 0 ? round(($progress['resources']['done']/$progress['resources']['total'])*100) : 0; ?>%</span>
                </div>
                <h4>Resources Explored</h4>
                <p><?php echo $progress['resources']['done']; ?> of <?php echo $progress['resources']['total']; ?> resources</p>
            </div>
            <div class="prog-card">
                <div class="prog-circle">
                    <svg viewBox="0 0 36 36">
                        <circle class="prog-bg" cx="18" cy="18" r="14"/>
                        <circle class="prog-fill" cx="18" cy="18" r="14" stroke="#7c3aed"
                            stroke-dasharray="88" stroke-dashoffset="<?php echo 88 - (88 * min(100, $progress['coding']['total'] > 0 ? round(($progress['coding']['done']/$progress['coding']['total'])*100) : 0) / 100); ?>"/>
                    </svg>
                    <span class="prog-pct"><?php echo $progress['coding']['total'] > 0 ? round(($progress['coding']['done']/$progress['coding']['total'])*100) : 0; ?>%</span>
                </div>
                <h4>Coding Progress</h4>
                <p><?php echo $progress['coding']['done']; ?> of <?php echo $progress['coding']['total']; ?> problems</p>
            </div>
            <div class="prog-card">
                <div class="prog-circle">
                    <svg viewBox="0 0 36 36">
                        <circle class="prog-bg" cx="18" cy="18" r="14"/>
                        <circle class="prog-fill" cx="18" cy="18" r="14" stroke="#16a34a"
                            stroke-dasharray="88" stroke-dashoffset="<?php echo 88 - (88 * min(100, $progress['practice']['total'] > 0 ? round(($progress['practice']['done']/$progress['practice']['total'])*100) : 0) / 100); ?>"/>
                    </svg>
                    <span class="prog-pct"><?php echo $progress['practice']['total'] > 0 ? round(($progress['practice']['done']/$progress['practice']['total'])*100) : 0; ?>%</span>
                </div>
                <h4>Practice Completion</h4>
                <p><?php echo $progress['practice']['done']; ?> of <?php echo $progress['practice']['total']; ?> tasks</p>
            </div>
        </div>

        <div class="profile-complete-card" id="profileCard">
            <div class="pcc-left">
                <h3>Complete Your Profile</h3>
                <p>Your profile is <?php echo $profileComplete; ?>% complete. Fill in your details to get personalized recommendations and a better experience.</p>
                <div class="pcc-bar-wrap">
                    <div class="pcc-bar" style="width:<?php echo $profileComplete; ?>%"></div>
                </div>
                <div class="pcc-pct"><?php echo $profileComplete; ?>% complete</div>
            </div>
            <div class="pcc-right">
                <a href="profile.php" class="pcc-btn">Complete Profile</a>
            </div>
        </div>
    </div>

    <footer class="footer-dashboard">
        &copy; 2026 EngiHub. All rights reserved. &middot; Built for engineering students, by engineering students. &middot; <a href="index.html">Back to Home</a>
    </footer>
</main>

<script>
function toggleSidebar(){
    var s=document.getElementById('sidebar'),o=document.getElementById('mobileOverlay');
    s.classList.toggle('open');o.classList.toggle('show');
}
function closeSidebar(){
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('mobileOverlay').classList.remove('show');
}

function toggleNotifications(){
    var p=document.getElementById('notifPanel'),d=document.getElementById('profileDropdown');
    d.classList.remove('show');p.classList.toggle('show');
}
function toggleProfileDropdown(){
    var d=document.getElementById('profileDropdown'),p=document.getElementById('notifPanel');
    p.classList.remove('show');d.classList.toggle('show');
}
document.addEventListener('click',function(e){
    var nb=document.getElementById('notifBtn'),np=document.getElementById('notifPanel');
    var pc=document.getElementById('profileChip'),pd=document.getElementById('profileDropdown');
    if(np.classList.contains('show')&&!np.contains(e.target)&&!nb.contains(e.target))np.classList.remove('show');
    if(pd.classList.contains('show')&&!pd.contains(e.target)&&!pc.contains(e.target))pd.classList.remove('show');
});

function markAllRead(){
    fetch('api/mark-notifications-read.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'}})
    .then(function(r){return r.json()})
    .then(function(d){
        if(d.success){
            document.querySelectorAll('.notif-item.unread').forEach(function(el){el.classList.remove('unread')});
            var b=document.querySelector('.top-nav-btn .badge');if(b)b.remove();
            var sb=document.querySelector('.sidebar-nav .nav-badge');if(sb)sb.remove();
        }
    });
}
function markNotifRead(id,el){
    if(!el.classList.contains('unread'))return;
    fetch('api/mark-notifications-read.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id})
    .then(function(r){return r.json()})
    .then(function(d){if(d.success)el.classList.remove('unread');});
}

function removeSaved(id,btn){
    if(!confirm('Remove from saved?'))return;
    fetch('api/remove-saved.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id})
    .then(function(r){return r.json()})
    .then(function(d){if(d.success){btn.closest('.saved-item').remove();}});
}

var searchInput=document.getElementById('globalSearch');
if(searchInput){
    searchInput.addEventListener('keydown',function(e){
        if(e.key==='Enter'&&this.value.trim()){
            var q=encodeURIComponent(this.value.trim());
            window.location.href='notes.html?search='+q;
        }
    });
}

var sidebarLinks=document.querySelectorAll('.sidebar-nav a[href^="#"]');
sidebarLinks.forEach(function(link){
    link.addEventListener('click',function(e){
        sidebarLinks.forEach(function(l){l.classList.remove('active')});
        this.classList.add('active');
        if(window.innerWidth<=1024)closeSidebar();
    });
});
</script>
</body>
</html>

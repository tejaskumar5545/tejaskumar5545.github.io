<?php
require_once '../db.php';
requireAdmin();

$stats = ['students'=>0,'notes'=>0,'pyq'=>0,'practicals'=>0,'projects'=>0,'downloads'=>0,'messages'=>0];
$r=$conn->query("SELECT COUNT(*) as c FROM students");if($r)$stats['students']=$r->fetch_assoc()['c'];
$r=$conn->query("SELECT COUNT(*) as c FROM notes");if($r)$stats['notes']=$r->fetch_assoc()['c'];
$r=$conn->query("SELECT COUNT(*) as c FROM pyq");if($r)$stats['pyq']=$r->fetch_assoc()['c'];
$r=$conn->query("SELECT COUNT(*) as c FROM practicals");if($r)$stats['practicals']=$r->fetch_assoc()['c'];
$r=$conn->query("SELECT COUNT(*) as c FROM projects");if($r)$stats['projects']=$r->fetch_assoc()['c'];
$r=$conn->query("SELECT COUNT(*) as c FROM downloads");if($r)$stats['downloads']=$r->fetch_assoc()['c'];
$r=$conn->query("SELECT COUNT(*) as c FROM contact_messages");if($r)$stats['messages']=$r->fetch_assoc()['c'];

$conn->query("CREATE TABLE IF NOT EXISTS activity_logs(
  id int(11) NOT NULL AUTO_INCREMENT,admin_id int(11) DEFAULT NULL,action varchar(255) NOT NULL,details text DEFAULT NULL,created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

$recentActivity=[];
$r=$conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10");
if($r){while($row=$r->fetch_assoc())$recentActivity[]=$row;}

$recentStudents=[];
$r=$conn->query("SELECT * FROM students ORDER BY created_at DESC LIMIT 8");
if($r){while($row=$r->fetch_assoc())$recentStudents[]=$row;}

$recentResources=[];
$r=$conn->query("(SELECT id,title,branch,semester,upload_date,'notes' as type FROM notes ORDER BY upload_date DESC LIMIT 4) UNION ALL (SELECT id,title,branch,semester,upload_date,'pyq' as type FROM pyq ORDER BY upload_date DESC LIMIT 4) UNION ALL (SELECT id,title,branch,semester,upload_date,'practical' as type FROM practicals ORDER BY upload_date DESC LIMIT 4) ORDER BY upload_date DESC LIMIT 10");
$recentResources=[];
if($r){while($row=$r->fetch_assoc())$recentResources[]=$row;}

$recentMessages=[];
$r=$conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");
if($r){while($row=$r->fetch_assoc())$recentMessages[]=$row;}

$adminName=$_SESSION['admin_name']??'Admin';
$unreadMsgs=0;
$r=$conn->query("SELECT COUNT(*) as c FROM contact_messages");
if($r)$unreadMsgs=$r->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="theme-color" content="#1e3a5f">
<title>Admin Dashboard - EngiHub</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex}
a{text-decoration:none;color:inherit}
button{cursor:pointer;font-family:inherit}

.sidebar{width:260px;background:#0f172a;color:white;height:100vh;position:fixed;top:0;left:0;z-index:200;display:flex;flex-direction:column;transition:transform .3s ease}
.sidebar-header{padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:12px}
.sidebar-header .logo{font-size:22px;font-weight:800;color:#60a5fa}.sidebar-header .logo span{color:white}
.sidebar-header .badge-admin{font-size:9px;background:#2563eb;color:white;padding:2px 8px;border-radius:20px;font-weight:700;letter-spacing:.5px}
.sidebar-nav{flex:1;overflow-y:auto;padding:8px 0}
.sidebar-nav::-webkit-scrollbar{width:4px}.sidebar-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:4px}
.sidebar-nav .nav-label{font-size:10px;font-weight:700;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:1px;padding:16px 24px 6px}
.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:11px 24px;color:rgba(255,255,255,.5);font-size:13px;font-weight:500;transition:all .15s;border-left:3px solid transparent}
.sidebar-nav a:hover{color:white;background:rgba(255,255,255,.04)}
.sidebar-nav a.active{color:#60a5fa;background:rgba(96,165,250,.08);border-left-color:#60a5fa;font-weight:600}
.sidebar-nav .nav-icon{width:20px;text-align:center;font-size:15px;flex-shrink:0}
.sidebar-nav .nav-badge{margin-left:auto;background:#ef4444;color:white;font-size:9px;font-weight:700;padding:2px 7px;border-radius:20px;min-width:18px;text-align:center}
.sidebar-footer{padding:16px 24px;border-top:1px solid rgba(255,255,255,.06)}
.sidebar-footer a{display:flex;align-items:center;gap:10px;padding:10px 0;font-size:13px;color:#ef4444;font-weight:600;transition:color .15s}.sidebar-footer a:hover{color:#f87171}

.main-content{flex:1;margin-left:260px;min-height:100vh;display:flex;flex-direction:column}
.topbar{height:64px;background:white;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;padding:0 32px;position:sticky;top:0;z-index:150}
.topbar-left{display:flex;align-items:center;gap:16px}
.sidebar-toggle{display:none;background:none;border:none;font-size:22px;color:#4b5563;padding:6px;border-radius:8px}.sidebar-toggle:hover{background:#f3f4f6}
.search-box{position:relative}
.search-box input{width:280px;padding:9px 14px 9px 36px;border:1px solid #e5e7eb;border-radius:10px;font-size:13px;color:#111827;outline:none;transition:all .2s;background:#f9fafb}
.search-box input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.08);background:white}
.search-box input::placeholder{color:#9ca3af}
.search-box .s-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:13px;pointer-events:none}
.topbar-right{display:flex;align-items:center;gap:8px}
.topbar-btn{background:none;border:none;width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#6b7280;position:relative;transition:all .15s}
.topbar-btn:hover{background:#f3f4f6;color:#2563eb}
.topbar-btn .badge{position:absolute;top:6px;right:6px;background:#ef4444;color:white;font-size:9px;font-weight:700;padding:1px 5px;border-radius:20px;min-width:16px;text-align:center;line-height:14px}
.admin-chip{display:flex;align-items:center;gap:10px;padding:5px 14px 5px 5px;border-radius:50px;cursor:pointer;transition:background .15s}
.admin-chip:hover{background:#f3f4f6}
.admin-avatar{width:34px;height:34px;border-radius:50%;background:#2563eb;color:white;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0}
.admin-name{font-size:13px;font-weight:600;color:#374151}
.admin-role{font-size:11px;color:#9ca3af}
.admin-dropdown{position:absolute;top:52px;right:24px;background:white;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.12);border:1px solid #f3f4f6;min-width:200px;padding:8px;display:none;animation:ddIn .2s ease;z-index:160}
.admin-dropdown.show{display:block}
@keyframes ddIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.admin-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;font-size:13px;color:#374151;border-radius:8px;transition:background .1s;width:100%}
.admin-dropdown a:hover{background:#f5f7fb}
.admin-dropdown .dd-div{height:1px;background:#f3f4f6;margin:4px 8px}

.page-content{padding:28px 32px;flex:1}

.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.stat-card{background:white;border-radius:14px;padding:22px;box-shadow:0 1px 6px rgba(0,0,0,.04);border:1px solid #f3f4f6;display:flex;align-items:center;gap:16px;transition:all .25s}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,.08)}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.stat-icon.blue{background:#eff6ff;color:#2563eb}.stat-icon.green{background:#f0fdf4;color:#16a34a}.stat-icon.purple{background:#faf5ff;color:#9333ea}.stat-icon.orange{background:#fff7ed;color:#ea580c}.stat-icon.red{background:#fef2f2;color:#ef4444}.stat-icon.cyan{background:#ecfeff;color:#0891b2}.stat-icon.amber{background:#fffbeb;color:#f59e0b}
.stat-info h3{font-size:26px;font-weight:800;color:#111827;line-height:1}.stat-info p{font-size:12px;color:#6b7280;margin-top:4px}

.section-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.section-header h2{font-size:18px;font-weight:700;color:#111827}
.section-header a{font-size:13px;color:#2563eb;font-weight:600}.section-header a:hover{text-decoration:underline}

.two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px}
.card-panel{background:white;border-radius:14px;box-shadow:0 1px 6px rgba(0,0,0,.04);border:1px solid #f3f4f6;overflow:hidden}
.panel-header{padding:18px 22px;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center}
.panel-header h3{font-size:15px;font-weight:700;color:#111827}
.panel-header a{font-size:12px;color:#2563eb;font-weight:600}
.panel-body{padding:4px 0}

.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th,td{padding:12px 16px;text-align:left;border-bottom:1px solid #f3f4f6;font-size:13px}
th{font-weight:700;color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.5px;background:#f9fafb}
td{color:#374151}
tr:hover td{background:#f9fafb}

.stu-name{display:flex;align-items:center;gap:10px}
.stu-avatar{width:32px;height:32px;border-radius:50%;background:#e0e7ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0}
.stu-avatar img{width:32px;height:32px;border-radius:50%;object-fit:cover}
.stu-info .name{font-weight:600;font-size:13px;color:#111827}.stu-info .email{font-size:11px;color:#9ca3af}
.badge-status{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block}
.badge-active{background:#f0fdf4;color:#16a34a}.badge-inactive{background:#fef2f2;color:#ef4444}.badge-verified{background:#eff6ff;color:#2563eb}
.badge-type{padding:3px 10px;border-radius:6px;font-size:11px;font-weight:600;display:inline-block}
.badge-notes{background:#eff6ff;color:#2563eb}.badge-pyq{background:#f0fdf4;color:#16a34a}.badge-practical{background:#faf5ff;color:#9333ea}.badge-project{background:#fff7ed;color:#ea580c}.badge-coding{background:#ecfeff;color:#0891b2}
.action-btn{padding:5px 12px;border-radius:6px;font-size:11px;font-weight:600;border:none;transition:all .15s}
.action-btn.view{background:#eff6ff;color:#2563eb}.action-btn.view:hover{background:#2563eb;color:white}
.action-btn.edit{background:#fff7ed;color:#ea580c}.action-btn.edit:hover{background:#ea580c;color:white}
.action-btn.block{background:#fef2f2;color:#ef4444}.action-btn.block:hover{background:#ef4444;color:white}
.action-btn.delete{background:#fef2f2;color:#ef4444}.action-btn.delete:hover{background:#ef4444;color:white}

.activity-item{display:flex;gap:12px;padding:12px 22px;border-bottom:1px solid #f9fafb;transition:background .1s}
.activity-item:last-child{border-bottom:none}
.activity-item:hover{background:#f9fafb}
.activity-dot{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.activity-dot.blue{background:#eff6ff;color:#2563eb}.activity-dot.green{background:#f0fdf4;color:#16a34a}.activity-dot.amber{background:#fffbeb;color:#f59e0b}.activity-dot.purple{background:#faf5ff;color:#9333ea}
.activity-info{flex:1;min-width:0}
.activity-title{font-size:13px;font-weight:600;color:#111827;margin-bottom:2px}
.activity-time{font-size:11px;color:#9ca3af}

.msg-item{display:flex;gap:12px;padding:12px 22px;border-bottom:1px solid #f9fafb;transition:background .1s}
.msg-item:last-child{border-bottom:none}
.msg-item:hover{background:#f9fafb}
.msg-avatar{width:36px;height:36px;border-radius:50%;background:#e0e7ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0}
.msg-info{flex:1;min-width:0}
.msg-name{font-size:13px;font-weight:600;color:#111827}
.msg-subject{font-size:12px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.msg-time{font-size:11px;color:#9ca3af;white-space:nowrap}

.quick-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.quick-btn{display:flex;align-items:center;gap:12px;padding:14px 16px;background:white;border:2px solid #f3f4f6;border-radius:12px;font-size:13px;font-weight:600;color:#374151;transition:all .2s}
.quick-btn:hover{border-color:#2563eb;color:#2563eb;background:#f0f5ff;transform:translateY(-1px)}
.quick-btn .qb-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}

.empty-state{text-align:center;padding:36px 20px;color:#9ca3af;font-size:13px}
.empty-state .empty-icon{font-size:36px;margin-bottom:8px;display:block}

.footer-admin{background:#111827;color:white;padding:24px 32px;text-align:center;font-size:13px;color:#6b7280;margin-top:auto}
.footer-admin a{color:#60a5fa}

.mobile-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:190}
.mobile-overlay.show{display:block}

.toast{position:fixed;top:24px;right:24px;padding:14px 20px;border-radius:12px;font-size:13px;font-weight:600;z-index:9999;display:flex;align-items:center;gap:10px;animation:tIn .3s ease;box-shadow:0 8px 24px rgba(0,0,0,.12);max-width:400px}
.toast.success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.toast.error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
@keyframes tIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}}

@media(max-width:1024px){
.sidebar{transform:translateX(-100%)}
.sidebar.open{transform:translateX(0)}
.mobile-overlay.show{display:block}
.main-content{margin-left:0}
.sidebar-toggle{display:flex}
.stats-grid{grid-template-columns:repeat(2,1fr)}
.two-col{grid-template-columns:1fr}
.quick-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:768px){
.topbar{padding:0 16px;height:56px}
.search-box input{width:160px}
.page-content{padding:20px 16px}
.stats-grid{grid-template-columns:1fr 1fr;gap:12px}
.stat-card{padding:16px;gap:12px}
.stat-info h3{font-size:22px}
.quick-grid{grid-template-columns:1fr}
.admin-chip .admin-name,.admin-chip .admin-role{display:none}
.admin-chip{padding:4px}
.table-wrap{margin:0 -16px;padding:0 16px;overflow-x:auto;-webkit-overflow-scrolling:touch}
}
@media(max-width:480px){
.search-box{display:none}
.stats-grid{grid-template-columns:1fr}
}
@media(prefers-reduced-motion:reduce){*{animation-duration:.01ms!important;transition-duration:.01ms!important}}
</style>
</head>
<body>

<aside class="sidebar" id="sidebar">
<div class="sidebar-header">
<a href="index.php" class="logo">Engi<span>Hub</span></a>
<span class="badge-admin">ADMIN</span>
</div>
<nav class="sidebar-nav">
<div class="nav-label">Overview</div>
<a href="index.php" class="active"><span class="nav-icon">&#127968;</span>Dashboard</a>

<div class="nav-label">Management</div>
<a href="students.php"><span class="nav-icon">&#127891;</span>Students</a>
<a href="notes.php"><span class="nav-icon">&#128218;</span>Notes Management</a>
<a href="syllabus.php"><span class="nav-icon">&#128209;</span>Syllabus Management</a>
<a href="pyq.php"><span class="nav-icon">&#128196;</span>PYQ Management</a>
<a href="practicals.php"><span class="nav-icon">&#128300;</span>Practical Management</a>

<div class="nav-label">Resources</div>
<a href="coding.php"><span class="nav-icon">&#128187;</span>Coding Resources</a>
<a href="projects.php"><span class="nav-icon">&#128640;</span>Projects Management</a>
<a href="placement.php"><span class="nav-icon">&#127919;</span>Placement Resources</a>

<div class="nav-label">Communication</div>
<a href="notices.php"><span class="nav-icon">&#128227;</span>Notices</a>
<a href="messages.php"><span class="nav-icon">&#128172;</span>Messages<?php if($unreadMsgs>0):?><span class="nav-badge"><?php echo $unreadMsgs;?></span><?php endif;?></a>

<div class="nav-label">System</div>
<a href="settings.php"><span class="nav-icon">&#9881;</span>Website Settings</a>
<a href="profile.php"><span class="nav-icon">&#128100;</span>Admin Profile</a>
</nav>
<div class="sidebar-footer">
<a href="logout.php">&#10148; Logout</a>
</div>
</aside>

<div class="mobile-overlay" id="mobileOverlay" onclick="closeSidebar()"></div>

<main class="main-content">
<header class="topbar">
<div class="topbar-left">
<button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button>
<div class="search-box">
<span class="s-icon">&#128269;</span>
<input type="text" placeholder="Search students, resources..." aria-label="Search">
</div>
</div>
<div class="topbar-right">
<button class="topbar-btn" onclick="toggleNotifs()" id="notifBtn">&#128276;<span class="badge" id="notifBadge"><?php echo $unreadMsgs;?></span></button>
<div class="admin-chip" onclick="toggleAdminDD()" id="adminChip">
<div class="admin-avatar"><?php echo strtoupper(substr($adminName,0,1));?></div>
<div><div class="admin-name"><?php echo htmlspecialchars($adminName);?></div><div class="admin-role">Administrator</div></div>
</div>
<div class="admin-dropdown" id="adminDD">
<a href="profile.php">&#128100; My Profile</a>
<a href="settings.php">&#9881; Settings</a>
<div class="dd-div"></div>
<a href="logout.php" style="color:#ef4444">&#10148; Logout</a>
</div>
</header>
</header>

<div class="page-content">
<div class="stats-grid">
<div class="stat-card"><div class="stat-icon blue">&#127891;</div><div class="stat-info"><h3><?php echo number_format($stats['students']);?></h3><p>Total Students</p></div></div>
<div class="stat-card"><div class="stat-icon green">&#128218;</div><div class="stat-info"><h3><?php echo number_format($stats['notes']);?></h3><p>Total Notes</p></div></div>
<div class="stat-card"><div class="stat-icon amber">&#128196;</div><div class="stat-info"><h3><?php echo number_format($stats['pyq']);?></h3><p>Total PYQ</p></div></div>
<div class="stat-card"><div class="stat-icon purple">&#128300;</div><div class="stat-info"><h3><?php echo number_format($stats['practicals']);?></h3><p>Total Practicals</p></div></div>
<div class="stat-card"><div class="stat-icon orange">&#128640;</div><div class="stat-info"><h3><?php echo number_format($stats['projects']);?></h3><p>Total Projects</p></div></div>
<div class="stat-card"><div class="stat-icon cyan">&#11015;</div><div class="stat-info"><h3><?php echo number_format($stats['downloads']);?></h3><p>Total Downloads</p></div></div>
<div class="stat-card"><div class="stat-icon red">&#128172;</div><div class="stat-info"><h3><?php echo number_format($stats['messages']);?></h3><p>Unread Messages</p></div></div>
</div>

<div class="section-header"><h2>Quick Actions</h2></div>
<div class="quick-grid" style="margin-bottom:28px">
<a href="notes.php?action=add" class="quick-btn"><div class="qb-icon" style="background:#eff6ff;color:#2563eb">&#128218;</div>Add New Note</a>
<a href="pyq.php?action=add" class="quick-btn"><div class="qb-icon" style="background:#f0fdf4;color:#16a34a">&#128196;</div>Upload PYQ</a>
<a href="practicals.php?action=add" class="quick-btn"><div class="qb-icon" style="background:#faf5ff;color:#9333ea">&#128300;</div>Add Practical</a>
<a href="projects.php?action=add" class="quick-btn"><div class="qb-icon" style="background:#fff7ed;color:#ea580c">&#128640;</div>Add Project</a>
<a href="notices.php?action=add" class="quick-btn"><div class="qb-icon" style="background:#fef2f2;color:#ef4444">&#128227;</div>Create Notice</a>
<a href="messages.php" class="quick-btn"><div class="qb-icon" style="background:#ecfeff;color:#0891b2">&#128172;</div>View Messages</a>
</div>

<div class="two-col">
<div class="card-panel">
<div class="panel-header"><h3>&#128337; Recent Activity</h3><a href="#">View All</a></div>
<div class="panel-body">
<?php if(empty($recentActivity)):?>
<div class="empty-state"><span class="empty-icon">&#128337;</span>No activity yet.<br>Actions will appear here as you manage content.</div>
<?php else: foreach($recentActivity as $act):?>
<div class="activity-item">
<div class="activity-dot <?php echo $act['action']==='student_registered'?'blue':($act['action']==='note_uploaded'?'green':($act['action']==='message_received'?'amber':'purple'));?>">
<?php echo $act['action']==='student_registered'?'&#127891;':($act['action']==='note_uploaded'?'&#128218;':($act['action']==='message_received'?'&#128172;':'&#128221;'));?>
</div>
<div class="activity-info">
<div class="activity-title"><?php echo htmlspecialchars($act['action']);?></div>
<div class="activity-time"><?php echo date('M j, g:i A',strtotime($act['created_at']));?></div>
</div>
</div>
<?php endforeach; endif;?>
</div>
</div>

<div class="card-panel">
<div class="panel-header"><h3>&#128172; Recent Messages</h3><a href="messages.php">View All</a></div>
<div class="panel-body">
<?php if(empty($recentMessages)):?>
<div class="empty-state"><span class="empty-icon">&#128172;</span>No messages yet.<br>Contact messages from students will appear here.</div>
<?php else: foreach($recentMessages as $msg):?>
<div class="msg-item">
<div class="msg-avatar"><?php echo strtoupper(substr($msg['name'],0,1));?></div>
<div class="msg-info">
<div class="msg-name"><?php echo htmlspecialchars($msg['name']);?></div>
<div class="msg-subject"><?php echo htmlspecialchars($msg['subject']);?></div>
</div>
<div class="msg-time"><?php echo date('M j',strtotime($msg['created_at']));?></div>
</div>
<?php endforeach; endif;?>
</div>
</div>
</div>

<div class="section-header"><h2>Recent Students</h2><a href="students.php">View All &#8594;</a></div>
<div class="card-panel" style="margin-bottom:28px">
<div class="table-wrap">
<table>
<thead><tr><th>Student</th><th>Email</th><th>College</th><th>Branch</th><th>Sem</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php if(!empty($recentStudents)): foreach($recentStudents as $stu):?>
<tr>
<td>
<div class="stu-name">
<div class="stu-avatar"><?php echo strtoupper(substr($stu['full_name'],0,1));?></div>
<div class="stu-info"><div class="name"><?php echo htmlspecialchars($stu['full_name']);?></div></div>
</div>
</td>
<td><?php echo htmlspecialchars($stu['email']);?></td>
<td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars($stu['college_name']??'-');?></td>
<td><?php echo htmlspecialchars($stu['branch']);?></td>
<td><?php echo htmlspecialchars($stu['semester']);?></td>
<td><span class="badge-status <?php echo $stu['is_active']?'badge-active':'badge-inactive';?>"><?php echo $stu['is_active']?'Active':'Inactive';?></span></td>
<td>
<div style="display:flex;gap:4px;flex-wrap:nowrap">
<a href="students.php?action=view&id=<?php echo $stu['id'];?>" class="action-btn view">View</a>
<a href="students.php?action=edit&id=<?php echo $stu['id'];?>" class="action-btn edit">Edit</a>
<form method="POST" action="students.php?action=edit&id=<?php echo $stu['id'];?>" style="display:inline"><input type="hidden" name="act" value="toggle_active"><button type="submit" class="action-btn block"><?php echo $stu['is_active']?'Block':'Unblock';?></button></form>
</div>
</td>
</tr>
<?php endforeach; else:?>
<tr><td colspan="7" class="empty-state">No students registered yet.</td></tr>
<?php endif;?>
</tbody>
</table>
</div>
</div>

<div class="section-header"><h2>Recent Resources</h2></div>
<div class="card-panel">
<div class="table-wrap">
<table>
<thead><tr><th>Resource</th><th>Type</th><th>Branch</th><th>Semester</th><th>Uploaded</th><th>Actions</th></tr></thead>
<tbody>
<?php if(!empty($recentResources)): foreach($recentResources as $res):?>
<tr>
<td style="font-weight:600"><?php echo htmlspecialchars($res['title']);?></td>
<td><span class="badge-type badge-<?php echo $res['type'];?>"><?php echo ucfirst($res['type']);?></span></td>
<td><?php echo htmlspecialchars($res['branch']);?></td>
<td><?php echo htmlspecialchars($res['semester']);?></td>
<td><?php echo date('M j, Y',strtotime($res['upload_date']));?></td>
<td>
<div style="display:flex;gap:4px">
<?php $pg=$res['type']==='practical'?'practicals':$res['type'];?>
<a href="<?php echo $pg;?>.php?action=edit&id=<?php echo $res['id'];?>" class="action-btn edit">Edit</a>
</div>
</td>
</tr>
<?php endforeach; else:?>
<tr><td colspan="6" class="empty-state">No resources uploaded yet.</td></tr>
<?php endif;?>
</tbody>
</table>
</div>
</div>
</div>

<footer class="footer-admin">
&copy; 2026 EngiHub Admin Panel. All rights reserved. &middot; <a href="../index.html">Back to Website</a>
</footer>
</main>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('mobileOverlay').classList.toggle('show')}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('mobileOverlay').classList.remove('show')}
function toggleAdminDD(){document.getElementById('adminDD').classList.toggle('show')}
function toggleNotifs(){window.location.href='messages.php'}
document.addEventListener('click',function(e){var ac=document.getElementById('adminChip'),dd=document.getElementById('adminDD');if(dd.classList.contains('show')&&!dd.contains(e.target)&&!ac.contains(e.target))dd.classList.remove('show');});
</script>
</body>
</html>

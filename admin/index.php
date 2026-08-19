<?php
require_once '../db.php';
requireAdmin();

$stats = ['students' => 0, 'notes' => 0, 'pyq' => 0, 'practicals' => 0];
$r = $conn->query("SELECT COUNT(*) as c FROM students"); if ($r) $stats['students'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) as c FROM notes"); if ($r) $stats['notes'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) as c FROM pyq"); if ($r) $stats['pyq'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) as c FROM practicals"); if ($r) $stats['practicals'] = $r->fetch_assoc()['c'];

$recent_uploads = $conn->query("(SELECT title, branch, semester, upload_date, 'notes' as type FROM notes ORDER BY upload_date DESC LIMIT 5) UNION ALL (SELECT title, branch, semester, upload_date, 'pyq' as type FROM pyq ORDER BY upload_date DESC LIMIT 5) ORDER BY upload_date DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#1e3a5f">
    <title>Admin Dashboard - EngiHub</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex}
        .sidebar{width:260px;background:#111827;color:white;padding:24px 0;flex-shrink:0;display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100}
        .sidebar .logo-area{padding:0 24px 24px;border-bottom:1px solid rgba(255,255,255,.1)}
        .sidebar .logo-area h2{font-size:22px;font-weight:800}.sidebar .logo-area p{font-size:12px;color:#9ca3af;margin-top:4px}
        .sidebar nav{flex:1;padding:16px 0}
        .sidebar nav a{display:flex;align-items:center;gap:12px;padding:12px 24px;color:#9ca3af;text-decoration:none;font-size:14px;font-weight:500;transition:all .2s;border-left:3px solid transparent}
        .sidebar nav a:hover{background:rgba(255,255,255,.05);color:white}.sidebar nav a.active{color:#60a5fa;border-left-color:#60a5fa;background:rgba(96,165,250,.1)}
        .sidebar .sidebar-footer{padding:16px 24px;border-top:1px solid rgba(255,255,255,.1);font-size:12px;color:#6b7280}
        .main{flex:1;margin-left:260px;padding:24px 32px}
        .topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px}
        .topbar h1{font-size:24px;font-weight:800;color:#111827}.topbar .admin-info{display:flex;align-items:center;gap:10px;font-size:14px;color:#6b7280}
        .topbar .admin-info a{color:#ef4444;text-decoration:none;font-weight:600;font-size:13px}
        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:32px}
        .stat-card{background:white;border-radius:14px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.06);display:flex;align-items:center;gap:16px}
        .stat-icon{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0}
        .stat-icon.blue{background:#eff6ff;color:#2563eb}.stat-icon.green{background:#f0fdf4;color:#16a34a}.stat-icon.purple{background:#faf5ff;color:#9333ea}.stat-icon.orange{background:#fff7ed;color:#ea580c}
        .stat-info h3{font-size:24px;font-weight:800;color:#111827}.stat-info p{font-size:13px;color:#6b7280;margin-top:2px}
        .content-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
        .card{background:white;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.06)}
        .card h3{font-size:16px;font-weight:700;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #f3f4f6}
        .upload-item{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid #f3f4f6}.upload-item:last-child{border-bottom:none}
        .upload-item .info h4{font-size:14px;font-weight:600}.upload-item .info p{font-size:12px;color:#6b7280;margin-top:2px}
        .upload-item .badge{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600}
        .badge-notes{background:#eff6ff;color:#2563eb}.badge-pyq{background:#f0fdf4;color:#16a34a}.badge-practicals{background:#faf5ff;color:#9333ea}
        .mobile-menu{display:none;position:fixed;top:0;left:0;right:0;height:60px;background:#111827;z-index:200;align-items:center;justify-content:space-between;padding:0 20px}
        .mobile-menu h2{color:white;font-size:18px;font-weight:700}.mobile-menu button{background:none;border:none;color:white;font-size:24px;cursor:pointer}
        @media(max-width:768px){.sidebar{display:none}.mobile-menu{display:flex}.main{margin-left:0;padding:80px 16px 24px}.stats-grid{grid-template-columns:1fr 1fr}.content-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="mobile-menu"><h2>EngiHub Admin</h2><button onclick="document.querySelector('.sidebar').style.display=document.querySelector('.sidebar').style.display==='flex'?'none':'flex'">&#9776;</button></div>
<div class="sidebar">
    <div class="logo-area"><h2>EngiHub</h2><p>Admin Panel</p></div>
    <nav>
        <a href="index.php" class="active">&#127968; Dashboard</a>
        <a href="notes.php">&#128218; Manage Notes</a>
        <a href="syllabus.php">&#128218; Manage Syllabus</a>
        <a href="pyq.php">&#128196; Manage PYQ</a>
        <a href="practicals.php">&#128295; Manage Practicals</a>
        <a href="students.php">&#127891; Students</a>
        <a href="notices.php">&#128227; Notices</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php" style="color:#ef4444;text-decoration:none;font-weight:600">Logout</a></div>
</div>
<div class="main">
    <div class="topbar"><h1>Dashboard</h1><div class="admin-info"><span>&#128100; <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span> | <a href="../logout.php">Logout</a></div></div>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon blue">&#127891;</div><div class="stat-info"><h3><?php echo $stats['students']; ?></h3><p>Total Students</p></div></div>
        <div class="stat-card"><div class="stat-icon green">&#128218;</div><div class="stat-info"><h3><?php echo $stats['notes']; ?></h3><p>Notes Uploaded</p></div></div>
        <div class="stat-card"><div class="stat-icon purple">&#128196;</div><div class="stat-info"><h3><?php echo $stats['pyq']; ?></h3><p>PYQ Papers</p></div></div>
        <div class="stat-card"><div class="stat-icon orange">&#128295;</div><div class="stat-info"><h3><?php echo $stats['practicals']; ?></h3><p>Practicals</p></div></div>
    </div>
    <div class="content-grid">
        <div class="card"><h3>&#128221; Recent Uploads</h3>
            <?php if ($recent_uploads && $recent_uploads->num_rows > 0): while ($row = $recent_uploads->fetch_assoc()): ?>
                <div class="upload-item"><div class="info"><h4><?php echo htmlspecialchars($row['title']); ?></h4><p><?php echo htmlspecialchars($row['branch']); ?> | Sem <?php echo htmlspecialchars($row['semester']); ?> | <?php echo date('d M', strtotime($row['upload_date'])); ?></p></div><span class="badge badge-<?php echo $row['type']; ?>"><?php echo ucfirst($row['type']); ?></span></div>
            <?php endwhile; else: ?><p style="text-align:center;color:#9ca3af;padding:20px 0">No uploads yet.</p><?php endif; ?>
        </div>
        <div class="card"><h3>&#9889; Quick Actions</h3>
            <a href="notes.php?action=add" style="display:block;padding:14px;background:#eff6ff;color:#2563eb;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;margin-bottom:10px">+ Upload Notes</a>
            <a href="pyq.php?action=add" style="display:block;padding:14px;background:#f0fdf4;color:#16a34a;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;margin-bottom:10px">+ Upload PYQ</a>
            <a href="practicals.php?action=add" style="display:block;padding:14px;background:#faf5ff;color:#9333ea;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px;margin-bottom:10px">+ Upload Practical</a>
            <a href="students.php" style="display:block;padding:14px;background:#fff7ed;color:#ea580c;border-radius:10px;text-decoration:none;font-weight:600;font-size:14px">View All Students</a>
        </div>
    </div>
</div>
</body></html>

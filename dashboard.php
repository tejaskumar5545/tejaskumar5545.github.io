<?php
require_once 'db.php';
requireLogin();

$student_id = $_SESSION['student_id'];
$name = $_SESSION['student_name'];
$branch = $_SESSION['student_branch'];
$semester = $_SESSION['student_semester'];

$stats = ['notes' => 0, 'pyq' => 0, 'practicals' => 0, 'downloads' => 0];
$r = $conn->query("SELECT COUNT(*) as c FROM notes WHERE branch='$branch' AND semester='$semester'");
if ($r) $stats['notes'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) as c FROM pyq WHERE branch='$branch' AND semester='$semester'");
if ($r) $stats['pyq'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) as c FROM practicals WHERE branch='$branch' AND semester='$semester'");
if ($r) $stats['practicals'] = $r->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) as c FROM downloads WHERE student_id=$student_id");
if ($r) $stats['downloads'] = $r->fetch_assoc()['c'];

$recent_notes = $conn->query("SELECT * FROM notes WHERE branch='$branch' AND semester='$semester' ORDER BY upload_date DESC LIMIT 5");
$recent_notices = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#2563eb">
    <title>Dashboard - EngiHub</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex;flex-direction:column}
        .navbar{width:100%;height:70px;background:white;display:flex;align-items:center;justify-content:space-between;padding:0 6%;box-shadow:0 2px 10px rgba(0,0,0,.08);position:sticky;top:0;z-index:100}
        .logo{font-size:28px;font-weight:bold;color:#2563eb}.logo span{color:#111827}
        .nav-links{display:flex;gap:20px;list-style:none;align-items:center}.nav-links a{text-decoration:none;color:#333;font-weight:500;transition:color .2s;font-size:14px}.nav-links a:hover{color:#2563eb}.nav-links a.active{color:#2563eb;font-weight:700}
        .nav-user{display:flex;align-items:center;gap:10px}.nav-user span{font-weight:600;color:#2563eb}.nav-user a{color:#ef4444;font-size:13px;font-weight:600;text-decoration:none}.nav-user a:hover{text-decoration:underline}
        .menu-toggle{display:none;background:none;border:none;font-size:28px;cursor:pointer;color:#111827}
        .dashboard{flex:1;max-width:1100px;margin:30px auto;width:100%;padding:0 6%}
        .dash-header{margin-bottom:28px}.dash-header h1{font-size:26px;font-weight:800;color:#111827}.dash-header p{font-size:14px;color:#6b7280;margin-top:4px}
        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:32px}
        .stat-card{background:white;border-radius:14px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.06);display:flex;align-items:center;gap:16px;transition:transform .2s}.stat-card:hover{transform:translateY(-3px)}
        .stat-icon{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0}
        .stat-icon.blue{background:#eff6ff;color:#2563eb}.stat-icon.green{background:#f0fdf4;color:#16a34a}.stat-icon.purple{background:#faf5ff;color:#9333ea}.stat-icon.orange{background:#fff7ed;color:#ea580c}
        .stat-info h3{font-size:24px;font-weight:800;color:#111827}.stat-info p{font-size:13px;color:#6b7280;margin-top:2px}
        .dash-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:32px}
        .dash-card{background:white;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.06)}
        .dash-card h3{font-size:16px;font-weight:700;color:#111827;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #f3f4f6}
        .note-item{display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid #f3f4f6}.note-item:last-child{border-bottom:none}
        .note-item .info h4{font-size:14px;font-weight:600;color:#111827}.note-item .info p{font-size:12px;color:#6b7280;margin-top:2px}
        .note-item a{background:#2563eb;color:white;padding:7px 14px;border-radius:7px;text-decoration:none;font-size:12px;font-weight:600;white-space:nowrap;transition:background .2s}.note-item a:hover{background:#1d4ed8}
        .notice-item{padding:12px 0;border-bottom:1px solid #f3f4f6}.notice-item:last-child{border-bottom:none}.notice-item h4{font-size:14px;font-weight:600;color:#111827}.notice-item p{font-size:12px;color:#6b7280;margin-top:3px}.notice-item .date{font-size:11px;color:#9ca3af;margin-top:4px}
        .empty-msg{text-align:center;color:#9ca3af;font-size:14px;padding:20px 0}
        .quick-links{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:32px}
        .quick-link{background:white;border-radius:12px;padding:18px;text-align:center;text-decoration:none;color:#111827;box-shadow:0 2px 10px rgba(0,0,0,.06);transition:all .2s}.quick-link:hover{transform:translateY(-3px);box-shadow:0 6px 20px rgba(0,0,0,.1)}.quick-link span{font-size:28px;display:block;margin-bottom:8px}.quick-link p{font-size:13px;font-weight:600;color:#374151}
        .footer{background:#111827;color:white;padding:20px 6%;text-align:center;margin-top:auto}.footer p{font-size:13px;color:#6b7280}
        @media(max-width:768px){.navbar{height:60px;padding:0 20px}.logo{font-size:23px}.nav-links{display:none;position:absolute;top:60px;left:0;right:0;background:white;flex-direction:column;padding:16px 20px;box-shadow:0 8px 24px rgba(0,0,0,.1);gap:0}.nav-links.open{display:flex}.nav-links a{padding:14px 0;border-bottom:1px solid #f3f4f6}.menu-toggle{display:block}.stats-grid{grid-template-columns:1fr 1fr}.dash-grid{grid-template-columns:1fr}.quick-links{grid-template-columns:1fr 1fr}.dashboard{padding:0 16px}}
    </style>
</head>
<body>
<nav class="navbar"><a href="index.html" class="logo">Engi<span>Hub</span></a><button class="menu-toggle" id="menuToggle">&#9776;</button><ul class="nav-links" id="navLinks"><li><a href="index.html">Home</a></li><li><a href="syllabus.html">Syllabus</a></li><li><a href="notes.php">Notes</a></li><li><a href="pyq.php">PYQ</a></li><li><a href="coding.html">Coding</a></li><li><div class="nav-user"><span>&#128100; <?php echo htmlspecialchars($name); ?></span><a href="logout.php">Logout</a></div></li></ul></nav>
<div class="dashboard">
    <div class="dash-header"><h1>Welcome back, <?php echo htmlspecialchars(explode(' ',$name)[0]); ?>!</h1><p><?php echo htmlspecialchars($branch); ?> | Semester <?php echo htmlspecialchars($semester); ?></p></div>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon blue">&#128218;</div><div class="stat-info"><h3><?php echo $stats['notes']; ?></h3><p>Study Notes</p></div></div>
        <div class="stat-card"><div class="stat-icon green">&#128196;</div><div class="stat-info"><h3><?php echo $stats['pyq']; ?></h3><p>PYQ Papers</p></div></div>
        <div class="stat-card"><div class="stat-icon purple">&#128295;</div><div class="stat-info"><h3><?php echo $stats['practicals']; ?></h3><p>Practicals</p></div></div>
        <div class="stat-card"><div class="stat-icon orange">&#11015;</div><div class="stat-info"><h3><?php echo $stats['downloads']; ?></h3><p>Downloads</p></div></div>
    </div>
    <div class="quick-links">
        <a href="syllabus.html" class="quick-link"><span>&#128218;</span><p>Syllabus</p></a>
        <a href="notes.php" class="quick-link"><span>&#128221;</span><p>Notes</p></a>
        <a href="pyq.php" class="quick-link"><span>&#128196;</span><p>Previous Year Qs</p></a>
        <a href="coding.html" class="quick-link"><span>&#128187;</span><p>Coding Hub</p></a>
    </div>
    <div class="dash-grid">
        <div class="dash-card"><h3>&#128218; Recent Notes</h3>
            <?php if ($recent_notes && $recent_notes->num_rows > 0): while ($row = $recent_notes->fetch_assoc()): ?>
                <div class="note-item"><div class="info"><h4><?php echo htmlspecialchars($row['title']); ?></h4><p><?php echo htmlspecialchars($row['subject_code'] ?? $row['branch']); ?></p></div><a href="download.php?type=notes&id=<?php echo $row['id']; ?>">Download</a></div>
            <?php endwhile; else: ?><p class="empty-msg">No notes uploaded yet for your branch/semester.</p><?php endif; ?>
        </div>
        <div class="dash-card"><h3>&#128227; Recent Notices</h3>
            <?php if ($recent_notices && $recent_notices->num_rows > 0): while ($row = $recent_notices->fetch_assoc()): ?>
                <div class="notice-item"><h4><?php echo htmlspecialchars($row['title']); ?></h4><p><?php echo htmlspecialchars(substr($row['content'],0,100)); ?></p><div class="date"><?php echo date('d M Y', strtotime($row['created_at'])); ?></div></div>
            <?php endwhile; else: ?><p class="empty-msg">No notices yet.</p><?php endif; ?>
        </div>
    </div>
</div>
<footer class="footer"><p>&copy; 2026 EngiHub. All rights reserved.</p></footer>
<script>document.getElementById('menuToggle').addEventListener('click',function(){document.getElementById('navLinks').classList.toggle('open')})</script>
</body></html>

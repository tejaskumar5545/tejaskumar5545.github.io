<?php
require_once 'db.php';
$branch = $_GET['branch'] ?? '';
$semester = $_GET['semester'] ?? '';
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM notes WHERE 1=1";
if ($branch) $query .= " AND branch='" . $conn->real_escape_string($branch) . "'";
if ($semester) $query .= " AND semester='" . $conn->real_escape_string($semester) . "'";
if ($search) $query .= " AND (title LIKE '%" . $conn->real_escape_string($search) . "%' OR subject_code LIKE '%" . $conn->real_escape_string($search) . "%')";
$query .= " ORDER BY upload_date DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#2563eb">
    <title>Notes - EngiHub</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex;flex-direction:column}
        .navbar{width:100%;height:70px;background:white;display:flex;align-items:center;justify-content:space-between;padding:0 6%;box-shadow:0 2px 10px rgba(0,0,0,.08);position:sticky;top:0;z-index:100}
        .logo{font-size:28px;font-weight:bold;color:#2563eb}.logo span{color:#111827}
        .nav-links{display:flex;gap:20px;list-style:none;align-items:center}.nav-links a{text-decoration:none;color:#333;font-weight:500;transition:color .2s;font-size:14px}.nav-links a:hover{color:#2563eb}.nav-links a.active{color:#2563eb;font-weight:700}
        .menu-toggle{display:none;background:none;border:none;font-size:28px;cursor:pointer;color:#111827}
        .hero{background:linear-gradient(135deg,#1e3a5f,#2563eb);color:white;padding:50px 6% 40px;text-align:center}
        .hero h1{font-size:32px;font-weight:800;margin-bottom:8px}.hero p{font-size:15px;opacity:.85}
        .container{max-width:1100px;margin:0 auto;width:100%;padding:0 6%}
        .filters{display:flex;gap:12px;flex-wrap:wrap;margin:24px 0;align-items:center}
        .filters select,.filters input{padding:10px 14px;border:2px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;background:white;color:#111827;outline:none}.filters select:focus,.filters input:focus{border-color:#2563eb}
        .filters input[type=text]{min-width:220px}
        .clear-btn{background:#f3f4f6;border:none;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;font-family:inherit;transition:all .2s}.clear-btn:hover{background:#e5e7eb;color:#111827}
        .notes-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px;padding:10px 0 40px}
        .note-card{background:white;border-radius:14px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.06);transition:all .2s;display:flex;flex-direction:column}.note-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
        .note-card .tag{display:inline-block;background:#eff6ff;color:#2563eb;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;margin-bottom:10px;width:fit-content}
        .note-card h3{font-size:16px;font-weight:700;color:#111827;margin-bottom:6px}
        .note-card p{font-size:13px;color:#6b7280;margin-bottom:14px;line-height:1.5;flex:1}
        .note-card .meta{display:flex;justify-content:space-between;align-items:center;margin-top:auto}
        .note-card .date{font-size:11px;color:#9ca3af}
        .note-card .actions{display:flex;gap:8px}
        .note-card .actions a{padding:8px 16px;border-radius:8px;text-decoration:none;font-size:12px;font-weight:600;transition:all .2s}
        .btn-view{background:#f3f4f6;color:#374151}.btn-view:hover{background:#e5e7eb}
        .btn-download{background:#2563eb;color:white}.btn-download:hover{background:#1d4ed8}
        .empty{text-align:center;padding:60px 0;color:#9ca3af;font-size:16px}
        .footer{background:#111827;color:white;padding:20px 6%;text-align:center;margin-top:auto}.footer p{font-size:13px;color:#6b7280}
        @media(max-width:768px){.navbar{height:60px;padding:0 20px}.logo{font-size:23px}.nav-links{display:none;position:absolute;top:60px;left:0;right:0;background:white;flex-direction:column;padding:16px 20px;box-shadow:0 8px 24px rgba(0,0,0,.1);gap:0}.nav-links.open{display:flex}.nav-links a{padding:14px 0;border-bottom:1px solid #f3f4f6}.menu-toggle{display:block}.notes-grid{grid-template-columns:1fr}.container{padding:0 16px}}
    </style>
</head>
<body>
<nav class="navbar"><a href="index.html" class="logo">Engi<span>Hub</span></a><button class="menu-toggle" id="menuToggle">&#9776;</button><ul class="nav-links" id="navLinks"><li><a href="index.html">Home</a></li><li><a href="syllabus.html">Syllabus</a></li><li><a href="notes.php" class="active">Notes</a></li><li><a href="pyq.php">PYQ</a></li><li><a href="coding.html">Coding</a></li><li><a href="login.php">Login</a></li></ul></nav>
<div class="hero"><h1>&#128218; Study Notes</h1><p>Access subject-wise notes for your branch and semester</p></div>
<div class="container">
    <form class="filters" method="GET" action="notes.php">
        <select name="branch"><option value="">All Branches</option><option value="CSE" <?php if($branch==='CSE')echo'selected';?>>CSE</option><option value="ECE" <?php if($branch==='ECE')echo'selected';?>>ECE</option><option value="ME" <?php if($branch==='ME')echo'selected';?>>ME</option><option value="CE" <?php if($branch==='CE')echo'selected';?>>CE</option><option value="EE" <?php if($branch==='EE')echo'selected';?>>EE</option></select>
        <select name="semester"><option value="">All Semesters</option><?php for($i=1;$i<=8;$i++):?><option value="<?php echo $i;?>" <?php if($semester==(string)$i)echo'selected';?>>Sem <?php echo $i;?></option><?php endfor;?></select>
        <input type="text" name="search" placeholder="Search notes..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn-download" style="border:none;cursor:pointer">&#128269; Search</button>
        <a href="notes.php" class="clear-btn">Clear All</a>
    </form>
    <div class="notes-grid">
        <?php if ($result && $result->num_rows > 0): while ($row = $result->fetch_assoc()): ?>
            <div class="note-card">
                <span class="tag"><?php echo htmlspecialchars($row['branch']); ?> | Sem <?php echo htmlspecialchars($row['semester']); ?></span>
                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                <p><?php echo htmlspecialchars($row['subject_code'] ?? 'General'); ?> <?php echo htmlspecialchars($row['description'] ?? ''); ?></p>
                <div class="meta"><span class="date"><?php echo date('d M Y', strtotime($row['upload_date'])); ?></span><div class="actions"><a href="download.php?type=notes&id=<?php echo $row['id'];?>" class="btn-view">View</a><a href="download.php?type=notes&id=<?php echo $row['id'];?>" class="btn-download">Download</a></div></div>
            </div>
        <?php endwhile; else: ?><div class="empty">&#128221; No notes found. Try different filters.</div><?php endif; ?>
    </div>
</div>
<footer class="footer"><p>&copy; 2026 EngiHub. All rights reserved.</p></footer>
<script>document.getElementById('menuToggle').addEventListener('click',function(){document.getElementById('navLinks').classList.toggle('open')})</script>
</body></html>

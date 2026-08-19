<?php
require_once 'db.php';
$branch = $_GET['branch'] ?? '';
$semester = $_GET['semester'] ?? '';
$year = $_GET['year'] ?? '';
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM pyq WHERE 1=1";
if ($branch) $query .= " AND branch='" . $conn->real_escape_string($branch) . "'";
if ($semester) $query .= " AND semester='" . $conn->real_escape_string($semester) . "'";
if ($year) $query .= " AND year='" . $conn->real_escape_string($year) . "'";
if ($search) $query .= " AND (title LIKE '%" . $conn->real_escape_string($search) . "%' OR subject LIKE '%" . $conn->real_escape_string($search) . "%')";
$query .= " ORDER BY year DESC, upload_date DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#2563eb">
    <title>Previous Year Questions - EngiHub</title>
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
        .clear-btn{background:#f3f4f6;border:none;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;font-family:inherit}.clear-btn:hover{background:#e5e7eb;color:#111827}
        .results-count{font-size:14px;color:#6b7280;margin-bottom:16px}
        .papers-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:18px;padding:10px 0 40px}
        .paper-card{background:white;border-radius:14px;padding:22px;box-shadow:0 2px 10px rgba(0,0,0,.06);transition:all .2s;display:flex;flex-direction:column}.paper-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,.1)}
        .paper-card .top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px}
        .paper-card .year-badge{background:#fef3c7;color:#92400e;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700}
        .paper-card h3{font-size:15px;font-weight:700;color:#111827;margin-bottom:6px}
        .paper-card .subject{font-size:13px;color:#6b7280;margin-bottom:12px}
        .paper-card .tags{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
        .paper-card .tags span{background:#f3f4f6;color:#374151;padding:3px 8px;border-radius:5px;font-size:11px;font-weight:500}
        .paper-card .meta{display:flex;justify-content:space-between;align-items:center;margin-top:auto}
        .paper-card .actions{display:flex;gap:8px}
        .paper-card .actions a{padding:8px 16px;border-radius:8px;text-decoration:none;font-size:12px;font-weight:600;transition:all .2s}
        .btn-view{background:#f3f4f6;color:#374151}.btn-view:hover{background:#e5e7eb}
        .btn-download{background:#2563eb;color:white}.btn-download:hover{background:#1d4ed8}
        .empty{text-align:center;padding:60px 0;color:#9ca3af;font-size:16px}
        .footer{background:#111827;color:white;padding:20px 6%;text-align:center;margin-top:auto}.footer p{font-size:13px;color:#6b7280}
        @media(max-width:768px){.navbar{height:60px;padding:0 20px}.logo{font-size:23px}.nav-links{display:none;position:absolute;top:60px;left:0;right:0;background:white;flex-direction:column;padding:16px 20px;box-shadow:0 8px 24px rgba(0,0,0,.1);gap:0}.nav-links.open{display:flex}.nav-links a{padding:14px 0;border-bottom:1px solid #f3f4f6}.menu-toggle{display:block}.papers-grid{grid-template-columns:1fr}.container{padding:0 16px}}
    </style>
</head>
<body>
<nav class="navbar"><a href="index.html" class="logo">Engi<span>Hub</span></a><button class="menu-toggle" id="menuToggle">&#9776;</button><ul class="nav-links" id="navLinks"><li><a href="index.html">Home</a></li><li><a href="syllabus.html">Syllabus</a></li><li><a href="notes.php">Notes</a></li><li><a href="pyq.php" class="active">PYQ</a></li><li><a href="coding.html">Coding</a></li><li><a href="login.php">Login</a></li></ul></nav>
<div class="hero"><h1>&#128196; Previous Year Questions</h1><p>Practice with real exam papers from previous years</p></div>
<div class="container">
    <form class="filters" method="GET" action="pyq.php">
        <select name="branch"><option value="">All Branches</option><option value="CSE" <?php if($branch==='CSE')echo'selected';?>>CSE</option><option value="ECE" <?php if($branch==='ECE')echo'selected';?>>ECE</option><option value="ME" <?php if($branch==='ME')echo'selected';?>>ME</option><option value="CE" <?php if($branch==='CE')echo'selected';?>>CE</option><option value="EE" <?php if($branch==='EE')echo'selected';?>>EE</option></select>
        <select name="semester"><option value="">All Semesters</option><?php for($i=1;$i<=8;$i++):?><option value="<?php echo $i;?>" <?php if($semester==(string)$i)echo'selected';?>>Sem <?php echo $i;?></option><?php endfor;?></select>
        <select name="year"><option value="">All Years</option><?php for($y=2025;$y>=2018;$y--):?><option value="<?php echo $y;?>" <?php if($year==(string)$y)echo'selected';?>><?php echo $y;?></option><?php endfor;?></select>
        <input type="text" name="search" placeholder="Search papers..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn-download" style="border:none;cursor:pointer">&#128269; Search</button>
        <a href="pyq.php" class="clear-btn">Clear All</a>
    </form>
    <div class="results-count"><?php echo $result->num_rows; ?> paper(s) found</div>
    <div class="papers-grid">
        <?php if ($result && $result->num_rows > 0): while ($row = $result->fetch_assoc()): ?>
            <div class="paper-card">
                <div class="top"><span class="year-badge"><?php echo htmlspecialchars($row['year']); ?></span><span class="year-badge" style="background:#eff6ff;color:#2563eb"><?php echo htmlspecialchars($row['branch']); ?></span></div>
                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                <div class="subject"><?php echo htmlspecialchars($row['subject'] ?? 'General'); ?> | Sem <?php echo htmlspecialchars($row['semester']); ?></div>
                <div class="meta"><div class="actions"><a href="download.php?type=pyq&id=<?php echo $row['id'];?>" class="btn-view">View</a><a href="download.php?type=pyq&id=<?php echo $row['id'];?>" class="btn-download">Download</a></div></div>
            </div>
        <?php endwhile; else: ?><div class="empty">&#128221; No papers found. Try different filters.</div><?php endif; ?>
    </div>
</div>
<footer class="footer"><p>&copy; 2026 EngiHub. All rights reserved.</p></footer>
<script>document.getElementById('menuToggle').addEventListener('click',function(){document.getElementById('navLinks').classList.toggle('open')})</script>
</body></html>

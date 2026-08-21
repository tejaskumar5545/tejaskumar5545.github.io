<?php
require_once '../db.php';
requireAdmin();

$conn->query("CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    branch VARCHAR(50) NOT NULL,
    semester INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    tags VARCHAR(255) DEFAULT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size BIGINT DEFAULT 0,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    downloads_count INT DEFAULT 0,
    uploaded_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$totalNotes = ($conn->query("SELECT COUNT(*) as c FROM notes")->fetch_assoc())['c'] ?? 0;
$publishedNotes = ($conn->query("SELECT COUNT(*) as c FROM notes WHERE status='published'")->fetch_assoc())['c'] ?? 0;
$draftNotes = ($conn->query("SELECT COUNT(*) as c FROM notes WHERE status='draft'")->fetch_assoc())['c'] ?? 0;
$totalDownloads = ($conn->query("SELECT COALESCE(SUM(downloads_count),0) as c FROM notes")->fetch_assoc())['c'] ?? 0;

$where = "1=1"; $params = []; $types = '';
if (!empty($_GET['search'])) { $where .= " AND (title LIKE ? OR subject LIKE ? OR tags LIKE ?)"; $s = "%".$_GET['search']."%"; $params[]=$s;$params[]=$s;$params[]=$s; $types.='sss'; }
if (!empty($_GET['branch'])) { $where .= " AND branch=?"; $params[]=$_GET['branch']; $types.='s'; }
if (!empty($_GET['semester'])) { $where .= " AND semester=?"; $params[]=$_GET['semester']; $types.='i'; }
if (!empty($_GET['subject'])) { $where .= " AND subject=?"; $params[]=$_GET['subject']; $types.='s'; }
if (!empty($_GET['status'])) { $where .= " AND status=?"; $params[]=$_GET['status']; $types.='s'; }

$page = max(1, intval($_GET['page'] ?? 1)); $perPage = 15; $offset = ($page - 1) * $perPage;
$countStmt = $conn->prepare("SELECT COUNT(*) as c FROM notes WHERE $where");
if (!empty($params)) { $countStmt->bind_param($types, ...$params); }
$countStmt->execute(); $totalRows = $countStmt->get_result()->fetch_assoc()['c']; $countStmt->close();
$totalPages = max(1, ceil($totalRows / $perPage));

$qTypes = $types . 'ii'; $qParams = array_merge($params, [$perPage, $offset]);
$stmt = $conn->prepare("SELECT * FROM notes WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param($qTypes, ...$qParams); $stmt->execute(); $notesResult = $stmt->get_result(); $stmt->close();

$subjects = [];
$sr = $conn->query("SELECT DISTINCT subject FROM notes ORDER BY subject");
if ($sr) { while ($r = $sr->fetch_assoc()) { $subjects[] = $r['subject']; } }

$flash = ''; $flashType = 'success';
if (isset($_SESSION['note_flash'])) { $flash = $_SESSION['note_flash']; unset($_SESSION['note_flash']); }
if (isset($_SESSION['note_flash_type'])) { $flashType = $_SESSION['note_flash_type']; unset($_SESSION['note_flash_type']); }
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$unreadMsgs = 0;
$mr = $conn->query("SELECT COUNT(*) as c FROM contact_messages");
if ($mr) $unreadMsgs = $mr->fetch_assoc()['c'];

function buildFilterUrl($overrides = []) {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, function($v) { return $v !== '' && $v !== null; });
    unset($params['page']);
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Notes Management - EngiHub Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex}a{text-decoration:none;color:inherit}button{cursor:pointer;font-family:inherit}
.sidebar{width:260px;background:#0f172a;color:white;height:100vh;position:fixed;top:0;left:0;z-index:200;display:flex;flex-direction:column;transition:transform .3s ease}.sidebar-header{padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:12px}.sidebar-header .logo{font-size:22px;font-weight:800;color:#60a5fa}.sidebar-header .logo span{color:white}.sidebar-header .badge-admin{font-size:9px;background:#2563eb;color:white;padding:2px 8px;border-radius:20px;font-weight:700;letter-spacing:.5px}.sidebar-nav{flex:1;overflow-y:auto;padding:8px 0}.sidebar-nav .nav-label{font-size:10px;font-weight:700;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:1px;padding:16px 24px 6px}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:11px 24px;color:rgba(255,255,255,.5);font-size:13px;font-weight:500;transition:all .15s;border-left:3px solid transparent}.sidebar-nav a:hover{color:white;background:rgba(255,255,255,.04)}.sidebar-nav a.active{color:#60a5fa;background:rgba(96,165,250,.08);border-left-color:#60a5fa;font-weight:600}.sidebar-nav .nav-icon{width:20px;text-align:center;font-size:15px;flex-shrink:0}.sidebar-footer{padding:16px 24px;border-top:1px solid rgba(255,255,255,.06)}.sidebar-footer a{display:flex;align-items:center;gap:10px;padding:10px 0;font-size:13px;color:#ef4444;font-weight:600}.sidebar-footer a:hover{color:#f87171}
.main-content{flex:1;margin-left:260px;min-height:100vh;display:flex;flex-direction:column}.topbar{height:64px;background:white;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;padding:0 32px;position:sticky;top:0;z-index:150}.topbar-left{display:flex;align-items:center;gap:16px}.sidebar-toggle{display:none;background:none;border:none;font-size:22px;color:#4b5563;padding:6px;border-radius:8px}.sidebar-toggle:hover{background:#f3f4f6}.search-box{position:relative}.search-box input{width:280px;padding:9px 14px 9px 36px;border:1px solid #e5e7eb;border-radius:10px;font-size:13px;color:#111827;outline:none;background:#f9fafb}.search-box input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.08);background:white}.search-box .s-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:13px;pointer-events:none}.topbar-right{display:flex;align-items:center;gap:8px}.admin-chip{display:flex;align-items:center;gap:10px;padding:5px 14px 5px 5px;border-radius:50px;cursor:pointer}.admin-chip:hover{background:#f3f4f6}.admin-avatar{width:34px;height:34px;border-radius:50%;background:#2563eb;color:white;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0}.admin-name{font-size:13px;font-weight:600;color:#374151}.admin-role{font-size:11px;color:#9ca3af}.admin-dropdown{position:absolute;top:52px;right:24px;background:white;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.12);border:1px solid #f3f4f6;min-width:200px;padding:8px;display:none;z-index:160}.admin-dropdown.show{display:block}.admin-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;font-size:13px;color:#374151;border-radius:8px;width:100%}.admin-dropdown a:hover{background:#f5f7fb}.admin-dropdown .dd-div{height:1px;background:#f3f4f6;margin:4px 8px}
.page-content{padding:28px 32px;flex:1}.page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px}.page-header h1{font-size:24px;font-weight:800}.page-header .sub{font-size:13px;color:#6b7280;margin-top:2px}.page-header-right{display:flex;gap:8px;flex-wrap:wrap}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}.stat-card{background:white;border-radius:14px;padding:22px;box-shadow:0 1px 6px rgba(0,0,0,.04);border:1px solid #f3f4f6;display:flex;align-items:center;gap:16px;transition:all .25s}.stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,.08)}.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}.stat-icon.blue{background:#eff6ff;color:#2563eb}.stat-icon.green{background:#f0fdf4;color:#16a34a}.stat-icon.amber{background:#fffbeb;color:#f59e0b}.stat-icon.cyan{background:#ecfeff;color:#0891b2}.stat-info h3{font-size:26px;font-weight:800;color:#111827;line-height:1}.stat-info p{font-size:12px;color:#6b7280;margin-top:4px}
.filter-bar{background:white;border-radius:14px;padding:20px 24px;box-shadow:0 1px 6px rgba(0,0,0,.04);border:1px solid #f3f4f6;margin-bottom:20px}.filter-row{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap}.filter-group{display:flex;flex-direction:column;gap:4px;flex:1;min-width:140px}.filter-group label{font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px}.filter-group input,.filter-group select{padding:9px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#111827;outline:none;background:white}.filter-group input:focus,.filter-group select:focus{border-color:#2563eb}.filter-actions{display:flex;gap:8px;align-items:flex-end;padding-top:2px}
.btn{padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .2s;display:inline-flex;align-items:center;gap:6px;text-decoration:none}.btn-primary{background:#2563eb;color:white}.btn-primary:hover{background:#1d4ed8;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.3)}.btn-danger{background:#ef4444;color:white}.btn-danger:hover{background:#dc2626}.btn-outline{background:white;color:#374151;border:1px solid #e5e7eb}.btn-outline:hover{background:#f9fafb}.btn-sm{padding:6px 14px;font-size:12px;border-radius:7px}
.card-panel{background:white;border-radius:14px;box-shadow:0 1px 6px rgba(0,0,0,.04);border:1px solid #f3f4f6;overflow:hidden;margin-bottom:24px}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse}th,td{padding:14px 16px;text-align:left;border-bottom:1px solid #f3f4f6;font-size:13px}th{font-weight:700;color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.5px;background:#f9fafb;white-space:nowrap}td{color:#374151}tr:hover td{background:#f9fafb}tr:last-child td{border-bottom:none}
.note-cell{display:flex;align-items:center;gap:12px}.note-pdf{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;background:#fef2f2;color:#ef4444}.note-info .name{font-weight:600;color:#111827;font-size:13px;line-height:1.3}.note-info .subj{font-size:11px;color:#9ca3af;margin-top:2px}
.badge{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block}.badge-published{background:#f0fdf4;color:#16a34a}.badge-draft{background:#fffbeb;color:#d97706}
.actions-cell{display:flex;gap:4px;flex-wrap:nowrap}.act-btn{padding:5px 10px;border-radius:6px;font-size:11px;font-weight:600;border:none;transition:all .15s;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:3px}.act-btn.view{background:#eff6ff;color:#2563eb}.act-btn.view:hover{background:#2563eb;color:white}.act-btn.edit{background:#fff7ed;color:#ea580c}.act-btn.edit:hover{background:#ea580c;color:white}.act-btn.pub{background:#f0fdf4;color:#16a34a}.act-btn.pub:hover{background:#16a34a;color:white}.act-btn.unpub{background:#fffbeb;color:#d97706}.act-btn.unpub:hover{background:#d97706;color:white}.act-btn.del{background:#fef2f2;color:#ef4444}.act-btn.del:hover{background:#ef4444;color:white}
.pagination{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-top:1px solid #f3f4f6;flex-wrap:wrap;gap:12px}.pagination-info{font-size:13px;color:#6b7280}.pagination-links{display:flex;gap:4px}.pagination-links a,.pagination-links span{padding:6px 12px;border-radius:8px;font-size:13px;font-weight:500;border:1px solid #e5e7eb;color:#374151;text-decoration:none}.pagination-links a:hover{background:#f3f4f6}.pagination-links .active{background:#2563eb;color:white;border-color:#2563eb}
.empty-state{text-align:center;padding:60px 20px;color:#9ca3af}.empty-state .e-icon{font-size:48px;margin-bottom:12px;display:block}.empty-state h3{font-size:16px;font-weight:700;color:#6b7280;margin-bottom:4px}.empty-state p{font-size:13px;margin-bottom:20px}
.modal-bg{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:999;display:none;align-items:center;justify-content:center;padding:20px}.modal-bg.show{display:flex}.modal{background:white;border-radius:16px;max-width:460px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.2)}.modal-head{padding:20px 24px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between}.modal-head h3{font-size:17px;font-weight:700}.modal-x{background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;padding:4px}.modal-x:hover{color:#374151}.modal-body{padding:24px}.modal-body p{font-size:14px;color:#374151;line-height:1.6;margin-bottom:8px}.modal-body .bold{font-weight:700;color:#111827}.modal-foot{padding:16px 24px;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;gap:8px}
.toast{position:fixed;top:24px;right:24px;padding:14px 20px;border-radius:12px;font-size:13px;font-weight:600;z-index:9999;display:flex;align-items:center;gap:10px;animation:tIn .3s ease;box-shadow:0 8px 24px rgba(0,0,0,.12);max-width:400px}.toast.success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}.toast.error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}@keyframes tIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}}
.footer-admin{background:#111827;padding:24px 32px;text-align:center;font-size:13px;color:#6b7280;margin-top:auto}.footer-admin a{color:#60a5fa}.mobile-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:190}.mobile-overlay.show{display:block}
@media(max-width:1024px){.sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}.mobile-overlay.show{display:block}.main-content{margin-left:0}.sidebar-toggle{display:flex}.stats-grid{grid-template-columns:repeat(2,1fr)}.filter-row{flex-direction:column}.filter-group{min-width:auto}}
@media(max-width:768px){.topbar{padding:0 16px;height:56px}.page-content{padding:20px 16px}.stats-grid{grid-template-columns:1fr 1fr;gap:12px}.stat-card{padding:16px;gap:12px}.stat-info h3{font-size:22px}.admin-chip .admin-name,.admin-chip .admin-role{display:none}.admin-chip{padding:4px}.page-header{flex-direction:column;align-items:flex-start}}
@media(max-width:480px){.search-box{display:none}.stats-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<aside class="sidebar" id="sidebar">
<div class="sidebar-header"><a href="dashboard.php" class="logo">Engi<span>Hub</span></a><span class="badge-admin">ADMIN</span></div>
<nav class="sidebar-nav">
<div class="nav-label">Overview</div><a href="dashboard.php"><span class="nav-icon">&#127968;</span>Dashboard</a>
<div class="nav-label">Management</div><a href="students.php"><span class="nav-icon">&#127891;</span>Students</a><a href="notes.php" class="active"><span class="nav-icon">&#128218;</span>Notes Management</a><a href="syllabus.php"><span class="nav-icon">&#128209;</span>Syllabus Management</a><a href="pyq.php"><span class="nav-icon">&#128196;</span>PYQ Management</a><a href="practicals.php"><span class="nav-icon">&#128300;</span>Practical Management</a>
<div class="nav-label">Resources</div><a href="coding.php"><span class="nav-icon">&#128187;</span>Coding Resources</a><a href="projects.php"><span class="nav-icon">&#128640;</span>Projects Management</a><a href="placement.php"><span class="nav-icon">&#127919;</span>Placement Resources</a>
<div class="nav-label">Communication</div><a href="notices.php"><span class="nav-icon">&#128227;</span>Notices</a><a href="messages.php"><span class="nav-icon">&#128172;</span>Messages<?php if($unreadMsgs>0):?><span style="background:#ef4444;color:white;font-size:9px;font-weight:700;padding:2px 7px;border-radius:20px;margin-left:auto"><?php echo $unreadMsgs;?></span><?php endif;?></a>
<div class="nav-label">System</div><a href="settings.php"><span class="nav-icon">&#9881;</span>Website Settings</a><a href="profile.php"><span class="nav-icon">&#128100;</span>Admin Profile</a>
</nav>
<div class="sidebar-footer"><a href="logout.php">&#10148; Logout</a></div>
</aside>
<div class="mobile-overlay" id="mobileOverlay" onclick="closeSidebar()"></div>
<main class="main-content">
<header class="topbar">
<div class="topbar-left"><button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button><div class="search-box"><span class="s-icon">&#128269;</span><input type="text" id="globalSearch" placeholder="Search notes..." value="<?php echo htmlspecialchars($_GET['search']??''); ?>" onkeydown="if(event.key==='Enter')applyFilters()"></div></div>
<div class="topbar-right">
<div class="admin-chip" onclick="toggleAdminDD()" id="adminChip"><div class="admin-avatar"><?php echo strtoupper(substr($adminName,0,1));?></div><div><div class="admin-name"><?php echo htmlspecialchars($adminName);?></div><div class="admin-role">Administrator</div></div></div>
<div class="admin-dropdown" id="adminDD"><a href="profile.php">&#128100; My Profile</a><a href="settings.php">&#9881; Settings</a><div class="dd-div"></div><a href="logout.php" style="color:#ef4444">&#10148; Logout</a></div>
</div>
</header>
<div class="page-content">
<?php if($flash):?><div class="toast <?php echo $flashType;?>" id="flashToast"><?php echo $flashType==='success'?'&#10003;':'&#9888;';?> <?php echo htmlspecialchars($flash);?></div><?php endif;?>
<div class="page-header"><div><h1>Notes Management</h1><p class="sub">Manage all engineering study notes and resources.</p></div><div class="page-header-right"><a href="add-note.php" class="btn btn-primary">+ Add New Note</a></div></div>
<div class="stats-grid">
<div class="stat-card"><div class="stat-icon blue">&#128218;</div><div class="stat-info"><h3><?php echo number_format($totalNotes);?></h3><p>Total Notes</p></div></div>
<div class="stat-card"><div class="stat-icon green">&#9989;</div><div class="stat-info"><h3><?php echo number_format($publishedNotes);?></h3><p>Published Notes</p></div></div>
<div class="stat-card"><div class="stat-icon amber">&#128221;</div><div class="stat-info"><h3><?php echo number_format($draftNotes);?></h3><p>Draft Notes</p></div></div>
<div class="stat-card"><div class="stat-icon cyan">&#11015;</div><div class="stat-info"><h3><?php echo number_format($totalDownloads);?></h3><p>Total Downloads</p></div></div>
</div>
<div class="filter-bar"><div class="filter-row">
<div class="filter-group"><label>Search</label><input type="text" id="fSearch" placeholder="Title, subject, tags..." value="<?php echo htmlspecialchars($_GET['search']??''); ?>"></div>
<div class="filter-group"><label>Branch</label><select id="fBranch"><option value="">All Branches</option><?php foreach(['CSE','ECE','ME','CE','EE'] as $b):?><option value="<?php echo $b;?>" <?php echo ($_GET['branch']??'')===$b?'selected':'';?>><?php echo $b;?></option><?php endforeach;?></select></div>
<div class="filter-group"><label>Semester</label><select id="fSemester"><option value="">All Semesters</option><?php for($i=1;$i<=8;$i++):?><option value="<?php echo $i;?>" <?php echo ($_GET['semester']??'')===(string)$i?'selected':'';?>>Sem <?php echo $i;?></option><?php endfor;?></select></div>
<div class="filter-group"><label>Subject</label><select id="fSubject"><option value="">All Subjects</option><?php foreach($subjects as $sub):?><option value="<?php echo htmlspecialchars($sub);?>" <?php echo ($_GET['subject']??'')===$sub?'selected':'';?>><?php echo htmlspecialchars($sub);?></option><?php endforeach;?></select></div>
<div class="filter-group"><label>Status</label><select id="fStatus"><option value="">All Status</option><option value="published" <?php echo ($_GET['status']??'')==='published'?'selected':'';?>>Published</option><option value="draft" <?php echo ($_GET['status']??'')==='draft'?'selected':'';?>>Draft</option></select></div>
<div class="filter-actions"><button class="btn btn-primary btn-sm" onclick="applyFilters()">Apply</button><a href="notes.php" class="btn btn-outline btn-sm">Clear</a></div>
</div></div>
<div class="card-panel"><div class="table-wrap"><table>
<thead><tr><th>Note</th><th>Branch</th><th>Sem</th><th>Status</th><th>Downloads</th><th>Date</th><th>Actions</th></tr></thead>
<tbody>
<?php if($notesResult && $notesResult->num_rows > 0): while($row = $notesResult->fetch_assoc()):?>
<tr>
<td><div class="note-cell"><div class="note-pdf">&#128196;</div><div class="note-info"><div class="name"><?php echo htmlspecialchars($row['title']);?></div><div class="subj"><?php echo htmlspecialchars($row['subject']);?></div></div></div></td>
<td><?php echo htmlspecialchars($row['branch']);?></td>
<td>Sem <?php echo $row['semester'];?></td>
<td><span class="badge badge-<?php echo $row['status'];?>"><?php echo ucfirst($row['status']);?></span></td>
<td><?php echo number_format($row['downloads_count']);?></td>
<td><?php echo date('d M Y', strtotime($row['created_at']));?></td>
<td><div class="actions-cell">
<a href="notes.php?view=<?php echo $row['id'];?>" class="act-btn view">&#128065;</a>
<a href="edit-note.php?id=<?php echo $row['id'];?>" class="act-btn edit">&#9998;</a>
<?php if($row['status']==='draft'):?><a href="note-action.php?id=<?php echo $row['id'];?>&action=publish" class="act-btn pub" title="Publish">&#9654;</a><?php else:?><a href="note-action.php?id=<?php echo $row['id'];?>&action=unpublish" class="act-btn unpub" title="Unpublish">&#9646;&#9646;</a><?php endif;?>
<button class="act-btn del" onclick="openDeleteModal(<?php echo $row['id'];?>,'<?php echo htmlspecialchars(addslashes($row['title']),ENT_QUOTES);?>')" title="Delete">&#128465;</button>
</div></td>
</tr>
<?php endwhile; else:?>
<tr><td colspan="7"><div class="empty-state"><span class="e-icon">&#128218;</span><h3>No notes found</h3><p>Try adjusting your search or filters.</p><a href="notes.php" class="btn btn-outline btn-sm">Clear Filters</a></div></td></tr>
<?php endif;?>
</tbody></table></div>
<?php if($totalPages > 1):?><div class="pagination"><div class="pagination-info">Showing <?php echo $offset+1;?>-<?php echo min($offset+$perPage,$totalRows);?> of <?php echo $totalRows;?> notes</div><div class="pagination-links">
<?php if($page>1):?><a href="notes.php<?php echo http_build_query(array_merge($_GET,['page'=>$page-1]));?>">&#8592; Prev</a><?php endif;?>
<?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++):?><a href="notes.php<?php echo http_build_query(array_merge($_GET,['page'=>$i]));?>" class="<?php echo $i===$page?'active':'';?>"><?php echo $i;?></a><?php endfor;?>
<?php if($page<$totalPages):?><a href="notes.php<?php echo http_build_query(array_merge($_GET,['page'=>$page+1]));?>">Next &#8594;</a><?php endif;?>
</div></div><?php endif;?>
</div>
</div>
<div class="footer-admin">&copy; 2026 EngiHub Admin Panel &middot; <a href="../index.html">Back to Website</a></div>
</main>
<div class="modal-bg" id="deleteModal"><div class="modal"><div class="modal-head"><h3>Delete Note</h3><button class="modal-x" onclick="closeDeleteModal()">&times;</button></div><div class="modal-body"><p>Are you sure you want to delete <span class="bold" id="delNoteTitle"></span>?</p><p style="color:#ef4444;font-size:13px">&#9888; This action cannot be undone. The associated PDF file will also be permanently removed.</p></div><div class="modal-foot"><button class="btn btn-outline btn-sm" onclick="closeDeleteModal()">Cancel</button><a href="#" id="delNoteLink" class="btn btn-danger btn-sm">Delete</a></div></div></div>
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('mobileOverlay').classList.toggle('show')}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('mobileOverlay').classList.remove('show')}
function toggleAdminDD(){document.getElementById('adminDD').classList.toggle('show')}
document.addEventListener('click',function(e){var ac=document.getElementById('adminChip'),dd=document.getElementById('adminDD');if(dd.classList.contains('show')&&!dd.contains(e.target)&&!ac.contains(e.target))dd.classList.remove('show');});
function openDeleteModal(id,title){document.getElementById('delNoteTitle').textContent=title;document.getElementById('delNoteLink').href='delete-note.php?id='+id;document.getElementById('deleteModal').classList.add('show')}
function closeDeleteModal(){document.getElementById('deleteModal').classList.remove('show')}
function applyFilters(){var s=document.getElementById('fSearch').value,b=document.getElementById('fBranch').value,se=document.getElementById('fSemester').value,su=document.getElementById('fSubject').value,st=document.getElementById('fStatus').value;var p=new URLSearchParams();if(s)p.set('search',s);if(b)p.set('branch',b);if(se)p.set('semester',se);if(su)p.set('subject',su);if(st)p.set('status',st);window.location.href='notes.php?'+p.toString()}
var ft=document.getElementById('flashToast');if(ft)setTimeout(function(){ft.remove()},4000);
</script>
</body></html>

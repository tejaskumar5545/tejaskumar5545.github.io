<?php
require_once '../db.php';
requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    $_SESSION['note_flash'] = 'Invalid note ID specified.';
    $_SESSION['note_flash_type'] = 'error';
    header('Location: notes.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM notes WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$note = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$note) {
    $_SESSION['note_flash'] = 'Note not found. It may have been deleted.';
    $_SESSION['note_flash_type'] = 'error';
    header('Location: notes.php');
    exit;
}

$error = '';
$form = [
    'title'       => $note['title'],
    'description' => $note['description'],
    'branch'      => $note['branch'],
    'semester'    => $note['semester'],
    'subject'     => $note['subject'],
    'tags'        => $note['tags'],
    'status'      => $note['status'],
];
$branches = ['CSE', 'ECE', 'ME', 'CE', 'EE'];
$maxSize = 10 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid or expired security token. Please try again.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $branch      = trim($_POST['branch'] ?? '');
        $semester    = intval($_POST['semester'] ?? 0);
        $subject     = trim($_POST['subject'] ?? '');
        $tags        = trim($_POST['tags'] ?? '');
        $status      = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';

        $form = [
            'title'       => $title,
            'description' => $description,
            'branch'      => $branch,
            'semester'    => $semester,
            'subject'     => $subject,
            'tags'        => $tags,
            'status'      => $status,
        ];

        if ($title === '' || $branch === '' || $semester < 1 || $semester > 8 || $subject === '') {
            $error = 'Title, Branch, Semester and Subject are required fields.';
        } else {
            $newFile = null;
            if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $f = $_FILES['pdf_file'];
                if ($f['error'] !== UPLOAD_ERR_OK) {
                    $error = 'File upload failed (error code ' . $f['error'] . '). Please try again.';
                } elseif (strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'pdf') {
                    $error = 'Only PDF files are allowed.';
                } elseif ($f['size'] > $maxSize) {
                    $error = 'File size must not exceed 10 MB.';
                } else {
                    $dir = '../uploads/notes/';
                    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
                    $base = pathinfo($f['name'], PATHINFO_FILENAME);
                    $safe = preg_replace('/[^A-Za-z0-9_-]/', '_', substr($base, 0, 80));
                    $uniqueName = time() . '_' . bin2hex(random_bytes(4)) . '_' . ($safe !== '' ? $safe : 'note') . '.pdf';
                    if (move_uploaded_file($f['tmp_name'], $dir . $uniqueName)) {
                        $newFile = ['name' => $f['name'], 'path' => $uniqueName, 'size' => $f['size']];
                    } else {
                        $error = 'Could not save the uploaded file. Check folder permissions.';
                    }
                }
            }

            if ($error === '') {
                if ($newFile) {
                    @unlink('../uploads/notes/' . $note['file_path']);
                    $stmt = $conn->prepare("UPDATE notes SET title=?, description=?, branch=?, semester=?, subject=?, tags=?, file_name=?, file_path=?, file_size=?, status=? WHERE id=?");
                    $stmt->bind_param('sssissssssi', $title, $description, $branch, $semester, $subject, $tags, $newFile['name'], $newFile['path'], $newFile['size'], $status, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE notes SET title=?, description=?, branch=?, semester=?, subject=?, tags=?, status=? WHERE id=?");
                    $stmt->bind_param('sssisssi', $title, $description, $branch, $semester, $subject, $tags, $status, $id);
                }
                $stmt->execute();
                $stmt->close();
                $_SESSION['note_flash'] = 'Note "' . $title . '" updated successfully!';
                $_SESSION['note_flash_type'] = 'success';
                header('Location: notes.php');
                exit;
            }
        }
    }
}

$flash = ''; $flashType = 'success';
if (isset($_SESSION['note_flash'])) { $flash = $_SESSION['note_flash']; unset($_SESSION['note_flash']); }
if (isset($_SESSION['note_flash_type'])) { $flashType = $_SESSION['note_flash_type']; unset($_SESSION['note_flash_type']); }
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$unreadMsgs = 0;
$mr = $conn->query("SELECT COUNT(*) as c FROM contact_messages");
if ($mr) $unreadMsgs = $mr->fetch_assoc()['c'];

$currentSizeKB = round(($note['file_size'] ?: 0) / 1024, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Edit Note - EngiHub Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex}a{text-decoration:none;color:inherit}button{cursor:pointer;font-family:inherit}
.sidebar{width:260px;background:#0f172a;color:white;height:100vh;position:fixed;top:0;left:0;z-index:200;display:flex;flex-direction:column;transition:transform .3s ease}.sidebar-header{padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:12px}.sidebar-header .logo{font-size:22px;font-weight:800;color:#60a5fa}.sidebar-header .logo span{color:white}.sidebar-header .badge-admin{font-size:9px;background:#2563eb;color:white;padding:2px 8px;border-radius:20px;font-weight:700;letter-spacing:.5px}.sidebar-nav{flex:1;overflow-y:auto;padding:8px 0}.sidebar-nav .nav-label{font-size:10px;font-weight:700;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:1px;padding:16px 24px 6px}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:11px 24px;color:rgba(255,255,255,.5);font-size:13px;font-weight:500;transition:all .15s;border-left:3px solid transparent}.sidebar-nav a:hover{color:white;background:rgba(255,255,255,.04)}.sidebar-nav a.active{color:#60a5fa;background:rgba(96,165,250,.08);border-left-color:#60a5fa;font-weight:600}.sidebar-nav .nav-icon{width:20px;text-align:center;font-size:15px;flex-shrink:0}.sidebar-footer{padding:16px 24px;border-top:1px solid rgba(255,255,255,.06)}.sidebar-footer a{display:flex;align-items:center;gap:10px;padding:10px 0;font-size:13px;color:#ef4444;font-weight:600}.sidebar-footer a:hover{color:#f87171}
.main-content{flex:1;margin-left:260px;min-height:100vh;display:flex;flex-direction:column}.topbar{height:64px;background:white;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;padding:0 32px;position:sticky;top:0;z-index:150}.topbar-left{display:flex;align-items:center;gap:16px}.sidebar-toggle{display:none;background:none;border:none;font-size:22px;color:#4b5563;padding:6px;border-radius:8px}.sidebar-toggle:hover{background:#f3f4f6}.search-box{position:relative}.search-box input{width:280px;padding:9px 14px 9px 36px;border:1px solid #e5e7eb;border-radius:10px;font-size:13px;color:#111827;outline:none;background:#f9fafb}.search-box input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.08);background:white}.search-box .s-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:13px;pointer-events:none}.topbar-right{display:flex;align-items:center;gap:8px}.admin-chip{display:flex;align-items:center;gap:10px;padding:5px 14px 5px 5px;border-radius:50px;cursor:pointer}.admin-chip:hover{background:#f3f4f6}.admin-avatar{width:34px;height:34px;border-radius:50%;background:#2563eb;color:white;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0}.admin-name{font-size:13px;font-weight:600;color:#374151}.admin-role{font-size:11px;color:#9ca3af}.admin-dropdown{position:absolute;top:52px;right:24px;background:white;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.12);border:1px solid #f3f4f6;min-width:200px;padding:8px;display:none;z-index:160}.admin-dropdown.show{display:block}.admin-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;font-size:13px;color:#374151;border-radius:8px;width:100%}.admin-dropdown a:hover{background:#f5f7fb}.admin-dropdown .dd-div{height:1px;background:#f3f4f6;margin:4px 8px}
.page-content{padding:28px 32px;flex:1}.page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px}.page-header h1{font-size:24px;font-weight:800}.page-header .sub{font-size:13px;color:#6b7280;margin-top:2px}.page-header-right{display:flex;gap:8px;flex-wrap:wrap}
.btn{padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .2s;display:inline-flex;align-items:center;gap:6px;text-decoration:none}.btn-primary{background:#2563eb;color:white}.btn-primary:hover{background:#1d4ed8;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.3)}.btn-danger{background:#ef4444;color:white}.btn-danger:hover{background:#dc2626}.btn-outline{background:white;color:#374151;border:1px solid #e5e7eb}.btn-outline:hover{background:#f9fafb}.btn-sm{padding:6px 14px;font-size:12px;border-radius:7px}
.card-panel{background:white;border-radius:14px;box-shadow:0 1px 6px rgba(0,0,0,.04);border:1px solid #f3f4f6;overflow:hidden;margin-bottom:24px}
.section-head{display:flex;align-items:center;gap:12px;padding:18px 24px;border-bottom:1px solid #f3f4f6;background:#f9fafb}.section-head .sec-icon{width:36px;height:36px;border-radius:10px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}.section-head h2{font-size:15px;font-weight:700;color:#111827}.section-head p{font-size:12px;color:#9ca3af;margin-top:1px}.section-body{padding:24px}
.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}.form-group{display:flex;flex-direction:column;gap:6px}.form-group.full{grid-column:1/-1}.form-group label{font-size:12px;font-weight:700;color:#374151}.form-group label .req{color:#ef4444;margin-left:2px}.form-group input[type=text],.form-group input[type=number],.form-group select,.form-group textarea{padding:11px 14px;border:1px solid #e5e7eb;border-radius:10px;font-size:13px;color:#111827;outline:none;background:white;font-family:inherit;transition:border-color .15s,box-shadow .15s;width:100%}.form-group textarea{resize:vertical;min-height:110px;line-height:1.55}.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.08)}.form-group .hint{font-size:11px;color:#9ca3af}
.current-file{display:flex;align-items:center;gap:14px;padding:16px 18px;background:#f9fafb;border:1px dashed #d1d5db;border-radius:12px}.current-file .cf-icon{width:46px;height:46px;border-radius:10px;background:#fef2f2;color:#ef4444;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}.current-file .cf-info{flex:1;min-width:0}.current-file .cf-name{font-size:13px;font-weight:600;color:#111827;word-break:break-all}.current-file .cf-meta{font-size:11px;color:#9ca3af;margin-top:3px}.current-file .cf-meta b{color:#6b7280;font-weight:600}.current-file .cf-dl{margin-left:auto;flex-shrink:0}
.replace-toggle{display:flex;align-items:center;gap:10px;margin-top:16px;cursor:pointer;user-select:none;width:fit-content}.replace-toggle input{width:17px;height:17px;accent-color:#2563eb;cursor:pointer}.replace-toggle span{font-size:13px;font-weight:600;color:#374151}
.dropzone{display:none;margin-top:16px;border:2px dashed #c7d2fe;border-radius:14px;background:#f8faff;padding:38px 20px;text-align:center;cursor:pointer;transition:all .2s}.dropzone.show{display:block}.dropzone:hover,.dropzone.dragover{border-color:#2563eb;background:#eff6ff}.dropzone.dragover{box-shadow:0 0 0 4px rgba(37,99,235,.1)}.dropzone .dz-icon{width:56px;height:56px;margin:0 auto 12px;border-radius:50%;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;font-size:24px}.dropzone .dz-title{font-size:14px;font-weight:700;color:#111827}.dropzone .dz-title span{color:#2563eb;text-decoration:underline}.dropzone .dz-sub{font-size:12px;color:#9ca3af;margin-top:5px}
.file-info{display:none;margin-top:14px;align-items:center;gap:12px;padding:14px 16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px}.file-info.show{display:flex}.file-info .fi-icon{width:40px;height:40px;border-radius:10px;background:#dcfce7;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}.file-info .fi-info{flex:1;min-width:0}.file-info .fi-name{font-size:13px;font-weight:600;color:#166534;word-break:break-all}.file-info .fi-meta{font-size:11px;color:#16a34a;margin-top:2px}.file-info .fi-x{background:#dcfce7;border:none;color:#16a34a;width:28px;height:28px;border-radius:8px;font-size:14px;font-weight:700;flex-shrink:0}.file-info .fi-x:hover{background:#16a34a;color:white}
.radio-cards{display:grid;grid-template-columns:repeat(2,minmax(0,260px));gap:14px}.radio-card{position:relative;display:flex;gap:12px;align-items:flex-start;padding:16px;border:2px solid #e5e7eb;border-radius:12px;cursor:pointer;transition:all .15s}.radio-card:hover{border-color:#c7d2fe}.radio-card input{margin-top:2px;width:16px;height:16px;accent-color:#2563eb;cursor:pointer;flex-shrink:0}.radio-card.selected{border-color:#2563eb;background:#eff6ff}.radio-card .rc-title{font-size:13px;font-weight:700;color:#111827}.radio-card .rc-desc{font-size:11px;color:#6b7280;margin-top:3px;line-height:1.45}
.form-actions{display:flex;justify-content:flex-end;gap:10px;padding:20px 24px;border-top:1px solid #f3f4f6;background:#f9fafb;flex-wrap:wrap}
.alert-banner{display:flex;align-items:flex-start;gap:10px;padding:14px 18px;border-radius:12px;font-size:13px;font-weight:600;margin-bottom:20px}.alert-banner.error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.toast{position:fixed;top:24px;right:24px;padding:14px 20px;border-radius:12px;font-size:13px;font-weight:600;z-index:9999;display:flex;align-items:center;gap:10px;animation:tIn .3s ease;box-shadow:0 8px 24px rgba(0,0,0,.12);max-width:400px}.toast.success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}.toast.error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}@keyframes tIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}}
.footer-admin{background:#111827;padding:24px 32px;text-align:center;font-size:13px;color:#6b7280;margin-top:auto}.footer-admin a{color:#60a5fa}.mobile-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:190}.mobile-overlay.show{display:block}
@media(max-width:1024px){.sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}.mobile-overlay.show{display:block}.main-content{margin-left:0}.sidebar-toggle{display:flex}}
@media(max-width:768px){.topbar{padding:0 16px;height:56px}.page-content{padding:20px 16px}.admin-chip .admin-name,.admin-chip .admin-role{display:none}.admin-chip{padding:4px}.page-header{flex-direction:column;align-items:flex-start}.form-grid{grid-template-columns:1fr}.radio-cards{grid-template-columns:1fr}.section-body{padding:18px}}
@media(max-width:480px){.search-box{display:none}}
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
<div class="topbar-left"><button class="sidebar-toggle" onclick="toggleSidebar()">&#9776;</button><div class="search-box"><span class="s-icon">&#128269;</span><input type="text" id="globalSearch" placeholder="Search notes..." onkeydown="if(event.key==='Enter')window.location.href='notes.php?search='+encodeURIComponent(this.value)"></div></div>
<div class="topbar-right">
<div class="admin-chip" onclick="toggleAdminDD()" id="adminChip"><div class="admin-avatar"><?php echo strtoupper(substr($adminName,0,1));?></div><div><div class="admin-name"><?php echo htmlspecialchars($adminName);?></div><div class="admin-role">Administrator</div></div></div>
<div class="admin-dropdown" id="adminDD"><a href="profile.php">&#128100; My Profile</a><a href="settings.php">&#9881; Settings</a><div class="dd-div"></div><a href="logout.php" style="color:#ef4444">&#10148; Logout</a></div>
</div>
</header>
<div class="page-content">
<?php if($flash):?><div class="toast <?php echo $flashType;?>" id="flashToast"><?php echo $flashType==='success'?'&#10003;':'&#9888;';?> <?php echo htmlspecialchars($flash);?></div><?php endif;?>
<?php if($error):?><div class="alert-banner error">&#9888; <?php echo htmlspecialchars($error);?></div><?php endif;?>
<div class="page-header"><div><h1>Edit Note</h1><p class="sub">Update note details for "<?php echo htmlspecialchars($note['title']);?>".</p></div><div class="page-header-right"><a href="notes.php" class="btn btn-outline">&#8592; Back to List</a></div></div>

<form method="POST" action="edit-note.php?id=<?php echo $id;?>" enctype="multipart/form-data" id="editNoteForm">
<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken();?>">

<div class="card-panel">
<div class="section-head"><div class="sec-icon">&#128221;</div><div><h2>Basic Information</h2><p>Title, description and academic classification of the note.</p></div></div>
<div class="section-body"><div class="form-grid">
<div class="form-group full"><label>Note Title<span class="req">*</span></label><input type="text" name="title" maxlength="255" placeholder="e.g. Data Structures Unit 1 Complete Notes" value="<?php echo htmlspecialchars($form['title']);?>" required></div>
<div class="form-group full"><label>Description</label><textarea name="description" placeholder="Brief summary of what this note covers..." ><?php echo htmlspecialchars($form['description']);?></textarea><span class="hint">Optional. A short summary helps students find the right material.</span></div>
<div class="form-group"><label>Branch<span class="req">*</span></label><select name="branch" required><option value="">Select Branch</option><?php foreach($branches as $b):?><option value="<?php echo $b;?>" <?php echo $form['branch']===$b?'selected':'';?>><?php echo $b;?></option><?php endforeach;?></select></div>
<div class="form-group"><label>Semester<span class="req">*</span></label><select name="semester" required><option value="">Select Semester</option><?php for($i=1;$i<=8;$i++):?><option value="<?php echo $i;?>" <?php echo (string)$form['semester']===(string)$i?'selected':'';?>>Semester <?php echo $i;?></option><?php endfor;?></select></div>
<div class="form-group"><label>Subject<span class="req">*</span></label><input type="text" name="subject" maxlength="100" placeholder="e.g. Data Structures" value="<?php echo htmlspecialchars($form['subject']);?>" required></div>
<div class="form-group"><label>Tags</label><input type="text" name="tags" maxlength="255" placeholder="e.g. ds, algorithms, unit-1" value="<?php echo htmlspecialchars($form['tags']);?>"><span class="hint">Comma separated keywords for search.</span></div>
</div></div>
</div>

<div class="card-panel">
<div class="section-head"><div class="sec-icon">&#128196;</div><div><h2>File Upload</h2><p>Currently attached PDF. Tick "Replace PDF" to upload a new version.</p></div></div>
<div class="section-body">
<div class="current-file">
<div class="cf-icon">&#128196;</div>
<div class="cf-info"><div class="cf-name"><?php echo htmlspecialchars($note['file_name']);?></div><div class="cf-meta"><b><?php echo $currentSizeKB;?> KB</b> &middot; Uploaded <?php echo date('d M Y', strtotime($note['created_at']));?></div></div>
<a class="btn btn-outline btn-sm cf-dl" href="../uploads/notes/<?php echo htmlspecialchars($note['file_path']);?>" target="_blank" download="<?php echo htmlspecialchars($note['file_name']);?>">&#11015; Download</a>
</div>
<label class="replace-toggle"><input type="checkbox" id="replaceToggle" onchange="toggleReplace()"> <span>Replace PDF with a new file</span></label>
<div class="dropzone" id="dropzone" onclick="document.getElementById('pdfInput').click()">
<div class="dz-icon">&#11014;</div>
<div class="dz-title">Drag &amp; drop your PDF here, or <span>browse files</span></div>
<div class="dz-sub">Only PDF files are accepted &middot; Maximum size: 10 MB</div>
<input type="file" id="pdfInput" name="pdf_file" accept="application/pdf,.pdf" style="display:none">
</div>
<div class="file-info" id="fileInfo">
<div class="fi-icon">&#128196;</div>
<div class="fi-info"><div class="fi-name" id="fiName"></div><div class="fi-meta" id="fiMeta"></div></div>
<button type="button" class="fi-x" onclick="clearSelectedFile(event)">&times;</button>
</div>
</div>
</div>

<div class="card-panel">
<div class="section-head"><div class="sec-icon">&#128202;</div><div><h2>Publication Settings</h2><p>Control the visibility of this note on the student portal.</p></div></div>
<div class="section-body">
<div class="radio-cards">
<label class="radio-card" id="rcDraft"><input type="radio" name="status" value="draft" <?php echo $form['status']==='draft'?'checked':'';?> onchange="updateRadioCards()"><div><div class="rc-title">&#128221; Draft</div><div class="rc-desc">Hidden from students. Only visible inside the admin panel.</div></div></label>
<label class="radio-card" id="rcPublished"><input type="radio" name="status" value="published" <?php echo $form['status']==='published'?'checked':'';?> onchange="updateRadioCards()"><div><div class="rc-title">&#127760; Published</div><div class="rc-desc">Live on the website. Students can browse and download.</div></div></label>
</div>
</div>
</div>

<div class="card-panel" style="margin-bottom:0">
<div class="form-actions">
<a href="notes.php" class="btn btn-outline">Cancel</a>
<button type="submit" class="btn btn-primary">&#128190; Save Changes</button>
</div>
</div>
</form>
</div>
<div class="footer-admin">&copy; 2026 EngiHub Admin Panel &middot; <a href="../index.html">Back to Website</a></div>
</main>
<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('mobileOverlay').classList.toggle('show')}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('mobileOverlay').classList.remove('show')}
function toggleAdminDD(){document.getElementById('adminDD').classList.toggle('show')}
document.addEventListener('click',function(e){var ac=document.getElementById('adminChip'),dd=document.getElementById('adminDD');if(dd.classList.contains('show')&&!dd.contains(e.target)&&!ac.contains(e.target))dd.classList.remove('show');});
var ft=document.getElementById('flashToast');if(ft)setTimeout(function(){ft.remove()},4000);

var MAX_SIZE=10*1024*1024;
var dz=document.getElementById('dropzone'),input=document.getElementById('pdfInput'),fi=document.getElementById('fileInfo');

function toggleReplace(){
var on=document.getElementById('replaceToggle').checked;
dz.classList.toggle('show',on);
if(!on){input.value='';fi.classList.remove('show');}
}

function fmtSize(bytes){
if(bytes>=1024*1024)return (bytes/(1024*1024)).toFixed(2)+' MB';
return Math.max(1,Math.round(bytes/1024))+' KB';
}

function handleFile(file){
if(!file)return;
if(file.type!=='application/pdf'||!/\.pdf$/i.test(file.name)){
alert('Invalid file: only PDF documents are allowed.');
input.value='';fi.classList.remove('show');return;
}
if(file.size>MAX_SIZE){
alert('File too large: maximum allowed size is 10 MB.');
input.value='';fi.classList.remove('show');return;
}
document.getElementById('fiName').textContent=file.name;
document.getElementById('fiMeta').textContent=fmtSize(file.size)+' \u00b7 Ready to replace current file';
fi.classList.add('show');
}

function clearSelectedFile(e){
e.stopPropagation();
input.value='';fi.classList.remove('show');
}

input.addEventListener('change',function(){handleFile(this.files[0])});
['dragenter','dragover'].forEach(function(ev){dz.addEventListener(ev,function(e){e.preventDefault();e.stopPropagation();dz.classList.add('dragover')})});
['dragleave','drop'].forEach(function(ev){dz.addEventListener(ev,function(e){e.preventDefault();e.stopPropagation();dz.classList.remove('dragover')})});
dz.addEventListener('drop',function(e){
var files=e.dataTransfer.files;
if(files.length){input.files=files;handleFile(files[0]);}
});

function updateRadioCards(){
document.getElementById('rcDraft').classList.toggle('selected',document.querySelector('input[name=status][value=draft]').checked);
document.getElementById('rcPublished').classList.toggle('selected',document.querySelector('input[name=status][value=published]').checked);
}
updateRadioCards();

document.getElementById('editNoteForm').addEventListener('submit',function(e){
var t=this.title.value.trim(),b=this.branch.value,s=this.semester.value,su=this.subject.value.trim();
if(!t||!b||!s||!su){e.preventDefault();alert('Please fill in all required fields: Title, Branch, Semester and Subject.');}
});
</script>
</body></html>

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

$errors = [];
$old = ['title'=>'','description'=>'','branch'=>'','semester'=>'','subject'=>'','tags'=>'','status'=>'draft'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid or expired security token. Please refresh the page and try again.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $branch = trim($_POST['branch'] ?? '');
        $semester = intval($_POST['semester'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $tags = trim($_POST['tags'] ?? '');
        $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';

        $old = ['title'=>$title,'description'=>$description,'branch'=>$branch,'semester'=>$_POST['semester']??'','subject'=>$subject,'tags'=>$tags,'status'=>$status];

        if ($title === '') { $errors[] = 'Title is required.'; }
        elseif (mb_strlen($title) > 255) { $errors[] = 'Title must not exceed 255 characters.'; }
        if ($branch === '' || !in_array($branch, ['CSE','ECE','ME','CE','EE'], true)) { $errors[] = 'Please select a valid branch.'; }
        if ($semester < 1 || $semester > 8) { $errors[] = 'Please select a valid semester (1-8).'; }
        if ($subject === '') { $errors[] = 'Subject is required.'; }
        elseif (mb_strlen($subject) > 100) { $errors[] = 'Subject must not exceed 100 characters.'; }
        if (mb_strlen($tags) > 255) { $errors[] = 'Tags must not exceed 255 characters.'; }

        $maxSize = 10 * 1024 * 1024;
        $fileValid = false;

        if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Please choose a PDF file to upload.';
        } elseif ($_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed. Please try again.';
        } elseif ($_FILES['pdf_file']['size'] > $maxSize) {
            $errors[] = 'File is too large. Maximum allowed size is 10MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['pdf_file']['tmp_name']);
            if ($mime !== 'application/pdf') {
                $errors[] = 'Invalid file type. Only PDF files are allowed.';
            } else {
                $fileValid = true;
            }
        }

        if (empty($errors) && $fileValid) {
            $uploadDir = __DIR__ . '/uploads/notes';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

            $newName = bin2hex(random_bytes(16)) . '.pdf';
            $destPath = $uploadDir . '/' . $newName;

            if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $destPath)) {
                $errors[] = 'Could not save the uploaded file. Please check folder permissions and try again.';
            } else {
                $originalName = $_FILES['pdf_file']['name'];
                $fileSize = $_FILES['pdf_file']['size'];
                $filePath = 'uploads/notes/' . $newName;
                $uploadedBy = $_SESSION['admin_id'];
                $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;

                $stmt = $conn->prepare("INSERT INTO notes (title, description, branch, semester, subject, tags, file_name, file_path, file_size, status, uploaded_by, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssissssisis', $title, $description, $branch, $semester, $subject, $tags, $originalName, $filePath, $fileSize, $status, $uploadedBy, $publishedAt);

                if ($stmt->execute()) {
                    $stmt->close();
                    $_SESSION['note_flash'] = $status === 'published' ? 'Note "' . $title . '" published successfully.' : 'Note "' . $title . '" saved as draft.';
                    $_SESSION['note_flash_type'] = 'success';
                    header('Location: notes.php');
                    exit;
                }

                $stmt->close();
                @unlink($destPath);
                $errors[] = 'Database error: could not save the note. Please try again.';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Add New Note - EngiHub Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex}a{text-decoration:none;color:inherit}button{cursor:pointer;font-family:inherit}
.sidebar{width:260px;background:#0f172a;color:white;height:100vh;position:fixed;top:0;left:0;z-index:200;display:flex;flex-direction:column;transition:transform .3s ease}.sidebar-header{padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:12px}.sidebar-header .logo{font-size:22px;font-weight:800;color:#60a5fa}.sidebar-header .logo span{color:white}.sidebar-header .badge-admin{font-size:9px;background:#2563eb;color:white;padding:2px 8px;border-radius:20px;font-weight:700;letter-spacing:.5px}.sidebar-nav{flex:1;overflow-y:auto;padding:8px 0}.sidebar-nav .nav-label{font-size:10px;font-weight:700;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:1px;padding:16px 24px 6px}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:11px 24px;color:rgba(255,255,255,.5);font-size:13px;font-weight:500;transition:all .15s;border-left:3px solid transparent}.sidebar-nav a:hover{color:white;background:rgba(255,255,255,.04)}.sidebar-nav a.active{color:#60a5fa;background:rgba(96,165,250,.08);border-left-color:#60a5fa;font-weight:600}.sidebar-nav .nav-icon{width:20px;text-align:center;font-size:15px;flex-shrink:0}.sidebar-footer{padding:16px 24px;border-top:1px solid rgba(255,255,255,.06)}.sidebar-footer a{display:flex;align-items:center;gap:10px;padding:10px 0;font-size:13px;color:#ef4444;font-weight:600}.sidebar-footer a:hover{color:#f87171}
.main-content{flex:1;margin-left:260px;min-height:100vh;display:flex;flex-direction:column}.topbar{height:64px;background:white;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;padding:0 32px;position:sticky;top:0;z-index:150}.topbar-left{display:flex;align-items:center;gap:16px}.sidebar-toggle{display:none;background:none;border:none;font-size:22px;color:#4b5563;padding:6px;border-radius:8px}.sidebar-toggle:hover{background:#f3f4f6}.search-box{position:relative}.search-box input{width:280px;padding:9px 14px 9px 36px;border:1px solid #e5e7eb;border-radius:10px;font-size:13px;color:#111827;outline:none;background:#f9fafb}.search-box input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.08);background:white}.search-box .s-icon{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:13px;pointer-events:none}.topbar-right{display:flex;align-items:center;gap:8px}.admin-chip{display:flex;align-items:center;gap:10px;padding:5px 14px 5px 5px;border-radius:50px;cursor:pointer}.admin-chip:hover{background:#f3f4f6}.admin-avatar{width:34px;height:34px;border-radius:50%;background:#2563eb;color:white;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0}.admin-name{font-size:13px;font-weight:600;color:#374151}.admin-role{font-size:11px;color:#9ca3af}.admin-dropdown{position:absolute;top:52px;right:24px;background:white;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.12);border:1px solid #f3f4f6;min-width:200px;padding:8px;display:none;z-index:160}.admin-dropdown.show{display:block}.admin-dropdown a{display:flex;align-items:center;gap:10px;padding:10px 14px;font-size:13px;color:#374151;border-radius:8px;width:100%}.admin-dropdown a:hover{background:#f5f7fb}.admin-dropdown .dd-div{height:1px;background:#f3f4f6;margin:4px 8px}
.page-content{padding:28px 32px;flex:1}.page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px}.page-header h1{font-size:24px;font-weight:800}.page-header .sub{font-size:13px;color:#6b7280;margin-top:2px}.page-header-right{display:flex;gap:8px;flex-wrap:wrap}
.btn{padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .2s;display:inline-flex;align-items:center;gap:6px;text-decoration:none}.btn-primary{background:#2563eb;color:white}.btn-primary:hover{background:#1d4ed8;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.3)}.btn-danger{background:#ef4444;color:white}.btn-danger:hover{background:#dc2626}.btn-outline{background:white;color:#374151;border:1px solid #e5e7eb}.btn-outline:hover{background:#f9fafb}.btn-sm{padding:6px 14px;font-size:12px;border-radius:7px}
.card-panel{background:white;border-radius:14px;box-shadow:0 1px 6px rgba(0,0,0,.04);border:1px solid #f3f4f6;overflow:hidden;margin-bottom:24px}
.toast{position:fixed;top:24px;right:24px;padding:14px 20px;border-radius:12px;font-size:13px;font-weight:600;z-index:9999;display:flex;align-items:center;gap:10px;animation:tIn .3s ease;box-shadow:0 8px 24px rgba(0,0,0,.12);max-width:400px}.toast.success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}.toast.error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}@keyframes tIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}}
.footer-admin{background:#111827;padding:24px 32px;text-align:center;font-size:13px;color:#6b7280;margin-top:auto}.footer-admin a{color:#60a5fa}.mobile-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:190}.mobile-overlay.show{display:block}
.card-head{padding:18px 24px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between}.card-head h3{font-size:15px;font-weight:700;color:#111827}.card-head .ch-hint{font-size:11px;color:#9ca3af;font-weight:500}
.form-card{padding:24px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px 22px}.form-group.full{grid-column:1/-1}.form-group label{display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:7px}.req{color:#ef4444}
.form-control{width:100%;padding:10px 14px;border:1px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;color:#111827;background:white;outline:none;transition:border-color .15s,box-shadow .15s}.form-control:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}.form-control::placeholder{color:#9ca3af}select.form-control{cursor:pointer}textarea.form-control{resize:vertical;min-height:80px;line-height:1.55}
.hint{font-size:12px;color:#9ca3af;margin-top:10px;display:flex;align-items:center;gap:6px}
.drop-zone{border:2px dashed #cbd5e1;border-radius:12px;background:#f9fafb;padding:44px 20px;text-align:center;cursor:pointer;transition:all .2s}.drop-zone:hover{border-color:#93c5fd;background:#f8fafc}.drop-zone.dragover{border-color:#2563eb;background:#eff6ff;transform:scale(1.01)}.dz-icon{font-size:38px;display:block;margin-bottom:10px}.dz-main{font-size:14px;font-weight:600;color:#374151}.dz-sub{font-size:12px;color:#9ca3af;margin-top:4px}
.file-info{display:none;align-items:center;gap:14px;border:1px solid #bbf7d0;background:#f0fdf4;border-radius:12px;padding:14px 18px;margin-top:14px}.fi-icon{width:42px;height:42px;border-radius:10px;background:#dcfce7;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}.fi-details{flex:1;min-width:0}.fi-name{font-size:13px;font-weight:700;color:#111827;word-break:break-all}.fi-size{font-size:12px;color:#6b7280;margin-top:2px}.fi-remove{background:#fef2f2;color:#ef4444;border:none;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;flex-shrink:0;transition:all .15s}.fi-remove:hover{background:#ef4444;color:white}
.file-error{display:none;margin-top:12px;font-size:12px;font-weight:600;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:9px 14px}
.radio-group{display:flex;gap:14px;flex-wrap:wrap}.radio-pill{display:flex;align-items:center;gap:12px;border:1.5px solid #e5e7eb;border-radius:12px;padding:14px 18px;cursor:pointer;transition:all .15s;flex:1;min-width:230px}.radio-pill:hover{border-color:#93c5fd}.radio-pill input{accent-color:#2563eb;width:17px;height:17px;cursor:pointer;flex-shrink:0}.radio-pill.selected{border-color:#2563eb;background:#eff6ff}.rp-title{display:block;font-size:13px;font-weight:700;color:#111827}.rp-desc{display:block;font-size:11px;color:#6b7280;margin-top:2px}
.form-actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;margin-bottom:24px}
.alert{display:flex;gap:10px;align-items:flex-start;padding:14px 18px;border-radius:12px;font-size:13px;font-weight:500;margin-bottom:20px;line-height:1.6}.alert-error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}.alert ul{margin-left:16px}
@media(max-width:1024px){.sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}.mobile-overlay.show{display:block}.main-content{margin-left:0}.sidebar-toggle{display:flex}}
@media(max-width:768px){.topbar{padding:0 16px;height:56px}.page-content{padding:20px 16px}.admin-chip .admin-name,.admin-chip .admin-role{display:none}.admin-chip{padding:4px}.page-header{flex-direction:column;align-items:flex-start}.form-grid{grid-template-columns:1fr}.form-card{padding:18px}.form-actions{flex-direction:column}.form-actions .btn{width:100%;justify-content:center}}
@media(max-width:480px){.search-box{display:none}.drop-zone{padding:30px 14px}}
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
<?php if(!empty($errors)):?><div class="alert alert-error"><span>&#9888;</span><ul><?php foreach($errors as $err):?><li><?php echo htmlspecialchars($err);?></li><?php endforeach;?></ul></div><?php endif;?>
<div class="page-header"><div><h1>Add New Note</h1><p class="sub">Upload a new study note to the EngiHub library.</p></div><div class="page-header-right"><a href="notes.php" class="btn btn-outline">&#8592; Back to List</a></div></div>

<form method="POST" action="add-note.php" enctype="multipart/form-data" id="noteForm">
<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken();?>">
<input type="hidden" name="status" id="statusInput" value="<?php echo $old['status']==='published'?'published':'draft';?>">

<div class="card-panel">
<div class="card-head"><h3>&#128218; Basic Information</h3><span class="ch-hint">Fields marked * are required</span></div>
<div class="form-card"><div class="form-grid">
<div class="form-group full"><label>Title <span class="req">*</span></label><input type="text" name="title" class="form-control" maxlength="255" placeholder="e.g. Complete Data Structures Handwritten Notes" value="<?php echo htmlspecialchars($old['title']);?>" required></div>
<div class="form-group full"><label>Description</label><textarea name="description" rows="3" class="form-control" placeholder="Brief description of the notes..."><?php echo htmlspecialchars($old['description']);?></textarea></div>
<div class="form-group"><label>Branch <span class="req">*</span></label><select name="branch" class="form-control" required><option value="">Select Branch</option><?php foreach(['CSE','ECE','ME','CE','EE'] as $b):?><option value="<?php echo $b;?>" <?php echo $old['branch']===$b?'selected':'';?>><?php echo $b;?></option><?php endforeach;?></select></div>
<div class="form-group"><label>Semester <span class="req">*</span></label><select name="semester" class="form-control" required><option value="">Select Semester</option><?php for($i=1;$i<=8;$i++):?><option value="<?php echo $i;?>" <?php echo $old['semester']===(string)$i?'selected':'';?>>Semester <?php echo $i;?></option><?php endfor;?></select></div>
<div class="form-group"><label>Subject <span class="req">*</span></label><input type="text" name="subject" class="form-control" maxlength="100" placeholder="e.g. Data Structures" value="<?php echo htmlspecialchars($old['subject']);?>" required></div>
<div class="form-group"><label>Tags</label><input type="text" name="tags" class="form-control" maxlength="255" placeholder="e.g. arrays, linked lists, trees" value="<?php echo htmlspecialchars($old['tags']);?>"></div>
</div></div>
</div>

<div class="card-panel">
<div class="card-head"><h3>&#128196; File Upload</h3><span class="ch-hint">PDF only</span></div>
<div class="form-card">
<div class="drop-zone" id="dropZone">
<span class="dz-icon">&#11015;</span>
<p class="dz-main">Drag &amp; drop your PDF here or click to browse</p>
<p class="dz-sub">Select a PDF document from your device</p>
</div>
<input type="file" name="pdf_file" id="pdfInput" accept=".pdf,application/pdf" hidden>
<div class="file-info" id="fileInfo">
<span class="fi-icon">&#128196;</span>
<div class="fi-details"><div class="fi-name" id="fileName"></div><div class="fi-size" id="fileSize"></div></div>
<button type="button" class="fi-remove" onclick="removeFile()">&times; Remove</button>
</div>
<div class="file-error" id="fileError"></div>
<p class="hint">&#8505; Maximum file size: 10MB. Only PDF files are allowed.</p>
</div>
</div>

<div class="card-panel">
<div class="card-head"><h3>&#127760; Publication Settings</h3></div>
<div class="form-card">
<div class="radio-group">
<label class="radio-pill" id="pillDraft"><input type="radio" name="status_choice" value="draft" onchange="setStatusChoice('draft')" <?php echo $old['status']!=='published'?'checked':'';?>><span><span class="rp-title">&#128221; Draft</span><span class="rp-desc">Hidden from students until published</span></span></label>
<label class="radio-pill" id="pillPublished"><input type="radio" name="status_choice" value="published" onchange="setStatusChoice('published')" <?php echo $old['status']==='published'?'checked':'';?>><span><span class="rp-title">&#9989; Published</span><span class="rp-desc">Visible to all students immediately</span></span></label>
</div>
</div>
</div>

<div class="form-actions">
<button type="submit" class="btn btn-outline" onclick="setStatusValue('draft')">&#128221; Save as Draft</button>
<button type="submit" class="btn btn-primary" onclick="setStatusValue('published')">&#128640; Publish Note</button>
<a href="notes.php" class="btn btn-outline">Cancel</a>
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
var dropZone=document.getElementById('dropZone'),pdfInput=document.getElementById('pdfInput'),fileInfo=document.getElementById('fileInfo'),fileError=document.getElementById('fileError');

['dragenter','dragover'].forEach(function(ev){dropZone.addEventListener(ev,function(e){e.preventDefault();e.stopPropagation();dropZone.classList.add('dragover')})});
['dragleave','drop'].forEach(function(ev){dropZone.addEventListener(ev,function(e){e.preventDefault();e.stopPropagation();dropZone.classList.remove('dragover')})});
dropZone.addEventListener('drop',function(e){if(e.dataTransfer.files.length){pdfInput.files=e.dataTransfer.files;handleFile(pdfInput.files[0])}});
dropZone.addEventListener('click',function(){pdfInput.click()});
pdfInput.addEventListener('change',function(){if(pdfInput.files.length)handleFile(pdfInput.files[0])});

function handleFile(file){
hideFileError();
var isPdf=file.type==='application/pdf'||/\.pdf$/i.test(file.name);
if(!isPdf){showFileError('Only PDF files are allowed. Please select a valid PDF document.');resetFile();return}
if(file.size>MAX_SIZE){showFileError('File is too large ('+formatSize(file.size)+'). Maximum allowed size is 10MB.');resetFile();return}
document.getElementById('fileName').textContent=file.name;
document.getElementById('fileSize').textContent=formatSize(file.size);
dropZone.style.display='none';fileInfo.style.display='flex';
}
function resetFile(){pdfInput.value='';dropZone.style.display='block';fileInfo.style.display='none'}
function removeFile(){resetFile();hideFileError()}
function showFileError(msg){fileError.textContent='\u26A0 '+msg;fileError.style.display='block'}
function hideFileError(){fileError.style.display='none'}
function formatSize(bytes){if(bytes<1024)return bytes+' B';if(bytes<1048576)return (bytes/1024).toFixed(1)+' KB';return (bytes/1048576).toFixed(2)+' MB'}

function setStatusValue(v){document.getElementById('statusInput').value=v}
function setStatusChoice(v){setStatusValue(v);updatePills(v)}
function updatePills(v){document.getElementById('pillDraft').classList.toggle('selected',v==='draft');document.getElementById('pillPublished').classList.toggle('selected',v==='published')}
updatePills(document.querySelector('input[name="status_choice"]:checked').value);

var formSubmitted=false;
document.getElementById('noteForm').addEventListener('submit',function(e){
if(formSubmitted){e.preventDefault();return}
formSubmitted=true;
var btns=this.querySelectorAll('button[type="submit"]');
for(var i=0;i<btns.length;i++){btns[i].disabled=true;btns[i].style.opacity='.6';btns[i].style.cursor='not-allowed'}
});
</script>
</body></html>

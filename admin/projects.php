<?php
require_once '../db.php';
requireAdmin();
$action=$_GET['action']??'list';
$id=intval($_GET['id']??0);
$msg='';
if(isset($_SESSION['admin_msg'])){$msg=$_SESSION['admin_msg'];unset($_SESSION['admin_msg']);}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';
    $title=sanitize($conn,$_POST['title']??'');
    $description=sanitize($conn,$_POST['description']??'');
    $semester=sanitize($conn,$_POST['semester']??'');
    $branch=sanitize($conn,$_POST['branch']??'');
    if($act==='add'||$act==='edit'){
        $pdf_name='';
        if(isset($_FILES['pdf_file'])&&$_FILES['pdf_file']['error']===UPLOAD_ERR_OK){
            $ext=strtolower(pathinfo($_FILES['pdf_file']['name'],PATHINFO_EXTENSION));
            if($ext==='pdf'){$pdf_name=time().'_'.preg_replace('/[^a-zA-Z0-9_.-]/','',$_FILES['pdf_file']['name']);$dir='../uploads/projects/';if(!is_dir($dir))mkdir($dir,0755,true);move_uploaded_file($_FILES['pdf_file']['tmp_name'],$dir.$pdf_name);}
        }
        if($act==='add'){
            if(empty($pdf_name)){$_SESSION['admin_msg']='Please select a PDF!';header("Location: projects.php?action=add");exit;}
            $stmt=$conn->prepare("INSERT INTO projects(title,description,semester,branch,pdf_file) VALUES(?,?,?,?,?)");
            $stmt->bind_param("sssss",$title,$description,$semester,$branch,$pdf_name);$stmt->execute();$stmt->close();
            $_SESSION['admin_msg']='Project added!';header("Location: projects.php");exit;
        }elseif($act==='edit'&&$id>0){
            if($pdf_name){$stmt=$conn->prepare("UPDATE projects SET title=?,description=?,semester=?,branch=?,pdf_file=? WHERE id=?");$stmt->bind_param("sssssi",$title,$description,$semester,$branch,$pdf_name,$id);}
            else{$stmt=$conn->prepare("UPDATE projects SET title=?,description=?,semester=?,branch=? WHERE id=?");$stmt->bind_param("ssssi",$title,$description,$semester,$branch,$id);}
            $stmt->execute();$stmt->close();$_SESSION['admin_msg']='Project updated!';header("Location: projects.php");exit;
        }
    }elseif($act==='delete'&&$id>0){
        $r=$conn->query("SELECT pdf_file FROM projects WHERE id=$id");if($r&&$r->num_rows>0){$row=$r->fetch_assoc();if(!empty($row['pdf_file']))@unlink('../uploads/projects/'.$row['pdf_file']);}
        $conn->query("DELETE FROM projects WHERE id=$id");$_SESSION['admin_msg']='Project deleted!';header("Location: projects.php");exit;
    }
}
$edit_row=null;if($action==='edit'&&$id>0){$r=$conn->query("SELECT * FROM projects WHERE id=$id");if($r&&$r->num_rows>0)$edit_row=$r->fetch_assoc();}
$items=$conn->query("SELECT * FROM projects ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><meta name="theme-color" content="#0f172a">
<title>Projects - EngiHub Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex}
.sidebar{width:260px;background:#0f172a;color:white;height:100vh;position:fixed;top:0;left:0;z-index:200;display:flex;flex-direction:column}.sidebar .logo-area{padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06)}.sidebar .logo-area h2{font-size:22px;font-weight:800;color:#60a5fa}.sidebar .logo-area h2 span{color:white}.sidebar .logo-area p{font-size:10px;background:#2563eb;color:white;padding:2px 8px;border-radius:20px;font-weight:700;display:inline-block;margin-top:6px}.sidebar nav{flex:1;overflow-y:auto;padding:8px 0}.sidebar nav .nl{font-size:10px;font-weight:700;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:1px;padding:14px 24px 4px}.sidebar nav a{display:flex;align-items:center;gap:12px;padding:11px 24px;color:rgba(255,255,255,.5);font-size:13px;font-weight:500;transition:all .15s;border-left:3px solid transparent}.sidebar nav a:hover{color:white;background:rgba(255,255,255,.04)}.sidebar nav a.active{color:#60a5fa;background:rgba(96,165,250,.08);border-left-color:#60a5fa;font-weight:600}.sidebar nav .ni{width:20px;text-align:center;font-size:15px;flex-shrink:0}.sidebar footer{padding:16px 24px;border-top:1px solid rgba(255,255,255,.06)}
.main{flex:1;margin-left:260px;padding:24px 32px}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h1{font-size:24px;font-weight:800}
.btn{padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;border:none;font-family:inherit;display:inline-flex;align-items:center;gap:6px;transition:all .15s}.btn-primary{background:#2563eb;color:white}.btn-primary:hover{background:#1d4ed8}.btn-danger{background:#ef4444;color:white}.btn-danger:hover{background:#dc2626}.btn-secondary{background:#f3f4f6;color:#374151}.btn-sm{padding:6px 14px;font-size:12px;border-radius:7px}
.alert{padding:12px 16px;border-radius:8px;font-size:14px;font-weight:500;margin-bottom:20px}.alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.card{background:white;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.06)}
table{width:100%;border-collapse:collapse}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid #f3f4f6;font-size:13px}th{font-weight:700;color:#6b7280;font-size:11px;text-transform:uppercase}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.form-group{margin-bottom:16px}.form-group.full{grid-column:1/-1}.form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}.form-group input,.form-group select,.form-group textarea{width:100%;padding:11px 14px;border:2px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;color:#111827;outline:none;transition:border-color .2s}.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.08)}.form-group textarea{resize:vertical;min-height:80px}
.mobile-menu{display:none;position:fixed;top:0;left:0;right:0;height:60px;background:#0f172a;z-index:200;align-items:center;justify-content:space-between;padding:0 20px}.mobile-menu h2{color:white;font-size:18px;font-weight:700}.mobile-menu button{background:none;border:none;color:white;font-size:24px;cursor:pointer}
@media(max-width:768px){.sidebar{display:none}.mobile-menu{display:flex}.main{margin-left:0;padding:80px 16px 24px}.form-grid{grid-template-columns:1fr}table{font-size:12px}th,td{padding:8px 6px}}
</style>
</head>
<body>
<div class="mobile-menu"><h2>EngiHub Admin</h2><button onclick="document.querySelector('.sidebar').style.display=document.querySelector('.sidebar').style.display==='flex'?'none':'flex'">&#9776;</button></div>
<div class="sidebar">
<div class="logo-area"><h2>Engi<span>Hub</span></h2><p>ADMIN</p></div>
<nav>
<div class="nl">Overview</div><a href="index.php"><span class="ni">&#127968;</span>Dashboard</a>
<div class="nl">Management</div><a href="students.php"><span class="ni">&#127891;</span>Students</a><a href="notes.php"><span class="ni">&#128218;</span>Notes</a><a href="syllabus.php"><span class="ni">&#128209;</span>Syllabus</a><a href="pyq.php"><span class="ni">&#128196;</span>PYQ</a><a href="practicals.php"><span class="ni">&#128300;</span>Practicals</a>
<div class="nl">Resources</div><a href="coding.php"><span class="ni">&#128187;</span>Coding</a><a href="projects.php" class="active"><span class="ni">&#128640;</span>Projects</a><a href="placement.php"><span class="ni">&#127919;</span>Placement</a>
<div class="nl">Communication</div><a href="notices.php"><span class="ni">&#128227;</span>Notices</a><a href="messages.php"><span class="ni">&#128172;</span>Messages</a>
<div class="nl">System</div><a href="settings.php"><span class="ni">&#9881;</span>Settings</a><a href="profile.php"><span class="ni">&#128100;</span>Profile</a>
</nav>
<footer><a href="../logout.php" style="color:#ef4444;font-size:13px;font-weight:600">&#10148; Logout</a></footer>
</div>
<div class="main">
<?php if($msg):?><div class="alert alert-success">&#10003; <?php echo $msg;?></div><?php endif;?>
<?php if($action==='add'||$action==='edit'):?>
<div class="page-header"><h1><?php echo $action==='add'?'&#128640; Add Project':'&#9998; Edit Project';?></h1><a href="projects.php" class="btn btn-secondary">&#8592; Back</a></div>
<div class="card">
<form method="POST" enctype="multipart/form-data"><input type="hidden" name="act" value="<?php echo $action;?>"><input type="hidden" name="id" value="<?php echo $id;?>">
<div class="form-grid">
<div class="form-group full"><label>Title *</label><input type="text" name="title" value="<?php echo htmlspecialchars($edit_row['title']??'');?>" required></div>
<div class="form-group"><label>Branch</label><select name="branch"><?php foreach(['Computer Science','Information Technology','Electronics','Electrical','Mechanical','Civil','Other'] as $b):?><option value="<?php echo $b;?>" <?php echo ($edit_row['branch']??'')===$b?'selected':'';?>><?php echo $b;?></option><?php endforeach;?></select></div>
<div class="form-group"><label>Semester</label><select name="semester"><?php for($i=1;$i<=8;$i++):?><option value="<?php echo $i;?>" <?php echo ($edit_row['semester']??'')==$i?'selected':'';?>>Semester <?php echo $i;?></option><?php endfor;?></select></div>
<div class="form-group full"><label>Description</label><textarea name="description"><?php echo htmlspecialchars($edit_row['description']??'');?></textarea></div>
<div class="form-group full"><label>PDF File <?php echo $action==='edit'?'(Leave empty to keep current)':'';?></label><input type="file" name="pdf_file" accept=".pdf"></div>
</div>
<div style="margin-top:16px"><button type="submit" class="btn btn-primary">&#10003; <?php echo $action==='add'?'Add Project':'Update';?></button></div>
</form>
</div>
<?php else:?>
<div class="page-header"><h1>&#128640; Projects</h1><a href="projects.php?action=add" class="btn btn-primary">+ Add Project</a></div>
<div class="card"><table><thead><tr><th>Title</th><th>Branch</th><th>Sem</th><th>Date</th><th>Actions</th></tr></thead><tbody>
<?php if($items&&$items->num_rows>0):while($row=$items->fetch_assoc()):?>
<tr><td style="font-weight:600"><?php echo htmlspecialchars($row['title']);?></td><td><?php echo htmlspecialchars($row['branch']);?></td><td><?php echo htmlspecialchars($row['semester']);?></td><td><?php echo date('d M Y',strtotime($row['created_at']));?></td>
<td><div style="display:flex;gap:4px"><a href="projects.php?action=edit&id=<?php echo $row['id'];?>" class="btn btn-primary btn-sm">Edit</a><form method="POST" style="display:inline"><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?php echo $row['id'];?>"><button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</button></form></div></td></tr>
<?php endwhile;else:?><tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:30px">No projects yet.</td></tr><?php endif;?>
</tbody></table></div>
<?php endif;?>
</div>
</body>
</html>

<?php
require_once '../db.php';
requireAdmin();
$msg='';
if(isset($_SESSION['admin_msg'])){$msg=$_SESSION['admin_msg'];unset($_SESSION['admin_msg']);}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $act=$_POST['act']??'';
    $id=intval($_POST['id']??0);
    if($act==='delete'&&$id>0){
        $conn->query("DELETE FROM contact_messages WHERE id=$id");
        $_SESSION['admin_msg']="Message deleted!";header("Location: messages.php");exit;
    }
}
$messages=[];
$r=$conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
if($r){while($row=$r->fetch_assoc())$messages[]=$row;}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><meta name="theme-color" content="#0f172a">
<title>Messages - EngiHub Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex}
.sidebar{width:260px;background:#0f172a;color:white;height:100vh;position:fixed;top:0;left:0;z-index:200;display:flex;flex-direction:column;transition:transform .3s}
.sidebar .logo-area{padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06)}.sidebar .logo-area h2{font-size:22px;font-weight:800;color:#60a5fa}.sidebar .logo-area h2 span{color:white}.sidebar .logo-area p{font-size:10px;background:#2563eb;color:white;padding:2px 8px;border-radius:20px;font-weight:700;display:inline-block;margin-top:6px}
.sidebar nav{flex:1;overflow-y:auto;padding:8px 0}.sidebar nav .nl{font-size:10px;font-weight:700;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:1px;padding:14px 24px 4px}
.sidebar nav a{display:flex;align-items:center;gap:12px;padding:11px 24px;color:rgba(255,255,255,.5);font-size:13px;font-weight:500;transition:all .15s;border-left:3px solid transparent}.sidebar nav a:hover{color:white;background:rgba(255,255,255,.04)}.sidebar nav a.active{color:#60a5fa;background:rgba(96,165,250,.08);border-left-color:#60a5fa;font-weight:600}
.sidebar nav .ni{width:20px;text-align:center;font-size:15px;flex-shrink:0}
.sidebar footer{padding:16px 24px;border-top:1px solid rgba(255,255,255,.06)}sidebar footer a{color:#ef4444;font-size:13px;font-weight:600}
.main{flex:1;margin-left:260px;padding:24px 32px}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.topbar h1{font-size:24px;font-weight:800}.topbar .sub{font-size:14px;color:#6b7280}
.btn{padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:6px;transition:all .15s}
.btn-danger{background:#ef4444;color:white}.btn-danger:hover{background:#dc2626}
.btn-sm{padding:6px 14px;font-size:12px;border-radius:7px}
.alert{padding:12px 16px;border-radius:8px;font-size:14px;font-weight:500;margin-bottom:20px}.alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.card{background:white;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.06)}
table{width:100%;border-collapse:collapse}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid #f3f4f6;font-size:13px}th{font-weight:700;color:#6b7280;font-size:11px;text-transform:uppercase}
.msg-sender{font-weight:600;font-size:13px}.msg-subject{font-size:13px;color:#111827;font-weight:500}.msg-body{font-size:12px;color:#6b7280;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.empty{text-align:center;color:#9ca3af;padding:40px}
.mobile-menu{display:none;position:fixed;top:0;left:0;right:0;height:60px;background:#0f172a;z-index:200;align-items:center;justify-content:space-between;padding:0 20px}.mobile-menu h2{color:white;font-size:18px;font-weight:700}.mobile-menu button{background:none;border:none;color:white;font-size:24px;cursor:pointer}
@media(max-width:768px){.sidebar{display:none}.mobile-menu{display:flex}.main{margin-left:0;padding:80px 16px 24px}table{font-size:12px}th,td{padding:8px 6px}}
</style>
</head>
<body>
<div class="mobile-menu"><h2>EngiHub Admin</h2><button onclick="document.querySelector('.sidebar').style.display=document.querySelector('.sidebar').style.display==='flex'?'none':'flex'">&#9776;</button></div>
<div class="sidebar">
<div class="logo-area"><h2>Engi<span>Hub</span></h2><p>ADMIN</p></div>
<nav>
<div class="nl">Overview</div><a href="index.php"><span class="ni">&#127968;</span>Dashboard</a>
<div class="nl">Management</div><a href="students.php"><span class="ni">&#127891;</span>Students</a><a href="notes.php"><span class="ni">&#128218;</span>Notes</a><a href="syllabus.php"><span class="ni">&#128209;</span>Syllabus</a><a href="pyq.php"><span class="ni">&#128196;</span>PYQ</a><a href="practicals.php"><span class="ni">&#128300;</span>Practicals</a>
<div class="nl">Resources</div><a href="coding.php"><span class="ni">&#128187;</span>Coding</a><a href="projects.php"><span class="ni">&#128640;</span>Projects</a><a href="placement.php"><span class="ni">&#127919;</span>Placement</a>
<div class="nl">Communication</div><a href="notices.php"><span class="ni">&#128227;</span>Notices</a><a href="messages.php" class="active"><span class="ni">&#128172;</span>Messages</a>
<div class="nl">System</div><a href="settings.php"><span class="ni">&#9881;</span>Settings</a><a href="profile.php"><span class="ni">&#128100;</span>Profile</a>
</nav>
<footer><a href="logout.php">&#10148; Logout</a></footer>
</div>
<div class="main">
<?php if($msg):?><div class="alert alert-success">&#10003; <?php echo $msg;?></div><?php endif;?>
<div class="topbar"><div><h1>&#128172; Contact Messages</h1><div class="sub"><?php echo count($messages);?> message(s) total</div></div></div>
<div class="card">
<table>
<thead><tr><th>Sender</th><th>Email</th><th>Subject</th><th>Message</th><th>Date</th><th>Actions</th></tr></thead>
<tbody>
<?php if(!empty($messages)): foreach($messages as $m):?>
<tr>
<td class="msg-sender"><?php echo htmlspecialchars($m['name']);?></td>
<td style="font-size:13px;color:#6b7280"><?php echo htmlspecialchars($m['email']);?></td>
<td class="msg-subject"><?php echo htmlspecialchars($m['subject']);?></td>
<td class="msg-body" title="<?php echo htmlspecialchars($m['message']);?>"><?php echo htmlspecialchars($m['message']);?></td>
<td style="font-size:12px;color:#9ca3af;white-space:nowrap"><?php echo date('M j, Y',strtotime($m['created_at']));?></td>
<td>
<form method="POST" style="display:inline"><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?php echo $m['id'];?>"><button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this message?')">&#128465;</button></form>
</td>
</tr>
<?php endforeach; else:?>
<tr><td colspan="6" class="empty">No messages received yet.</td></tr>
<?php endif;?>
</tbody>
</table>
</div>
</div>
</body>
</html>

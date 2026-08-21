<?php
require_once '../db.php';
requireAdmin();
$msg='';
if(isset($_SESSION['admin_msg'])){$msg=$_SESSION['admin_msg'];unset($_SESSION['admin_msg']);}
$conn->query("CREATE TABLE IF NOT EXISTS site_settings(id int(11) NOT NULL AUTO_INCREMENT,setting_key varchar(100) NOT NULL,setting_value text DEFAULT NULL,updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),PRIMARY KEY (id),UNIQUE KEY setting_key(setting_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

if($_SERVER['REQUEST_METHOD']==='POST'){
    $keys=['site_name','site_description','contact_email','site_footer_text',' maintenance_mode'];
    foreach($keys as $k){
        $v=trim($_POST[$k]??'');
        $stmt=$conn->prepare("INSERT INTO site_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        $stmt->bind_param("ss",$k,$v);$stmt->execute();$stmt->close();
    }
    $_SESSION['admin_msg']='Settings saved!';header("Location: settings.php");exit;
}
$settings=[];
$r=$conn->query("SELECT setting_key,setting_value FROM site_settings");
if($r){while($row=$r->fetch_assoc())$settings[$row['setting_key']]=$row['setting_value'];}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><meta name="theme-color" content="#0f172a">
<title>Website Settings - EngiHub Admin</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex}
.sidebar{width:260px;background:#0f172a;color:white;height:100vh;position:fixed;top:0;left:0;z-index:200;display:flex;flex-direction:column}.sidebar .logo-area{padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06)}.sidebar .logo-area h2{font-size:22px;font-weight:800;color:#60a5fa}.sidebar .logo-area h2 span{color:white}.sidebar .logo-area p{font-size:10px;background:#2563eb;color:white;padding:2px 8px;border-radius:20px;font-weight:700;display:inline-block;margin-top:6px}.sidebar nav{flex:1;overflow-y:auto;padding:8px 0}.sidebar nav .nl{font-size:10px;font-weight:700;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:1px;padding:14px 24px 4px}.sidebar nav a{display:flex;align-items:center;gap:12px;padding:11px 24px;color:rgba(255,255,255,.5);font-size:13px;font-weight:500;transition:all .15s;border-left:3px solid transparent}.sidebar nav a:hover{color:white;background:rgba(255,255,255,.04)}.sidebar nav a.active{color:#60a5fa;background:rgba(96,165,250,.08);border-left-color:#60a5fa;font-weight:600}.sidebar nav .ni{width:20px;text-align:center;font-size:15px;flex-shrink:0}.sidebar footer{padding:16px 24px;border-top:1px solid rgba(255,255,255,.06)}
.main{flex:1;margin-left:260px;padding:24px 32px}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.topbar h1{font-size:24px;font-weight:800}
.btn{padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:6px;transition:all .15s}.btn-primary{background:#2563eb;color:white}.btn-primary:hover{background:#1d4ed8}
.alert{padding:12px 16px;border-radius:8px;font-size:14px;font-weight:500;margin-bottom:20px}.alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.card{background:white;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.06)}
.form-group{margin-bottom:18px}.form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}.form-group input,.form-group textarea,.form-group select{width:100%;padding:11px 14px;border:2px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;color:#111827;outline:none;transition:border-color .2s}.form-group input:focus,.form-group textarea:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.08)}.form-group textarea{resize:vertical;min-height:80px}
.mobile-menu{display:none;position:fixed;top:0;left:0;right:0;height:60px;background:#0f172a;z-index:200;align-items:center;justify-content:space-between;padding:0 20px}.mobile-menu h2{color:white;font-size:18px;font-weight:700}.mobile-menu button{background:none;border:none;color:white;font-size:24px;cursor:pointer}
@media(max-width:768px){.sidebar{display:none}.mobile-menu{display:flex}.main{margin-left:0;padding:80px 16px 24px}}
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
<div class="nl">Communication</div><a href="notices.php"><span class="ni">&#128227;</span>Notices</a><a href="messages.php"><span class="ni">&#128172;</span>Messages</a>
<div class="nl">System</div><a href="settings.php" class="active"><span class="ni">&#9881;</span>Settings</a><a href="profile.php"><span class="ni">&#128100;</span>Profile</a>
</nav>
<footer><a href="logout.php" style="color:#ef4444;font-size:13px;font-weight:600">&#10148; Logout</a></footer>
</div>
<div class="main">
<?php if($msg):?><div class="alert alert-success">&#10003; <?php echo $msg;?></div><?php endif;?>
<div class="topbar"><h1>&#9881; Website Settings</h1></div>
<div class="card">
<form method="POST">
<div class="form-group"><label>Site Name</label><input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']??'EngiHub');?>"></div>
<div class="form-group"><label>Site Description</label><textarea name="site_description"><?php echo htmlspecialchars($settings['site_description']??'Everything an Engineering Student Needs');?></textarea></div>
<div class="form-group"><label>Contact Email</label><input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email']??'support@engihub.com');?>"></div>
<div class="form-group"><label>Footer Text</label><input type="text" name="site_footer_text" value="<?php echo htmlspecialchars($settings['site_footer_text']??'Built for engineering students, by engineering students.');?>"></div>
<div class="form-group"><label>Maintenance Mode</label><select name="maintenance_mode"><option value="0" <?php echo ($settings['maintenance_mode']??'0')==='0'?'selected':'';?>>Disabled</option><option value="1" <?php echo ($settings['maintenance_mode']??'0')==='1'?'selected':'';?>>Enabled</option></select></div>
<button type="submit" class="btn btn-primary">&#10003; Save Settings</button>
</form>
</div>
</div>
</body>
</html>

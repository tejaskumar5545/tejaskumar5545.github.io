<?php
require_once '../db.php';
requireAdmin();
$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);
$msg = '';
if (isset($_SESSION['admin_msg'])) { $msg = $_SESSION['admin_msg']; unset($_SESSION['admin_msg']); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'toggle_active' && $id > 0) {
        $conn->query("UPDATE students SET is_active = NOT is_active WHERE id = $id");
        $_SESSION['admin_msg'] = "Student status updated!"; header("Location: students.php"); exit;
    } elseif ($act === 'delete' && $id > 0) {
        $conn->query("DELETE FROM students WHERE id = $id");
        $_SESSION['admin_msg'] = "Student deleted!"; header("Location: students.php"); exit;
    }
}
$students = $conn->query("SELECT * FROM students ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#1e3a5f">
    <title>Manage Students - EngiHub Admin</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex}
        .sidebar{width:260px;background:#111827;color:white;padding:24px 0;flex-shrink:0;display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100}
        .sidebar .logo-area{padding:0 24px 24px;border-bottom:1px solid rgba(255,255,255,.1)}.sidebar .logo-area h2{font-size:22px;font-weight:800}.sidebar .logo-area p{font-size:12px;color:#9ca3af;margin-top:4px}
        .sidebar nav{flex:1;padding:16px 0}.sidebar nav a{display:flex;align-items:center;gap:12px;padding:12px 24px;color:#9ca3af;text-decoration:none;font-size:14px;font-weight:500;transition:all .2s;border-left:3px solid transparent}.sidebar nav a:hover{background:rgba(255,255,255,.05);color:white}.sidebar nav a.active{color:#60a5fa;border-left-color:#60a5fa;background:rgba(96,165,250,.1)}
        .sidebar .sidebar-footer{padding:16px 24px;border-top:1px solid rgba(255,255,255,.1)}
        .main{flex:1;margin-left:260px;padding:24px 32px}
        .page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h1{font-size:24px;font-weight:800}
        .btn{padding:10px 20px;border-radius:10px;text-decoration:none;font-size:14px;font-weight:600;cursor:pointer;border:none;font-family:inherit;display:inline-flex;align-items:center;gap:6px}.btn-primary{background:#2563eb;color:white}.btn-primary:hover{background:#1d4ed8}.btn-danger{background:#ef4444;color:white}.btn-danger:hover{background:#dc2626}.btn-sm{padding:6px 14px;font-size:12px;border-radius:7px}
        .alert{padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:20px;font-weight:500}.alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
        .card{background:white;border-radius:14px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,.06)}
        table{width:100%;border-collapse:collapse}th,td{padding:12px;text-align:left;border-bottom:1px solid #f3f4f6;font-size:14px}th{font-weight:700;color:#6b7280;font-size:12px;text-transform:uppercase}
        .actions-cell{display:flex;gap:6px}
        .status-active{color:#16a34a;font-weight:600}.status-inactive{color:#ef4444;font-weight:600}
        .mobile-menu{display:none;position:fixed;top:0;left:0;right:0;height:60px;background:#111827;z-index:200;align-items:center;justify-content:space-between;padding:0 20px}
        .mobile-menu h2{color:white;font-size:18px;font-weight:700}.mobile-menu button{background:none;border:none;color:white;font-size:24px;cursor:pointer}
        @media(max-width:768px){.sidebar{display:none}.mobile-menu{display:flex}.main{margin-left:0;padding:80px 16px 24px}table{font-size:12px}th,td{padding:8px 6px}}
    </style>
</head>
<body>
<div class="mobile-menu"><h2>EngiHub Admin</h2><button onclick="document.querySelector('.sidebar').style.display=document.querySelector('.sidebar').style.display==='flex'?'none':'flex'">&#9776;</button></div>
<div class="sidebar">
    <div class="logo-area"><h2>EngiHub</h2><p>Admin Panel</p></div>
    <nav>
        <a href="index.php">&#127968; Dashboard</a><a href="notes.php">&#128218; Manage Notes</a><a href="syllabus.php">&#128218; Manage Syllabus</a><a href="pyq.php">&#128196; Manage PYQ</a><a href="practicals.php">&#128295; Manage Practicals</a><a href="students.php" class="active">&#127891; Students</a><a href="notices.php">&#128227; Notices</a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php" style="color:#ef4444;text-decoration:none;font-weight:600">Logout</a></div>
</div>
<div class="main">
    <?php if ($msg): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>
    <div class="page-header"><h1>Manage Students</h1><span style="font-size:14px;color:#6b7280"><?php echo $students->num_rows; ?> total student(s)</span></div>
    <div class="card"><table><thead><tr><th>Name</th><th>Email</th><th>Branch</th><th>Sem</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead><tbody>
    <?php if ($students && $students->num_rows > 0): while ($row = $students->fetch_assoc()): ?>
        <tr>
            <td style="font-weight:600"><?php echo htmlspecialchars($row['full_name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['branch']); ?></td>
            <td><?php echo htmlspecialchars($row['semester']); ?></td>
            <td><span class="<?php echo $row['is_active'] ? 'status-active' : 'status-inactive'; ?>"><?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
            <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
            <td><div class="actions-cell">
                <form method="POST" style="display:inline"><input type="hidden" name="act" value="toggle_active"><input type="hidden" name="id" value="<?php echo $row['id']; ?>"><button type="submit" class="btn btn-primary btn-sm"><?php echo $row['is_active'] ? 'Deactivate' : 'Activate'; ?></button></form>
                <form method="POST" style="display:inline"><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?php echo $row['id']; ?>"><button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this student?')">Delete</button></form>
            </div></td>
        </tr>
    <?php endwhile; else: ?><tr><td colspan="7" style="text-align:center;color:#9ca3af;padding:30px">No students registered yet.</td></tr><?php endif; ?>
    </tbody></table></div>
</div></body></html>

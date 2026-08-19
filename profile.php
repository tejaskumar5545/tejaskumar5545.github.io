<?php
require_once 'db.php';
requireLogin();

$student_id = $_SESSION['student_id'];
$message = '';
$msgType = '';

$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = "Invalid security token.";
        $msgType = 'error';
    } else {
        $dob = $_POST['dob'] ?? '';
        $state = sanitize($conn, $_POST['state'] ?? '');
        $city = sanitize($conn, $_POST['city'] ?? '');
        $sid = sanitize($conn, $_POST['student_id_field'] ?? '');

        $photoPath = $student['profile_photo'];
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_photo'];
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            if (in_array($file['type'], $allowed) && $file['size'] <= 5*1024*1024) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'student_' . $student_id . '_' . time() . '.' . $ext;
                $uploadDir = 'uploads/students/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    if ($photoPath && file_exists($photoPath)) unlink($photoPath);
                    $photoPath = $uploadDir . $filename;
                }
            } else {
                $message = "Invalid photo. Use JPG/PNG/GIF/WebP under 5MB.";
                $msgType = 'error';
            }
        }

        $update = $conn->prepare("UPDATE students SET dob=?, state=?, city=?, student_id=?, profile_photo=? WHERE id=?");
        $update->bind_param("sssssi", $dob, $state, $city, $sid, $photoPath, $student_id);
        if ($update->execute()) {
            $message = "Profile updated successfully!";
            $msgType = 'success';
            $stmt2 = $conn->prepare("SELECT * FROM students WHERE id = ?");
            $stmt2->bind_param("i", $student_id);
            $stmt2->execute();
            $student = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
        } else {
            $message = "Failed to update profile.";
            $msgType = 'error';
        }
        $update->close();
    }
}
$csrf = generateCSRFToken();

$states = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Delhi','Jammu and Kashmir','Ladakh','Chandigarh','Puducherry','Andaman and Nicobar Islands','Dadra and Nagar Haveli and Daman and Diu','Lakshadweep'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="theme-color" content="#2563eb">
    <title>My Profile - EngiHub</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f5f7fb;color:#111827;min-height:100vh;display:flex;flex-direction:column}
        .navbar{width:100%;height:70px;background:white;display:flex;align-items:center;justify-content:space-between;padding:0 6%;box-shadow:0 2px 10px rgba(0,0,0,.08);position:sticky;top:0;z-index:100}
        .logo{font-size:28px;font-weight:bold;color:#2563eb;text-decoration:none}.logo span{color:#111827}
        .nav-links{display:flex;gap:20px;list-style:none;align-items:center}.nav-links a{text-decoration:none;color:#333;font-weight:500;transition:color .2s;font-size:14px}.nav-links a:hover{color:#2563eb}.nav-links a.active{color:#2563eb;font-weight:700}
        .nav-user{display:flex;align-items:center;gap:12px}.nav-user span{font-weight:600;color:#2563eb}.nav-user a{color:#ef4444;font-size:13px;font-weight:600;text-decoration:none}.nav-user a:hover{text-decoration:underline}
        .menu-toggle{display:none;background:none;border:none;font-size:28px;cursor:pointer;color:#111827}
        .profile-wrapper{flex:1;max-width:800px;margin:30px auto;width:100%;padding:0 6%}
        .profile-header{margin-bottom:24px}.profile-header h1{font-size:26px;font-weight:800;color:#111827}.profile-header p{font-size:14px;color:#6b7280;margin-top:4px}
        .profile-card{background:white;border-radius:14px;padding:32px;box-shadow:0 2px 10px rgba(0,0,0,.06);margin-bottom:20px}
        .profile-card h3{font-size:18px;font-weight:700;color:#111827;margin-bottom:6px;padding-bottom:12px;border-bottom:2px solid #f3f4f6}
        .profile-card .desc{font-size:13px;color:#6b7280;margin-bottom:20px}
        .photo-upload{display:flex;align-items:center;gap:20px;margin-bottom:24px}
        .photo-preview{width:90px;height:90px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-size:36px;color:#9ca3af;overflow:hidden;flex-shrink:0;border:3px solid #e5e7eb}
        .photo-preview img{width:100%;height:100%;object-fit:cover}
        .photo-actions{display:flex;flex-direction:column;gap:8px}
        .photo-actions label{display:inline-block;padding:8px 16px;background:#2563eb;color:white;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:background .2s;text-align:center}.photo-actions label:hover{background:#1d4ed8}
        .photo-actions input[type="file"]{display:none}
        .photo-actions .remove-btn{padding:8px 16px;background:#fef2f2;color:#ef4444;border:1px solid #fecaca;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;text-align:center}.photo-actions .remove-btn:hover{background:#fee2e2}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .form-group{margin-bottom:18px}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
        .form-group label .req{color:#ef4444;margin-left:2px}
        .form-group label .optional{color:#9ca3af;font-weight:400;font-size:12px}
        .input-wrapper{position:relative}
        .input-wrapper input,.input-wrapper select{width:100%;padding:12px 14px;border:2px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;color:#111827;outline:none;transition:all .2s;background:white}
        .input-wrapper input:focus,.input-wrapper select:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
        .input-wrapper .readonly{background:#f9fafb;color:#6b7280;cursor:not-allowed}
        .save-btn{padding:13px 32px;background:#2563eb;color:white;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .2s;display:flex;align-items:center;gap:8px}
        .save-btn:hover{background:#1d4ed8;transform:translateY(-1px);box-shadow:0 4px 12px rgba(37,99,235,.3)}
        .save-btn .spinner{display:none;width:18px;height:18px;border:2px solid rgba(255,255,255,.3);border-top-color:white;border-radius:50%;animation:spin .6s linear infinite}
        .save-btn.loading .spinner{display:block}.save-btn.loading .btn-text{display:none}
        @keyframes spin{to{transform:rotate(360deg)}}
        .alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:18px;font-weight:500;display:flex;align-items:center;gap:8px}
        .alert-success{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
        .alert-danger{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
        .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .info-item{padding:12px;background:#f9fafb;border-radius:8px}
        .info-item .label{font-size:11px;color:#6b7280;text-transform:uppercase;font-weight:600;letter-spacing:.5px}
        .info-item .value{font-size:15px;font-weight:600;color:#111827;margin-top:4px}
        .footer{background:#111827;color:white;padding:20px 6%;text-align:center;margin-top:auto}.footer p{font-size:13px;color:#6b7280}
        @media(max-width:768px){
            .navbar{height:60px;padding:0 20px}.logo{font-size:23px}
            .nav-links{display:none;position:absolute;top:60px;left:0;right:0;background:white;flex-direction:column;padding:16px 20px;box-shadow:0 8px 24px rgba(0,0,0,.1);gap:0}.nav-links.open{display:flex}.nav-links a{padding:14px 0;border-bottom:1px solid #f3f4f6}.menu-toggle{display:block}
            .profile-wrapper{padding:20px 16px}
            .form-row{grid-template-columns:1fr}
            .info-grid{grid-template-columns:1fr}
            .photo-upload{flex-direction:column;align-items:flex-start}
        }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="index.html" class="logo">Engi<span>Hub</span></a>
    <button class="menu-toggle" id="menuToggle">&#9776;</button>
    <ul class="nav-links" id="navLinks">
        <li><a href="index.html">Home</a></li>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="notes.php">Notes</a></li>
        <li><a href="pyq.php">PYQ</a></li>
        <li><a href="coding.html">Coding</a></li>
        <div class="nav-user">
            <span>&#128100; <?php echo htmlspecialchars($student['full_name']); ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </ul>
</nav>

<div class="profile-wrapper">
    <div class="profile-header">
        <h1>My Profile</h1>
        <p>Manage your personal information and profile settings.</p>
    </div>

    <?php if ($message): ?><div class="alert alert-<?php echo $msgType; ?>"><?php echo $msgType === 'success' ? '&#10003; ' : '&#9888; '; echo htmlspecialchars($message); ?></div><?php endif; ?>

    <div class="profile-card">
        <h3>Account Information</h3>
        <div class="info-grid">
            <div class="info-item"><div class="label">Full Name</div><div class="value"><?php echo htmlspecialchars($student['full_name']); ?></div></div>
            <div class="info-item"><div class="label">Email</div><div class="value"><?php echo htmlspecialchars($student['email']); ?></div></div>
            <div class="info-item"><div class="label">Mobile</div><div class="value"><?php echo htmlspecialchars($student['mobile'] ?? '-'); ?></div></div>
            <div class="info-item"><div class="label">Branch</div><div class="value"><?php echo htmlspecialchars($student['branch']); ?></div></div>
            <div class="info-item"><div class="label">Semester</div><div class="value"><?php echo $student['semester']; ?><?php echo $student['semester']==='1'?'st':($student['semester']==='2'?'nd':($student['semester']==='3'?'rd':'th')); ?> Semester</div></div>
            <div class="info-item"><div class="label">College</div><div class="value"><?php echo htmlspecialchars($student['college_name'] ?: '-'); ?></div></div>
        </div>
    </div>

    <div class="profile-card">
        <h3>Complete Your Profile</h3>
        <p class="desc">Add optional details to complete your profile. These fields help personalize your experience.</p>
        <form method="POST" action="profile.php" enctype="multipart/form-data" id="profileForm">
            <?php csrfField(); ?>

            <div class="photo-upload">
                <div class="photo-preview" id="photoPreview">
                    <?php if ($student['profile_photo'] && file_exists($student['profile_photo'])): ?>
                        <img src="<?php echo htmlspecialchars($student['profile_photo']); ?>" alt="Profile">
                    <?php else: ?>
                        &#128100;
                    <?php endif; ?>
                </div>
                <div class="photo-actions">
                    <label for="photoInput">Change Photo</label>
                    <input type="file" name="profile_photo" id="photoInput" accept="image/*">
                    <span style="font-size:11px;color:#9ca3af">JPG, PNG or WebP. Max 5MB.</span>
                </div>
            </div>

            <div class="form-group">
                <label>Student ID / Roll Number <span class="optional">(Optional)</span></label>
                <div class="input-wrapper"><input type="text" name="student_id_field" placeholder="e.g. 21CE001" value="<?php echo htmlspecialchars($student['student_id'] ?? ''); ?>"></div>
            </div>

            <div class="form-group">
                <label>Date of Birth <span class="optional">(Optional)</span></label>
                <div class="input-wrapper"><input type="date" name="dob" value="<?php echo htmlspecialchars($student['dob'] ?? ''); ?>"></div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>State <span class="optional">(Optional)</span></label>
                    <div class="input-wrapper">
                        <select name="state">
                            <option value="">Select State</option>
                            <?php foreach ($states as $s): ?>
                                <option value="<?php echo $s; ?>" <?php if (($student['state'] ?? '') === $s) echo 'selected'; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>City <span class="optional">(Optional)</span></label>
                    <div class="input-wrapper"><input type="text" name="city" placeholder="Your city" value="<?php echo htmlspecialchars($student['city'] ?? ''); ?>"></div>
                </div>
            </div>

            <button type="submit" class="save-btn" id="saveBtn">
                <span class="btn-text">Save Profile &#10003;</span>
                <span class="spinner"></span>
            </button>
        </form>
    </div>
</div>

<footer class="footer"><p>&copy; 2026 EngiHub. All rights reserved.</p></footer>

<script>
document.getElementById('menuToggle').addEventListener('click',function(){document.getElementById('navLinks').classList.toggle('open')});
document.getElementById('photoInput').addEventListener('change',function(){
    var f=this.files[0];if(!f)return;
    if(f.size>5*1024*1024){alert('File too large. Max 5MB.');this.value='';return}
    var r=new FileReader();r.onload=function(e){
        var p=document.getElementById('photoPreview');p.innerHTML='<img src="'+e.target.result+'" alt="Preview">';
    };r.readAsDataURL(f);
});
document.getElementById('profileForm').addEventListener('submit',function(){
    var b=document.getElementById('saveBtn');b.classList.add('loading');b.disabled=true;
});
</script>
</body></html>

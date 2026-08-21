<?php
require_once 'db.php';
header('Content-Type: application/json');

if (!isStudent()) { echo json_encode(['success' => false, 'message' => 'Not authenticated']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'message' => 'Invalid request']); exit; }
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { echo json_encode(['success' => false, 'message' => 'Invalid security token']); exit; }

$studentId = $_SESSION['student_id'];

$fullName = trim($_POST['full_name'] ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$dob = $_POST['dob'] ?? '';
$state = trim($_POST['state'] ?? '');
$city = trim($_POST['city'] ?? '');
$collegeName = trim($_POST['college_name'] ?? '');
$studentIdField = trim($_POST['student_id_field'] ?? '');
$branch = trim($_POST['branch'] ?? '');
$semester = trim($_POST['semester'] ?? '');

if (empty($fullName) || strlen($fullName) < 2) {
    echo json_encode(['success' => false, 'message' => 'Full name must be at least 2 characters']); exit;
}
if (!preg_match('/^[a-zA-Z\s\.\-]+$/', $fullName)) {
    echo json_encode(['success' => false, 'message' => 'Name can only contain letters, spaces, dots, and hyphens']); exit;
}
if (!empty($mobile) && !preg_match('/^[\+]?[0-9\s\-\(\)]{7,15}$/', $mobile)) {
    echo json_encode(['success' => false, 'message' => 'Invalid mobile number format']); exit;
}
if (!empty($dob)) {
    $d = DateTime::createFromFormat('Y-m-d', $dob);
    if (!$d || $d->format('Y-m-d') !== $dob) {
        echo json_encode(['success' => false, 'message' => 'Invalid date of birth']); exit;
    }
    if ($d > new DateTime()) {
        echo json_encode(['success' => false, 'message' => 'Date of birth cannot be in the future']); exit;
    }
}

if (!empty($mobile)) {
    $check = $conn->prepare("SELECT id FROM students WHERE mobile = ? AND id != ? LIMIT 1");
    $check->bind_param("si", $mobile, $studentId);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        echo json_encode(['success' => false, 'message' => 'This mobile number is already registered with another account']); exit;
    }
    $check->close();
}

$stmt = $conn->prepare("UPDATE students SET full_name=?, mobile=?, dob=?, state=?, city=?, college_name=?, student_id=?, branch=?, semester=? WHERE id=?");
$stmt->bind_param("sssssssssi", $fullName, $mobile, $dob, $state, $city, $collegeName, $studentIdField, $branch, $semester, $studentId);
$stmt->execute();
$stmt->close();

$_SESSION['student_name'] = $fullName;

$profileFields = ['profile_photo', 'dob', 'mobile', 'city', 'student_id'];
$filled = 0;
$total = count($profileFields);
$checks = ['profile_photo' => $student['profile_photo'] ?? '', 'dob' => $dob, 'mobile' => $mobile, 'city' => $city, 'student_id' => $studentIdField];
foreach ($checks as $v) { if (!empty($v)) $filled++; }
$completion = round(($filled / $total) * 100);

echo json_encode(['success' => true, 'message' => 'Profile updated successfully', 'completion' => $completion]);

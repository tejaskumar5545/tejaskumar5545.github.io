<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

$msg = '';
$err = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM students WHERE id = $id"));
    if ($student) {
        mysqli_query($conn, "DELETE FROM student_answers WHERE attempt_id IN (SELECT id FROM exam_attempts WHERE student_id = $id)");
        mysqli_query($conn, "DELETE FROM exam_attempts WHERE student_id = $id");
        mysqli_query($conn, "DELETE FROM students WHERE id = $id");
        $msg = 'Student removed successfully (along with their test records).';
    } else {
        $err = 'Failed to remove student.';
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_sem = isset($_GET['semester']) ? $_GET['semester'] : '';
$filter_branch = isset($_GET['branch']) ? $_GET['branch'] : '';

$where = [];
if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(full_name LIKE '%$s%' OR email LIKE '%$s%')";
}
if ($filter_sem !== '') {
    $where[] = "semester = '" . mysqli_real_escape_string($conn, $filter_sem) . "'";
}
if ($filter_branch !== '') {
    $where[] = "branch = '" . mysqli_real_escape_string($conn, $filter_branch) . "'";
}
$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$students = mysqli_query($conn, "
    SELECT s.*,
        (SELECT COUNT(*) FROM exam_attempts WHERE student_id = s.id) as attempt_count,
        (SELECT COUNT(*) FROM exam_attempts WHERE student_id = s.id AND passed = 1) as passed_count
    FROM students s
    $where_sql
    ORDER BY s.created_at DESC
");

$semesters = ['Semester 1','Semester 2','Semester 3','Semester 4','Semester 5','Semester 6'];
$branches_result = mysqli_query($conn, "SELECT DISTINCT branch FROM students ORDER BY branch");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d2240">
    <title>Manage Students - ClassroomX</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <a href="index.php" class="logo">
        <img src="images/logo.jpg" alt="ClassroomX" class="logo-img">
        ClassroomX
    </a>
    <button class="menu-toggle">&#9776;</button>
    <nav>
        <a href="index.php">Home</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="admin_syllabus.php">Syllabus</a>
        <a href="admin_pyq.php">PYQ</a>
        <a href="admin_practical.php">Practical</a>
        <a href="admin_coding.php">Coding</a>
        <a href="admin_projects.php">Projects</a>
        <a href="admin_placement.php">Placement</a>
        <a href="admin_exams.php">Exams</a>
        <a href="admin_assignments.php">Assignments</a>
        <a href="admin_notices.php">Notices</a>
        <a href="admin_gallery.php">Gallery</a>
        <a href="admin_students.php" class="active">Students</a>
        <a href="admin_results.php">Results</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="dashboard-header">
        <div>
            <h2>Manage Students</h2>
        </div>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

    <form method="GET" action="admin_students.php">
        <div class="filter-bar">
            <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="semester">
                <option value="">All Semesters</option>
                <?php foreach ($semesters as $sem): ?>
                    <option value="<?php echo $sem; ?>" <?php echo $filter_sem === $sem ? 'selected' : ''; ?>><?php echo $sem; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="branch">
                <option value="">All Branches</option>
                <?php if ($branches_result): while ($b = mysqli_fetch_assoc($branches_result)): ?>
                    <option value="<?php echo $b['branch']; ?>" <?php echo $filter_branch === $b['branch'] ? 'selected' : ''; ?>><?php echo $b['branch']; ?></option>
                <?php endwhile; endif; ?>
            </select>
            <button type="submit" class="btn-filter">Filter</button>
            <?php if ($search !== '' || $filter_sem !== '' || $filter_branch !== ''): ?>
                <a href="admin_students.php" class="btn-reset">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($students && mysqli_num_rows($students) > 0): ?>
        <p style="color:var(--gray-700);font-size:14px;margin-bottom:16px;">Showing <?php echo mysqli_num_rows($students); ?> student(s)</p>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Semester</th>
                        <th>Branch</th>
                        <th>Tests Taken</th>
                        <th>Passed</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = mysqli_fetch_assoc($students)): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['semester']); ?></td>
                            <td><?php echo htmlspecialchars($row['branch']); ?></td>
                            <td><?php echo $row['attempt_count']; ?></td>
                            <td><?php echo $row['passed_count']; ?></td>
                            <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                            <td class="actions">
                                <a href="admin_students.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm btn-delete">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">&#128100;</div>
            <h3>No students found</h3>
            <p>Students who register on the portal will appear here.</p>
        </div>
    <?php endif; ?>
</div>

<footer><p>&copy; 2026 ClassroomX Portal</p></footer>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

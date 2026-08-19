<?php
session_start();
include 'db.php';

$languages_result = mysqli_query($conn, "SELECT DISTINCT language FROM coding ORDER BY language");
$languages = [];
while ($r = mysqli_fetch_assoc($languages_result)) {
    $languages[] = $r['language'];
}

$filter_lang = isset($_GET['language']) ? $_GET['language'] : '';
$filter_diff = isset($_GET['difficulty']) ? $_GET['difficulty'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = [];
if ($filter_lang !== '') {
    $where[] = "language = '" . mysqli_real_escape_string($conn, $filter_lang) . "'";
}
if ($filter_diff !== '') {
    $where[] = "difficulty = '" . mysqli_real_escape_string($conn, $filter_diff) . "'";
}
if ($search !== '') {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $where[] = "(title LIKE '%$search_safe%' OR description LIKE '%$search_safe%')";
}

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
$coding = mysqli_query($conn, "SELECT * FROM coding $where_sql ORDER BY FIELD(difficulty,'Easy','Medium','Hard'), created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#0d2240">
    <title>Coding Practice - EngiHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <a href="index.php" class="logo">
        <img src="images/logo.jpg" alt="EngiHub" class="logo-img">
        EngiHub
    </a>
    <button class="menu-toggle">&#9776;</button>
    <nav>
        <a href="index.php">Home</a>
        <a href="notes.php">Notes</a>
        <a href="syllabus.php">Syllabus</a>
        <a href="pyq.php">PYQ</a>
        <a href="practical.php">Practical</a>
        <a href="coding.php" class="active">Coding</a>
        <?php if (isset($_SESSION['admin'])): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php elseif (isset($_SESSION['student'])): ?>
            <a href="student_dashboard.php">My Dashboard</a>
            <a href="student_logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Admin</a>
            <a href="student_login.php" class="btn-login">Student Login</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container">
    <h2 style="font-size:28px;font-weight:700;margin-bottom:8px;color:var(--white);">Coding Practice</h2>
    <p style="color:var(--gray-700);font-size:15px;margin-bottom:28px;">Practice coding problems with solutions to sharpen your programming skills.</p>

    <form method="GET" action="coding.php">
        <div class="filter-bar">
            <input type="text" name="search" placeholder="Search by title or description..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="language">
                <option value="">All Languages</option>
                <?php foreach ($languages as $lang): ?>
                    <option value="<?php echo $lang; ?>" <?php echo $filter_lang === $lang ? 'selected' : ''; ?>><?php echo $lang; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="difficulty">
                <option value="">All Levels</option>
                <option value="Easy" <?php echo $filter_diff === 'Easy' ? 'selected' : ''; ?>>Easy</option>
                <option value="Medium" <?php echo $filter_diff === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                <option value="Hard" <?php echo $filter_diff === 'Hard' ? 'selected' : ''; ?>>Hard</option>
            </select>
            <button type="submit" class="btn-filter">Filter</button>
            <?php if ($filter_lang !== '' || $filter_diff !== '' || $search !== ''): ?>
                <a href="coding.php" class="btn-reset">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($coding && mysqli_num_rows($coding) > 0): ?>
        <p style="color:var(--gray-700);font-size:14px;margin-bottom:16px;">Showing <?php echo mysqli_num_rows($coding); ?> problem(s)</p>
        <div class="cards">
            <?php while ($row = mysqli_fetch_assoc($coding)): ?>
                <div class="card">
                    <span class="card-badge badge-branch"><?php echo htmlspecialchars($row['language']); ?></span>
                    <span class="card-badge badge-sem"><?php echo htmlspecialchars($row['difficulty']); ?></span>
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p class="card-desc"><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                    <?php if (!empty($row['code'])): ?>
                        <details class="code-details">
                            <summary>View Solution</summary>
                            <pre class="code-block"><code><?php echo htmlspecialchars($row['code']); ?></code></pre>
                        </details>
                    <?php endif; ?>
                    <div class="card-meta">
                        <span class="card-date"><?php echo date('d M Y', strtotime($row['created_at'])); ?></span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">&#128187;</div>
            <h3>No coding problems found</h3>
            <p>Try adjusting your filters or check back later.</p>
        </div>
    <?php endif; ?>
</div>

<footer><p>&copy; 2026 EngiHub Portal</p></footer>
<a href="https://wa.me/918860695666?text=Hi%20EngiHub%2C%20I%20have%20a%20question" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M16.004 0h-.008C7.174 0 0 7.176 0 16c0 3.5 1.132 6.744 3.058 9.374L1.054 31.2l6.064-1.97A15.912 15.912 0 0016.004 32C24.826 32 32 24.822 32 16S24.826 0 16.004 0zm9.31 22.608c-.39 1.096-1.932 2.01-3.162 2.274-.844.18-1.946.322-5.656-1.216-4.746-1.966-7.798-6.79-8.036-7.108-.23-.318-1.9-2.524-1.9-4.814 0-2.29 1.204-3.416 1.63-3.884.39-.428.924-.57 1.23-.57.31 0 .618.004.886.016.284.012.664-.106 1.036.79.39.932 1.33 3.24 1.446 3.478.116.238.194.516.038.834-.156.318-.232.516-.462.794-.23.278-.484.62-.692.832-.23.238-.47.496-.2.972.27.476 1.2 1.98 2.578 3.208 1.77 1.58 3.26 2.07 3.736 2.298.374.18.792.136 1.086-.23.374-.476.836-1.262 1.304-2.02.334-.542.756-.61 1.276-.414.53.196 3.364 1.586 3.94 1.87.576.284.96.428 1.102.664.14.236.14 1.37-.25 2.464z"/></svg>
</a>
<script src="js/main.js"></script>
</body>
</html>
<?php mysqli_close($conn); ?>

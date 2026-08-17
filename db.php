<?php
if (!file_exists(__DIR__ . '/config.php')) {
    die("config.php not found. Copy config.example.php to config.php and fill in your database credentials.");
}

require __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");
?>

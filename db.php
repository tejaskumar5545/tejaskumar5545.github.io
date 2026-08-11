<?php
// === LOCAL DEVELOPMENT (XAMPP) ===
// For live server, update the values below:

$host = "localhost";
$user = "root";
$password = "";
$database = "college_notes";

// === LIVE SERVER EXAMPLE ===
// $host = "sqlXXX.infinityfree.com";
// $user = "if0_XXXXXXX";
// $password = "your_db_password";
// $database = "if0_XXXXXXX_college_notes";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");
?>

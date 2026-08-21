<?php
require_once '../db.php';
if (!isAdmin()) { header("Location: login.php"); exit; }
header("Location: dashboard.php");
exit;

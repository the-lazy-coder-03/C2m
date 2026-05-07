<?php
session_start();

$adminBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php')), '/');
$redirectPath = isset($_SESSION['admin_id']) ? '/dashboard.php' : '/login.php';

header('Location: ' . $adminBasePath . $redirectPath);
exit;

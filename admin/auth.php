<?php
session_start();
require_once __DIR__ . '/../config/config.php';
$adminBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/auth.php')), '/');

$username = trim($_POST['username'] ?? '');
$pass  = $_POST['password'] ?? '';

/**
 * DEMO ONLY:
 * Admin credentials are read from config/.env so they can be changed without editing PHP files.
 * Replace this with a database lookup and password_verify() when real admin accounts are added.
 */
$demoUsername = config_get('ADMIN_USERNAME', 'admin');
$demoPass  = config_get('ADMIN_PASSWORD', 'Admin@123');

if ($username === $demoUsername && $pass === $demoPass) {
  $_SESSION['admin_id'] = 1;
  $_SESSION['admin_name'] = $demoUsername;
  header('Location: ' . $adminBasePath . '/dashboard.php');
  exit;
}

header('Location: ' . $adminBasePath . '/login.php?err=1');
exit;

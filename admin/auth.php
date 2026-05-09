<?php
session_start();
require_once __DIR__ . '/../config/config.php';
$adminBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/auth.php')), '/');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . $adminBasePath . '/login.php');
  exit;
}

$username = trim($_POST['username'] ?? '');
$pass  = $_POST['password'] ?? '';

$adminUsername = (string) config_get('ADMIN_USERNAME', '');
$adminPassword = (string) config_get('ADMIN_PASSWORD', '');

if ($adminUsername !== '' && $adminPassword !== '' && hash_equals($adminUsername, $username) && hash_equals($adminPassword, $pass)) {
  $_SESSION['admin_id'] = 1;
  $_SESSION['admin_name'] = $adminUsername;
  header('Location: ' . $adminBasePath . '/dashboard.php');
  exit;
}


header('Location: ' . $adminBasePath . '/login.php?err=1');
exit;

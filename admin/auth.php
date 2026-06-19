<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/helpers/admin_auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /admin/login');
  exit;
}

$username = trim($_POST['username'] ?? '');
$pass  = $_POST['password'] ?? '';

$adminUsername = (string) config_get('ADMIN_USERNAME', '');
$adminPassword = (string) config_get('ADMIN_PASSWORD', '');

if ($adminUsername !== '' && $adminPassword !== '' && hash_equals($adminUsername, $username) && hash_equals($adminPassword, $pass)) {
  $_SESSION['admin_id'] = 1;
  $_SESSION['admin_name'] = $adminUsername;
  issue_admin_auth($adminUsername);
  header('Location: /admin/dashboard');
  exit;
}


header('Location: /admin/login?err=1');
exit;

<?php
session_start();

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

/**
 * DEMO ONLY: hardcoded admin
 * Replace with database check:
 * - fetch admin by email
 * - verify password_verify($pass, $row['password_hash'])
 */
$DEMO_EMAIL = "admin@example.com";
$DEMO_PASS  = "Admin@123";

if ($email === $DEMO_EMAIL && $pass === $DEMO_PASS) {
  $_SESSION['admin_id'] = 1;
  $_SESSION['admin_name'] = "Admin";
  header("Location: dashboard.php");
  exit;
}

header("Location: login.php?err=1");
exit;

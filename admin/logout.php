<?php
session_start();
$adminBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/logout.php')), '/');
session_destroy();
header('Location: ' . $adminBasePath . '/login.php');
exit;

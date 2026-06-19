<?php
session_start();
require_once __DIR__ . '/../app/helpers/admin_auth_helper.php';

clear_admin_auth();
session_destroy();
header('Location: /');
exit;

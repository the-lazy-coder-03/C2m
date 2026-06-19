<?php
require_once __DIR__ . '/../app/helpers/admin_auth_helper.php';
require_once __DIR__ . '/../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';

clear_user_jwt();
clear_admin_auth();
endUserSession();

header('Location: /login');
exit;

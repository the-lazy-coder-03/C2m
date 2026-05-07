<?php
require_once __DIR__ . '/../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';

clear_user_jwt();
endUserSession();

header('Location: login.php');
exit;

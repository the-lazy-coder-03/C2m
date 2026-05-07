<?php
require_once __DIR__ . '/../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../config/database.php';

startUserSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$errors = [];

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if ($password === '') {
    $errors[] = 'Password is required.';
}

if ($errors !== []) {
    $_SESSION['login_errors'] = $errors;
    $_SESSION['login_old'] = ['email' => $email];
    header('Location: login.php');
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT user_id, first_name, email, password_hash, active
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['login_errors'] = ['Invalid email or password.'];
        $_SESSION['login_old'] = ['email' => $email];
        header('Location: login.php');
        exit;
    }

    if (!$user['active']) {
        $_SESSION['login_errors'] = ['This account is not active.'];
        $_SESSION['login_old'] = ['email' => $email];
        header('Location: login.php');
        exit;
    }

    issue_user_jwt((int) $user['user_id'], $user['first_name'], $user['email']);

    header('Location: products.php');
    exit;
} catch (Throwable $exception) {
    $_SESSION['login_errors'] = ['Login failed: ' . $exception->getMessage()];
    $_SESSION['login_old'] = ['email' => $email];
    header('Location: login.php');
    exit;
}

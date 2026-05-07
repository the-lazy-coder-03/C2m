<?php
require_once __DIR__ . '/../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../config/database.php';

startUserSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$errors = [];

if ($firstName === '') {
    $errors[] = 'First name is required.';
}

if ($lastName === '') {
    $errors[] = 'Last name is required.';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
}

if ($errors !== []) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_old'] = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone,
    ];
    header('Location: register.php');
    exit;
}

try {
    $pdo = getDbConnection();

    $existingUser = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
    $existingUser->execute([':email' => $email]);

    if ($existingUser->fetch()) {
        $_SESSION['register_errors'] = ['An account with this email already exists.'];
        $_SESSION['register_old'] = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
        ];
        header('Location: register.php');
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (first_name, last_name, email, password_hash, phone, active)
         VALUES (:first_name, :last_name, :email, :password_hash, :phone, TRUE)
         RETURNING user_id'
    );
    $stmt->execute([
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':email' => $email,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':phone' => $phone !== '' ? $phone : null,
    ]);

    $userId = (int) $stmt->fetchColumn();
    issue_user_jwt($userId, $firstName, $email);

    header('Location: products.php');
    exit;
} catch (Throwable $exception) {
    $_SESSION['register_errors'] = ['Registration failed: ' . $exception->getMessage()];
    $_SESSION['register_old'] = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone,
    ];
    header('Location: register.php');
    exit;
}

<?php
require_once __DIR__ . '/../app/helpers/admin_auth_helper.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../config/database.php';

startUserSession();
require_admin_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/users');
    exit;
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$activeValue = $_POST['active'] ?? null;
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    !$userId
    || !in_array($activeValue, ['0', '1'], true)
    || !is_string($csrfToken)
    || !isValidCsrfToken('admin_user_status', $csrfToken)
) {
    $_SESSION['admin_flash'] = [
        'type' => 'danger',
        'message' => 'The user status update was not valid. Please try again.',
    ];

    header('Location: /admin/users');
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'UPDATE users
         SET active = :active
         WHERE user_id = :user_id
         RETURNING first_name, last_name, email, active'
    );
    $stmt->bindValue(':active', $activeValue === '1', PDO::PARAM_BOOL);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        throw new RuntimeException('User was not found.');
    }

    $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['email'];
    $isActive = in_array($user['active'], [true, 1, '1', 't', 'true'], true);

    $_SESSION['admin_flash'] = [
        'type' => 'success',
        'message' => $name . ' is now ' . ($isActive ? 'active.' : 'inactive.'),
    ];
} catch (Throwable $exception) {
    $_SESSION['admin_flash'] = [
        'type' => 'danger',
        'message' => 'User status could not be updated: ' . $exception->getMessage(),
    ];
}

header('Location: /admin/users');
exit;

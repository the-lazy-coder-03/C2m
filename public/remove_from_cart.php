<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/helpers/cart_helper.php';

startUserSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit;
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$csrfToken = $_POST['csrf_token'] ?? '';

if (!$productId || !is_string($csrfToken) || !isValidCsrfToken('cart_action', $csrfToken)) {
    $_SESSION['cart_flash'] = [
        'type' => 'danger',
        'message' => 'The remove request was not valid. Please try again.',
    ];
    header('Location: cart.php');
    exit;
}

cart_remove_product($productId);

$_SESSION['cart_flash'] = [
    'type' => 'success',
    'message' => 'Item removed from your cart.',
];

header('Location: cart.php');
exit;

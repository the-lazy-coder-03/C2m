<?php
require_once __DIR__ . '/../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/helpers/cart_helper.php';
require_once __DIR__ . '/../config/database.php';

startUserSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

$currentUser = current_user_from_jwt();

if ($currentUser === null) {
    $_SESSION['login_errors'] = ['Please log in before adding products to your cart.'];
    header('Location: login.php');
    exit;
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$csrfToken = $_POST['csrf_token'] ?? '';
$redirectPath = $productId ? 'product.php?id=' . $productId : 'products.php';

if (!$productId || !is_string($csrfToken) || !isValidCsrfToken('cart_action', $csrfToken)) {
    $_SESSION['cart_flash'] = [
        'type' => 'danger',
        'message' => 'The cart request was not valid. Please try again.',
    ];
    header('Location: ' . $redirectPath);
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT product_id, seller_id, title, quantity, status, active
         FROM products
         WHERE product_id = :product_id
         LIMIT 1'
    );
    $stmt->execute([':product_id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        throw new RuntimeException('Product was not found.');
    }

    $isActive = in_array($product['active'], [true, 1, '1', 't', 'true'], true);

    if ((int) $product['seller_id'] === (int) $currentUser['user_id']) {
        throw new RuntimeException('You cannot add your own listing to the cart.');
    }

    if (!$isActive || $product['status'] !== 'active' || (int) $product['quantity'] < 1) {
        throw new RuntimeException('This product is not available anymore.');
    }

    cart_add_product($productId);

    $_SESSION['cart_flash'] = [
        'type' => 'success',
        'message' => $product['title'] . ' was added to your cart.',
    ];

    header('Location: cart.php');
    exit;
} catch (Throwable $exception) {
    $_SESSION['cart_flash'] = [
        'type' => 'danger',
        'message' => 'Product could not be added to cart: ' . $exception->getMessage(),
    ];

    header('Location: ' . $redirectPath);
    exit;
}

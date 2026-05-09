<?php
require_once __DIR__ . '/../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/helpers/cart_helper.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/product_image_helper.php';

$currentUser = require_user_from_jwt();
startUserSession();

$cartIds = cart_product_ids();
$items = [];
$errors = [];
$pageError = '';
$checkoutToken = getCsrfToken('checkout_cart');
$paymentMethods = [
    'card' => 'Card payment',
    'eft' => 'EFT / Bank transfer',
    'cash' => 'Cash on delivery',
];
$formData = [
    'shipping_full_name' => '',
    'shipping_phone' => '',
    'shipping_address_line1' => '',
    'shipping_address_line2' => '',
    'shipping_city' => '',
    'shipping_province' => '',
    'shipping_postal_code' => '',
    'shipping_notes' => '',
    'payment_method' => 'card',
];

function checkout_cart_placeholders(array $ids): array
{
    $placeholders = [];
    $params = [];

    foreach ($ids as $index => $productId) {
        $placeholder = ':id' . $index;
        $placeholders[] = $placeholder;
        $params[$placeholder] = $productId;
    }

    return [$placeholders, $params];
}

function checkout_fetch_summary_items(PDO $pdo, array $ids): array
{
    if ($ids === []) {
        return [];
    }

    [$placeholders, $params] = checkout_cart_placeholders($ids);
    $stmt = $pdo->prepare(
        'SELECT
            p.product_id,
            p.title,
            p.price,
            p.quantity,
            p.status,
            p.active,
            p.location,
            p.seller_id,
            c.category_name,
            u.first_name,
            u.last_name,
            primary_image.image_path AS primary_image_path
         FROM products p
         LEFT JOIN categories c ON c.category_id = p.category_id
         LEFT JOIN users u ON u.user_id = p.seller_id
         LEFT JOIN LATERAL (
            SELECT image_path
            FROM product_images
            WHERE product_id = p.product_id
            ORDER BY is_primary DESC, uploaded_at ASC, image_id ASC
            LIMIT 1
         ) primary_image ON TRUE
         WHERE p.product_id IN (' . implode(', ', $placeholders) . ')
         ORDER BY p.created_at DESC'
    );
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function checkout_clean_text(string $value, int $maxLength): string
{
    return substr(trim($value), 0, $maxLength);
}

try {
    $pdo = getDbConnection();

    $accountStmt = $pdo->prepare(
        'SELECT first_name, last_name, phone
         FROM users
         WHERE user_id = :user_id
         LIMIT 1'
    );
    $accountStmt->execute([':user_id' => $currentUser['user_id']]);
    $account = $accountStmt->fetch();

    if ($account) {
        $formData['shipping_full_name'] = trim($account['first_name'] . ' ' . $account['last_name']);
        $formData['shipping_phone'] = (string) ($account['phone'] ?? '');
    }

    $items = checkout_fetch_summary_items($pdo, $cartIds);
} catch (Throwable $exception) {
    $pageError = 'Checkout could not be loaded: ' . $exception->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pageError === '') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    $formData = [
        'shipping_full_name' => checkout_clean_text((string) ($_POST['shipping_full_name'] ?? ''), 200),
        'shipping_phone' => checkout_clean_text((string) ($_POST['shipping_phone'] ?? ''), 30),
        'shipping_address_line1' => checkout_clean_text((string) ($_POST['shipping_address_line1'] ?? ''), 255),
        'shipping_address_line2' => checkout_clean_text((string) ($_POST['shipping_address_line2'] ?? ''), 255),
        'shipping_city' => checkout_clean_text((string) ($_POST['shipping_city'] ?? ''), 100),
        'shipping_province' => checkout_clean_text((string) ($_POST['shipping_province'] ?? ''), 100),
        'shipping_postal_code' => checkout_clean_text((string) ($_POST['shipping_postal_code'] ?? ''), 20),
        'shipping_notes' => checkout_clean_text((string) ($_POST['shipping_notes'] ?? ''), 1000),
        'payment_method' => checkout_clean_text((string) ($_POST['payment_method'] ?? 'card'), 50),
    ];

    if (!is_string($submittedToken) || !isValidCsrfToken('checkout_cart', $submittedToken)) {
        $errors[] = 'The checkout form expired. Please try again.';
    }

    foreach ([
        'shipping_full_name' => 'Full name is required.',
        'shipping_phone' => 'Phone number is required.',
        'shipping_address_line1' => 'Street address is required.',
        'shipping_city' => 'City is required.',
        'shipping_province' => 'Province is required.',
        'shipping_postal_code' => 'Postal code is required.',
    ] as $field => $message) {
        if ($formData[$field] === '') {
            $errors[] = $message;
        }
    }

    if (!array_key_exists($formData['payment_method'], $paymentMethods)) {
        $errors[] = 'Choose a valid payment method.';
    }

    if ($cartIds === []) {
        $errors[] = 'Your cart is empty.';
    }

    if ($errors === []) {
        try {
            $pdo->beginTransaction();

            [$placeholders, $params] = checkout_cart_placeholders($cartIds);
            $productStmt = $pdo->prepare(
                'SELECT product_id, seller_id, title, price, quantity, status, active
                 FROM products
                 WHERE product_id IN (' . implode(', ', $placeholders) . ')
                 FOR UPDATE'
            );
            $productStmt->execute($params);
            $lockedProducts = $productStmt->fetchAll();

            $productsById = [];

            foreach ($lockedProducts as $product) {
                $productsById[(int) $product['product_id']] = $product;
            }

            $productsBySeller = [];
            $checkoutTotal = 0.0;

            foreach ($cartIds as $productId) {
                $product = $productsById[$productId] ?? null;

                if (!$product) {
                    $errors[] = 'One cart item no longer exists.';
                    continue;
                }

                $isProductActive = in_array($product['active'], [true, 1, '1', 't', 'true'], true);

                if ((int) $product['seller_id'] === (int) $currentUser['user_id']) {
                    $errors[] = 'You cannot checkout your own listing: ' . $product['title'];
                    continue;
                }

                if (!$isProductActive || $product['status'] !== 'active' || (int) $product['quantity'] < 1) {
                    $errors[] = 'This item is no longer available: ' . $product['title'];
                    continue;
                }

                $sellerId = (int) $product['seller_id'];
                $productsBySeller[$sellerId][] = $product;
                $checkoutTotal += (float) $product['price'];
            }

            if ($errors !== []) {
                throw new RuntimeException(implode(' ', $errors));
            }

            $orderStmt = $pdo->prepare(
                'INSERT INTO orders (
                    buyer_id,
                    seller_id,
                    total_amount,
                    status,
                    shipping_full_name,
                    shipping_phone,
                    shipping_address_line1,
                    shipping_address_line2,
                    shipping_city,
                    shipping_province,
                    shipping_postal_code,
                    shipping_notes
                 ) VALUES (
                    :buyer_id,
                    :seller_id,
                    :total_amount,
                    :status,
                    :shipping_full_name,
                    :shipping_phone,
                    :shipping_address_line1,
                    :shipping_address_line2,
                    :shipping_city,
                    :shipping_province,
                    :shipping_postal_code,
                    :shipping_notes
                 )
                 RETURNING order_id'
            );
            $orderItemStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, price)
                 VALUES (:order_id, :product_id, :quantity, :price)'
            );
            $paymentStmt = $pdo->prepare(
                'INSERT INTO payments (order_id, payment_method, amount, status, paid_at)
                 VALUES (:order_id, :payment_method, :amount, :status, CURRENT_TIMESTAMP)'
            );
            $productUpdateStmt = $pdo->prepare(
                'UPDATE products
                 SET status = :status,
                     active = FALSE,
                     quantity = 0,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE product_id = :product_id'
            );

            $orderIds = [];

            foreach ($productsBySeller as $sellerId => $sellerProducts) {
                $sellerTotal = array_reduce(
                    $sellerProducts,
                    static fn (float $sum, array $product): float => $sum + (float) $product['price'],
                    0.0
                );

                $orderStmt->execute([
                    ':buyer_id' => (int) $currentUser['user_id'],
                    ':seller_id' => (int) $sellerId,
                    ':total_amount' => $sellerTotal,
                    ':status' => 'paid',
                    ':shipping_full_name' => $formData['shipping_full_name'],
                    ':shipping_phone' => $formData['shipping_phone'],
                    ':shipping_address_line1' => $formData['shipping_address_line1'],
                    ':shipping_address_line2' => $formData['shipping_address_line2'],
                    ':shipping_city' => $formData['shipping_city'],
                    ':shipping_province' => $formData['shipping_province'],
                    ':shipping_postal_code' => $formData['shipping_postal_code'],
                    ':shipping_notes' => $formData['shipping_notes'],
                ]);
                $orderId = (int) $orderStmt->fetchColumn();
                $orderIds[] = $orderId;

                foreach ($sellerProducts as $product) {
                    $orderItemStmt->execute([
                        ':order_id' => $orderId,
                        ':product_id' => (int) $product['product_id'],
                        ':quantity' => 1,
                        ':price' => (float) $product['price'],
                    ]);

                    $productUpdateStmt->execute([
                        ':status' => 'sold',
                        ':product_id' => (int) $product['product_id'],
                    ]);
                }

                $paymentStmt->execute([
                    ':order_id' => $orderId,
                    ':payment_method' => $formData['payment_method'],
                    ':amount' => $sellerTotal,
                    ':status' => 'successful',
                ]);
            }

            $pdo->commit();
            cart_clear();

            $_SESSION['checkout_success'] = [
                'order_ids' => $orderIds,
                'total' => $checkoutTotal,
                'payment_method' => $paymentMethods[$formData['payment_method']],
            ];

            header('Location: /checkout-success');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors = ['Checkout could not be completed: ' . $exception->getMessage()];
            $items = checkout_fetch_summary_items($pdo, $cartIds);
        }
    }
}

$cartTotal = 0.0;
$hasBlockedItems = false;

foreach ($items as $item) {
    $isActive = in_array($item['active'], [true, 1, '1', 't', 'true'], true);
    $isAvailable = $isActive && $item['status'] === 'active' && (int) $item['quantity'] > 0;
    $isOwnListing = (int) $item['seller_id'] === (int) $currentUser['user_id'];

    if ($isAvailable && !$isOwnListing) {
        $cartTotal += (float) $item['price'];
    } else {
        $hasBlockedItems = true;
    }
}

$canCheckout = $cartIds !== [] && $items !== [] && !$hasBlockedItems && count($cartIds) === count($items);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout | LocalMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/marketplace.css">
</head>
<body>
<div class="market-page">
    <?php include __DIR__ . '/includes/market_nav.php'; ?>

    <main class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Checkout</h1>
                <p class="text-secondary mb-0">Enter shipping details and choose a payment method.</p>
            </div>
            <a class="btn btn-outline-primary" href="/cart">Back to Cart</a>
        </div>

        <?php if ($pageError !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($pageError); ?></div>
        <?php elseif (!$canCheckout): ?>
            <div class="alert alert-warning">Your cart has unavailable items. Please return to your cart before checkout.</div>
            <a class="btn btn-primary" href="/cart">Review Cart</a>
        <?php else: ?>
            <?php if ($errors !== []): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <form class="market-card bg-white p-4" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($checkoutToken); ?>">

                        <h2 class="h4 fw-bold mb-3">Shipping Details</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="shipping_full_name">Full name</label>
                                <input class="form-control" id="shipping_full_name" name="shipping_full_name" value="<?php echo htmlspecialchars($formData['shipping_full_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="shipping_phone">Phone number</label>
                                <input class="form-control" id="shipping_phone" name="shipping_phone" value="<?php echo htmlspecialchars($formData['shipping_phone']); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="shipping_address_line1">Street address</label>
                                <input class="form-control" id="shipping_address_line1" name="shipping_address_line1" value="<?php echo htmlspecialchars($formData['shipping_address_line1']); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="shipping_address_line2">Apartment, building, or landmark</label>
                                <input class="form-control" id="shipping_address_line2" name="shipping_address_line2" value="<?php echo htmlspecialchars($formData['shipping_address_line2']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="shipping_city">City</label>
                                <input class="form-control" id="shipping_city" name="shipping_city" value="<?php echo htmlspecialchars($formData['shipping_city']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="shipping_province">Province</label>
                                <input class="form-control" id="shipping_province" name="shipping_province" value="<?php echo htmlspecialchars($formData['shipping_province']); ?>" placeholder="Gauteng" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="shipping_postal_code">Postal code</label>
                                <input class="form-control" id="shipping_postal_code" name="shipping_postal_code" value="<?php echo htmlspecialchars($formData['shipping_postal_code']); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="shipping_notes">Delivery notes</label>
                                <textarea class="form-control" id="shipping_notes" name="shipping_notes" rows="3"><?php echo htmlspecialchars($formData['shipping_notes']); ?></textarea>
                            </div>
                        </div>

                        <h2 class="h4 fw-bold mt-4 mb-3">Payment</h2>
                        <div class="mb-3">
                            <label class="form-label" for="payment_method">Payment method</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <?php foreach ($paymentMethods as $method => $label): ?>
                                    <option value="<?php echo htmlspecialchars($method); ?>" <?php echo $formData['payment_method'] === $method ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-help mt-2">Demo checkout records the payment as successful in the database. Do not enter real card details.</div>
                        </div>

                        <button class="btn btn-success btn-lg w-100 mt-3" type="submit">Pay R<?php echo number_format($cartTotal, 2); ?> and Place Order</button>
                    </form>
                </div>

                <div class="col-lg-4">
                    <aside class="market-card bg-white p-4 cart-summary">
                        <h2 class="h4 fw-bold mb-3">Cart Summary</h2>
                        <?php foreach ($items as $item): ?>
                            <div class="d-flex justify-content-between gap-3 border-bottom py-2">
                                <span><?php echo htmlspecialchars($item['title']); ?></span>
                                <strong>R<?php echo number_format((float) $item['price'], 2); ?></strong>
                            </div>
                        <?php endforeach; ?>
                        <div class="d-flex justify-content-between pt-3 mt-2">
                            <span>Total</span>
                            <strong class="text-success">R<?php echo number_format($cartTotal, 2); ?></strong>
                        </div>
                    </aside>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>

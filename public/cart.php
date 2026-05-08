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
$error = '';
$flash = $_SESSION['cart_flash'] ?? null;
$cartToken = getCsrfToken('cart_action');
$cartTotal = 0.0;
$checkoutItemCount = 0;
$hasBlockedItems = false;
unset($_SESSION['cart_flash']);

try {
    if ($cartIds !== []) {
        $pdo = getDbConnection();
        $placeholders = [];
        $params = [];

        foreach ($cartIds as $index => $productId) {
            $placeholder = ':id' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $productId;
        }

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
        $items = $stmt->fetchAll();

        $foundIds = array_map(static fn (array $item): int => (int) $item['product_id'], $items);

        foreach (array_diff($cartIds, $foundIds) as $missingProductId) {
            cart_remove_product((int) $missingProductId);
        }

        $cartIds = cart_product_ids();
    }
} catch (Throwable $exception) {
    $error = 'Cart could not be loaded: ' . $exception->getMessage();
}

foreach ($items as $item) {
    $isActive = in_array($item['active'], [true, 1, '1', 't', 'true'], true);
    $isAvailable = $isActive && $item['status'] === 'active' && (int) $item['quantity'] > 0;
    $isOwnListing = (int) $item['seller_id'] === (int) $currentUser['user_id'];

    if ($isAvailable && !$isOwnListing) {
        $cartTotal += (float) $item['price'];
        $checkoutItemCount++;
    } else {
        $hasBlockedItems = true;
    }
}

$missingItemCount = max(0, count($cartIds) - count($items));
$canCheckout = $items !== [] && !$hasBlockedItems && $missingItemCount === 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cart | LocalMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/marketplace.css">
</head>
<body>
<div class="market-page">
    <?php include __DIR__ . '/includes/market_nav.php'; ?>

    <main class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">My Cart</h1>
                <p class="text-secondary mb-0">Review your selected products before checkout.</p>
            </div>
            <a class="btn btn-outline-primary" href="products.php">Continue Browsing</a>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($cartIds === []): ?>
            <div class="market-card bg-white p-4 text-center">
                <h2 class="h4 fw-bold">Your cart is empty</h2>
                <p class="text-secondary">Add a product before starting checkout.</p>
                <a class="btn btn-primary" href="products.php">Browse Items</a>
            </div>
        <?php else: ?>
            <?php if ($missingItemCount > 0): ?>
                <div class="alert alert-warning">
                    <?php echo $missingItemCount; ?> item(s) in your cart no longer exist. Remove unavailable items before checkout.
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="market-card bg-white p-0">
                        <?php foreach ($items as $item): ?>
                            <?php
                            $isActive = in_array($item['active'], [true, 1, '1', 't', 'true'], true);
                            $isAvailable = $isActive && $item['status'] === 'active' && (int) $item['quantity'] > 0;
                            $isOwnListing = (int) $item['seller_id'] === (int) $currentUser['user_id'];
                            $itemBlocked = !$isAvailable || $isOwnListing;
                            ?>
                            <div class="cart-item p-3 p-md-4 border-bottom">
                                <div class="d-flex gap-3 align-items-start">
                                    <img
                                        class="cart-item-image"
                                        src="<?php echo htmlspecialchars(public_asset_url($item['primary_image_path'])); ?>"
                                        alt="<?php echo htmlspecialchars($item['title']); ?>"
                                        loading="lazy"
                                    >
                                    <div class="flex-grow-1">
                                        <div class="d-flex flex-wrap justify-content-between gap-2">
                                            <div>
                                                <div class="small text-secondary"><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></div>
                                                <h2 class="h5 mb-1"><?php echo htmlspecialchars($item['title']); ?></h2>
                                                <p class="text-secondary small mb-2">
                                                    Seller: <?php echo htmlspecialchars(trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? '')) ?: 'Seller'); ?> ·
                                                    <?php echo htmlspecialchars($item['location'] ?: 'Location not listed'); ?>
                                                </p>
                                            </div>
                                            <div class="text-md-end">
                                                <div class="fw-bold text-success">R<?php echo number_format((float) $item['price'], 2); ?></div>
                                                <?php if ($itemBlocked): ?>
                                                    <span class="badge text-bg-warning">Cannot checkout</span>
                                                <?php else: ?>
                                                    <span class="badge text-bg-success">Available</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <?php if (!$isAvailable): ?>
                                            <div class="alert alert-warning py-2 mt-2 mb-2">This item is no longer available.</div>
                                        <?php elseif ($isOwnListing): ?>
                                            <div class="alert alert-info py-2 mt-2 mb-2">This is your listing, so you cannot buy it.</div>
                                        <?php endif; ?>

                                        <div class="d-flex flex-wrap gap-2 mt-3">
                                            <a class="btn btn-sm btn-outline-primary" href="product.php?id=<?php echo (int) $item['product_id']; ?>">View Item</a>
                                            <form action="remove_from_cart.php" method="POST">
                                                <input type="hidden" name="product_id" value="<?php echo (int) $item['product_id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cartToken); ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <aside class="market-card bg-white p-4 cart-summary">
                        <h2 class="h4 fw-bold mb-3">Order Summary</h2>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Checkout items</span>
                            <strong><?php echo $checkoutItemCount; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-3 mt-3">
                            <span>Total</span>
                            <strong class="text-success">R<?php echo number_format($cartTotal, 2); ?></strong>
                        </div>

                        <?php if ($canCheckout): ?>
                            <a class="btn btn-success w-100 mt-4" href="checkout.php">Checkout</a>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100 mt-4" type="button" disabled>Fix Cart Before Checkout</button>
                            <p class="small text-secondary mt-2 mb-0">Remove unavailable or blocked items before paying.</p>
                        <?php endif; ?>
                    </aside>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>

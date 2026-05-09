<?php
require_once __DIR__ . '/../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/helpers/cart_helper.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/product_image_helper.php';

startUserSession();

$product = null;
$images = [];
$error = '';
$flash = $_SESSION['cart_flash'] ?? null;
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$productId && preg_match('#/product/(\d+)#', parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '', $matches)) {
    $productId = (int) $matches[1];
}

$currentUser = current_user_from_jwt();
$canManageProduct = false;
$canAddToCart = false;
$isProductAvailable = false;
$deleteToken = '';
$cartToken = '';
$cartIds = cart_product_ids();
unset($_SESSION['cart_flash']);

if (!$productId) {
    $error = 'A valid product ID is required.';
} else {
    try {
        $pdo = getDbConnection();

        $stmt = $pdo->prepare(
            'SELECT
                p.product_id,
                p.title,
                p.description,
                p.price,
                p.quantity,
                p.condition,
                p.location,
                p.seller_id,
                p.status,
                p.active,
                p.created_at,
                c.category_name,
                u.first_name,
                u.last_name
             FROM products p
             LEFT JOIN categories c ON c.category_id = p.category_id
             LEFT JOIN users u ON u.user_id = p.seller_id
             WHERE p.product_id = :product_id'
        );
        $stmt->execute([':product_id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) {
            $error = 'Product was not found.';
        } else {
            $isProductActive = in_array($product['active'], [true, 1, '1', 't', 'true'], true);
            $isProductAvailable = $isProductActive && $product['status'] === 'active' && (int) $product['quantity'] > 0;
            $canManageProduct = $currentUser !== null && (int) $product['seller_id'] === (int) $currentUser['user_id'];

            if (!$isProductAvailable && !$canManageProduct) {
                $error = 'This product is no longer available.';
            }

            $imageStmt = $pdo->prepare(
                'SELECT image_path, is_primary
                 FROM product_images
                 WHERE product_id = :product_id
                 ORDER BY is_primary DESC, uploaded_at ASC, image_id ASC'
            );
            $imageStmt->execute([':product_id' => $productId]);
            $images = $imageStmt->fetchAll();
            $canAddToCart = $currentUser !== null && !$canManageProduct && $isProductAvailable;

            if ($canManageProduct) {
                $deleteToken = getCsrfToken('delete_listing');
            }

            if ($canAddToCart) {
                $cartToken = getCsrfToken('cart_action');
            }
        }
    } catch (Throwable $exception) {
        $error = 'Product could not be loaded: ' . $exception->getMessage();
    }
}

$mainImagePath = $images[0]['image_path'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $product ? htmlspecialchars($product['title']) : 'Product'; ?> | LocalMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/marketplace.css">
</head>
<body>
<div class="market-page">
    <?php include __DIR__ . '/includes/market_nav.php'; ?>

    <main class="container py-5">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <a class="btn btn-outline-primary" href="/products">Back to products</a>
        <?php else: ?>
            <div class="row g-5">
                <div class="col-lg-7">
                    <img
                        class="product-detail-image mb-3"
                        src="<?php echo htmlspecialchars(public_asset_url($mainImagePath)); ?>"
                        alt="<?php echo htmlspecialchars($product['title']); ?>"
                    >

                    <?php if ($images !== []): ?>
                        <div class="row g-3">
                            <?php foreach ($images as $image): ?>
                                <div class="col-4 col-md-3">
                                    <img
                                        class="product-thumb"
                                        src="<?php echo htmlspecialchars(public_asset_url($image['image_path'])); ?>"
                                        alt="<?php echo htmlspecialchars($product['title']); ?>"
                                        loading="lazy"
                                    >
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-5">
                    <div class="market-card bg-white p-4">
                        <div class="text-secondary mb-2"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></div>
                        <h1 class="fw-bold"><?php echo htmlspecialchars($product['title']); ?></h1>
                        <p class="display-6 text-success fw-bold">R<?php echo number_format((float) $product['price'], 2); ?></p>

                        <div class="border-top border-bottom py-3 my-3">
                            <div><strong>Condition:</strong> <?php echo htmlspecialchars(ucfirst($product['condition'])); ?></div>
                            <div><strong>Quantity:</strong> <?php echo (int) $product['quantity']; ?></div>
                            <div><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($product['status'])); ?></div>
                            <div><strong>Location:</strong> <?php echo htmlspecialchars($product['location'] ?: 'Location not listed'); ?></div>
                            <div><strong>Seller:</strong> <?php echo htmlspecialchars(trim(($product['first_name'] ?? '') . ' ' . ($product['last_name'] ?? '')) ?: 'Seller'); ?></div>
                        </div>

                        <p><?php echo nl2br(htmlspecialchars($product['description'] ?: 'No description provided.')); ?></p>
                        <?php if ($canAddToCart): ?>
                            <?php $isInCart = in_array((int) $product['product_id'], $cartIds, true); ?>
                            <?php if ($isInCart): ?>
                                <a class="btn btn-success w-100 mt-3" href="/cart">Already in Cart - View Cart</a>
                            <?php else: ?>
                                <form class="mt-3" action="/cart/add" method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo (int) $product['product_id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cartToken); ?>">
                                    <button class="btn btn-success w-100" type="submit">Add to Cart</button>
                                </form>
                            <?php endif; ?>
                        <?php elseif (!$currentUser && $isProductAvailable): ?>
                            <a class="btn btn-success w-100 mt-3" href="/login">Login to Add to Cart</a>
                        <?php elseif ($canManageProduct): ?>
                            <div class="alert alert-info mt-3 mb-0">This is your listing, so the cart button is hidden.</div>
                        <?php else: ?>
                            <div class="alert alert-warning mt-3 mb-0">This listing is not available for purchase.</div>
                        <?php endif; ?>

                        <a class="btn btn-outline-primary w-100 mt-2" href="/products">Back to Browse</a>
                        <?php if ($canManageProduct && $product['status'] !== 'sold'): ?>
                            <form
                                class="mt-2"
                                action="/delete-listing"
                                method="POST"
                                onsubmit="return confirm('Delete this listing permanently?');"
                            >
                                <input type="hidden" name="product_id" value="<?php echo (int) $product['product_id']; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($deleteToken); ?>">
                                <button class="btn btn-outline-danger w-100" type="submit">Delete Listing</button>
                            </form>
                        <?php elseif ($canManageProduct): ?>
                            <button class="btn btn-outline-secondary w-100 mt-2" type="button" disabled>Sold - Kept for Records</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>

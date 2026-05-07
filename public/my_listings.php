<?php
require_once __DIR__ . '/../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/product_image_helper.php';

$currentUser = require_user_from_jwt();
startUserSession();

$listings = [];
$error = '';
$flash = $_SESSION['listing_flash'] ?? null;
$deleteToken = getCsrfToken('delete_listing');
unset($_SESSION['listing_flash']);

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT
            p.product_id,
            p.title,
            p.price,
            p.quantity,
            p.status,
            p.location,
            p.created_at,
            c.category_name,
            primary_image.image_path AS primary_image_path
         FROM products p
         LEFT JOIN categories c ON c.category_id = p.category_id
         LEFT JOIN LATERAL (
            SELECT image_path
            FROM product_images
            WHERE product_id = p.product_id
            ORDER BY is_primary DESC, uploaded_at ASC, image_id ASC
            LIMIT 1
         ) primary_image ON TRUE
         WHERE p.seller_id = :seller_id
         ORDER BY p.created_at DESC'
    );
    $stmt->execute([':seller_id' => $currentUser['user_id']]);
    $listings = $stmt->fetchAll();
} catch (Throwable $exception) {
    $error = 'Your listings could not be loaded: ' . $exception->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Listings | LocalMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/marketplace.css">
</head>
<body>
<div class="market-page">
    <?php include __DIR__ . '/includes/market_nav.php'; ?>

    <main class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">My listings</h1>
                <p class="text-secondary mb-0">Manage the products you have listed on LocalMarket.</p>
            </div>
            <a class="btn btn-primary" href="sell_product.php">Create Listing</a>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <?php if ($listings === []): ?>
                <div class="alert alert-info">You have not created any listings yet.</div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($listings as $listing): ?>
                        <div class="col-md-6 col-xl-4">
                            <article class="card market-card product-card">
                                <img
                                    class="product-card-image"
                                    src="<?php echo htmlspecialchars(public_asset_url($listing['primary_image_path'])); ?>"
                                    alt="<?php echo htmlspecialchars($listing['title']); ?>"
                                    loading="lazy"
                                >
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <span class="small text-secondary"><?php echo htmlspecialchars($listing['category_name'] ?? 'Uncategorized'); ?></span>
                                        <span class="badge text-bg-light"><?php echo htmlspecialchars(ucfirst($listing['status'])); ?></span>
                                    </div>
                                    <h2 class="h5 card-title"><?php echo htmlspecialchars($listing['title']); ?></h2>
                                    <p class="fw-bold text-success mb-1">R<?php echo number_format((float) $listing['price'], 2); ?></p>
                                    <p class="text-secondary small mb-3">
                                        Qty: <?php echo (int) $listing['quantity']; ?> ·
                                        <?php echo htmlspecialchars($listing['location'] ?: 'Location not listed'); ?>
                                    </p>
                                    <div class="d-grid gap-2">
                                        <a class="btn btn-outline-primary" href="product.php?id=<?php echo (int) $listing['product_id']; ?>">View Listing</a>
                                        <form
                                            action="delete_listing.php"
                                            method="POST"
                                            onsubmit="return confirm('Delete this listing permanently?');"
                                        >
                                            <input type="hidden" name="product_id" value="<?php echo (int) $listing['product_id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($deleteToken); ?>">
                                            <button class="btn btn-outline-danger w-100" type="submit">Delete Listing</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>
</body>
</html>

<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/product_image_helper.php';

startUserSession();

$products = [];
$error = '';
$flash = $_SESSION['checkout_flash'] ?? ($_SESSION['cart_flash'] ?? null);
$requestedCategory = is_string($_GET['category'] ?? null) ? trim($_GET['category']) : '';
$categoryAliases = [
    'electronics' => 'Electronics',
    'clothing' => 'Clothing',
    'vehicle' => 'Vehicle',
    'vehicles' => 'Vehicle',
    'furniture' => 'Furniture',
    'sport' => 'Sport',
    'sports' => 'Sport',
    'other' => 'Other',
];
$selectedCategory = $requestedCategory !== ''
    ? ($categoryAliases[strtolower($requestedCategory)] ?? $requestedCategory)
    : '';
$pageTitle = $selectedCategory !== '' ? $selectedCategory . ' Listings' : 'Browse Items';
unset($_SESSION['checkout_flash'], $_SESSION['cart_flash']);

try {
    $pdo = getDbConnection();
    $sql =
        'SELECT
            p.product_id,
            p.title,
            p.price,
            p.location,
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
         WHERE p.active = TRUE AND p.status = :status
        ';

    $params = [':status' => 'active'];

    if ($selectedCategory !== '') {
        $sql .= ' AND LOWER(c.category_name) = LOWER(:category_name)';
        $params[':category_name'] = $selectedCategory;
    }

    $sql .= ' ORDER BY p.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (Throwable $exception) {
    $error = 'Products could not be loaded: ' . $exception->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?> | LocalMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/marketplace.css">
</head>
<body>
<div class="market-page">
    <?php include __DIR__ . '/includes/market_nav.php'; ?>

    <main class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <p class="text-secondary mb-0">
                    <?php if ($selectedCategory !== ''): ?>
                        Showing active listings under <?php echo htmlspecialchars($selectedCategory); ?>.
                    <?php else: ?>
                        Product cards use the primary image stored in PostgreSQL.
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($selectedCategory !== ''): ?>
                    <a class="btn btn-outline-secondary" href="products.php">All Listings</a>
                <?php endif; ?>
                <a class="btn btn-primary" href="sell_product.php">Create Listing</a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($products === []): ?>
            <div class="alert alert-info">
                <?php if ($selectedCategory !== ''): ?>
                    No active products found under <?php echo htmlspecialchars($selectedCategory); ?> yet.
                <?php else: ?>
                    No active products found yet.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                    <div class="col-sm-6 col-lg-4">
                        <article class="card market-card product-card">
                            <img
                                class="product-card-image"
                                src="<?php echo htmlspecialchars(public_asset_url($product['primary_image_path'])); ?>"
                                alt="<?php echo htmlspecialchars($product['title']); ?>"
                                loading="lazy"
                            >
                            <div class="card-body">
                                <div class="small text-secondary mb-2"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></div>
                                <h2 class="h5 card-title"><?php echo htmlspecialchars($product['title']); ?></h2>
                                <p class="fw-bold text-success mb-1">R<?php echo number_format((float) $product['price'], 2); ?></p>
                                <p class="text-secondary small mb-3"><?php echo htmlspecialchars($product['location'] ?: 'Location not listed'); ?></p>
                                <a class="btn btn-outline-primary w-100" href="product.php?id=<?php echo (int) $product['product_id']; ?>">View Item</a>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>

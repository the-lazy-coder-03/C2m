<?php
$pageTitle = 'Settings';
$active = 'settings';
require_once __DIR__ . '/../config/database.php';
require __DIR__ . '/partials/header.php';

$counts = [];
$categories = [];
$productStatusRows = [];
$orderStatusRows = [];
$error = '';

try {
    $pdo = getDbConnection();
    $counts = $pdo->query(
        'SELECT
            (SELECT COUNT(*) FROM users) AS users,
            (SELECT COUNT(*) FROM categories) AS categories,
            (SELECT COUNT(*) FROM products) AS products,
            (SELECT COUNT(*) FROM product_images) AS product_images,
            (SELECT COUNT(*) FROM orders) AS orders,
            (SELECT COUNT(*) FROM payments) AS payments'
    )->fetch() ?: [];

    $categories = $pdo->query(
        'SELECT
            c.category_id,
            c.category_name,
            COUNT(p.product_id) AS total_products
         FROM categories c
         LEFT JOIN products p ON p.category_id = c.category_id
         GROUP BY c.category_id, c.category_name
         ORDER BY c.category_name'
    )->fetchAll();

    $productStatusRows = $pdo->query(
        'SELECT status, COUNT(*) AS total
         FROM products
         GROUP BY status
         ORDER BY status'
    )->fetchAll();

    $orderStatusRows = $pdo->query(
        'SELECT status, COUNT(*) AS total
         FROM orders
         GROUP BY status
         ORDER BY status'
    )->fetchAll();
} catch (Throwable $exception) {
    $error = 'Settings data could not be loaded: ' . $exception->getMessage();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">Settings</h1>
        <p class="text-muted mb-0">Database-backed platform overview.</p>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 mb-4">
        <?php foreach ($counts as $label => $value): ?>
            <div class="col">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $label))); ?></h6>
                        <h2 class="card-title mb-0"><?php echo number_format((int) $value); ?></h2>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Categories</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Category</th>
                                <th class="pe-4 text-end">Listings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td class="ps-4"><?php echo htmlspecialchars($category['category_name']); ?></td>
                                    <td class="pe-4 text-end"><?php echo number_format((int) $category['total_products']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Status Breakdown</h5>
                </div>
                <div class="card-body">
                    <h6 class="text-muted">Product Statuses</h6>
                    <?php if ($productStatusRows === []): ?>
                        <p class="text-muted">No products found.</p>
                    <?php else: ?>
                        <?php foreach ($productStatusRows as $row): ?>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span>
                                <strong><?php echo number_format((int) $row['total']); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <h6 class="text-muted mt-4">Order Statuses</h6>
                    <?php if ($orderStatusRows === []): ?>
                        <p class="text-muted mb-0">No orders found.</p>
                    <?php else: ?>
                        <?php foreach ($orderStatusRows as $row): ?>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span>
                                <strong><?php echo number_format((int) $row['total']); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

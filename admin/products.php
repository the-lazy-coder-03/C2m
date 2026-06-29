<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/helpers/product_image_helper.php';

$statusFilters = [
    'all' => 'All Listings',
    'active' => 'Active Listings',
    'sold' => 'Sold Items',
    'reserved' => 'Reserved Items',
    'inactive' => 'Inactive Items',
];
$selectedStatus = strtolower(trim($_GET['status'] ?? 'all'));

if (!array_key_exists($selectedStatus, $statusFilters)) {
    $selectedStatus = 'all';
}

$pageTitle = $statusFilters[$selectedStatus];
$active = $selectedStatus === 'sold' ? 'sold_products' : 'products';

require __DIR__ . '/partials/header.php';

$products = [];
$statusCounts = [
    'all' => 0,
    'active' => 0,
    'sold' => 0,
    'reserved' => 0,
    'inactive' => 0,
];
$error = '';
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
$deleteListingToken = getCsrfToken('admin_delete_listing');

try {
    $pdo = getDbConnection();

    $countRows = $pdo->query(
        'SELECT status, COUNT(*) AS total
         FROM products
         GROUP BY status'
    )->fetchAll();

    foreach ($countRows as $row) {
        $status = $row['status'];
        $total = (int) $row['total'];

        if (array_key_exists($status, $statusCounts)) {
            $statusCounts[$status] = $total;
            $statusCounts['all'] += $total;
        }
    }

    $sql =
        'SELECT
            p.product_id,
            p.title,
            p.price,
            p.quantity,
            p.condition,
            p.status,
            p.location,
            p.active,
            p.created_at,
            c.category_name,
            u.first_name,
            u.last_name,
            u.email,
            primary_image.image_path AS primary_image_path,
            COALESCE(image_count.total_images, 0) AS total_images
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
         LEFT JOIN LATERAL (
            SELECT COUNT(*) AS total_images
            FROM product_images
            WHERE product_id = p.product_id
         ) image_count ON TRUE
        ';

    $params = [];

    if ($selectedStatus !== 'all') {
        $sql .= ' WHERE p.status = :status';
        $params[':status'] = $selectedStatus;
    }

    $sql .= ' ORDER BY p.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (Throwable $exception) {
    $error = 'Listings could not be loaded: ' . $exception->getMessage();
}

$statusClasses = [
    'active' => 'success',
    'sold' => 'secondary',
    'reserved' => 'warning',
    'inactive' => 'dark',
];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1"><?php echo htmlspecialchars($pageTitle); ?></h1>
        <p class="text-muted mb-0">Edit seller listings, prices, status, and visibility.</p>
    </div>
    <a class="btn btn-outline-primary btn-sm" href="/products" target="_blank">
        <i class="bi bi-box-arrow-up-right me-1"></i> View Public Listings
    </a>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    <?php foreach ($statusFilters as $status => $label): ?>
        <?php $href = $status === 'all' ? '/admin/products' : '/admin/products?status=' . urlencode($status); ?>
        <a class="btn btn-sm <?php echo $selectedStatus === $status ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo htmlspecialchars($href); ?>">
            <?php echo htmlspecialchars($label); ?>
            <span class="badge <?php echo $selectedStatus === $status ? 'text-bg-light text-primary' : 'text-bg-primary'; ?> ms-1">
                <?php echo number_format($statusCounts[$status]); ?>
            </span>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php elseif ($products === []): ?>
    <div class="alert alert-info">No <?php echo htmlspecialchars(strtolower($pageTitle)); ?> found.</div>
<?php else: ?>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php echo htmlspecialchars($pageTitle); ?></h5>
            <span class="badge bg-primary-subtle text-primary"><?php echo count($products); ?> total</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Listing</th>
                        <th>Seller</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Active</th>
                        <th class="text-end">Price</th>
                        <th class="pe-4 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $statusClass = $statusClasses[$product['status']] ?? 'secondary';
                        $isVisible = in_array($product['active'], [true, 1, '1', 't', 'true'], true);
                        $isPubliclyVisible = $isVisible && $product['status'] === 'active';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <img
                                        class="admin-listing-thumb"
                                        src="<?php echo htmlspecialchars(public_asset_url($product['primary_image_path'])); ?>"
                                        alt="<?php echo htmlspecialchars($product['title']); ?>"
                                    >
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($product['title']); ?></div>
                                        <div class="text-muted small">
                                            ID #<?php echo (int) $product['product_id']; ?> ·
                                            <?php echo (int) $product['total_images']; ?> image(s) ·
                                            Qty <?php echo (int) $product['quantity']; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars(trim(($product['first_name'] ?? '') . ' ' . ($product['last_name'] ?? '')) ?: 'Unknown seller'); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($product['email'] ?? 'No email'); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                            <td>
                                <span class="badge rounded-pill text-bg-<?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars(ucfirst($product['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($isVisible): ?>
                                    <span class="badge rounded-pill text-bg-success">Visible</span>
                                <?php else: ?>
                                    <span class="badge rounded-pill text-bg-secondary">Hidden</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-semibold">R<?php echo number_format((float) $product['price'], 2); ?></td>
                            <td class="pe-4 text-end">
                                <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    <?php if ($isPubliclyVisible): ?>
                                        <a class="btn btn-outline-secondary" href="/product/<?php echo (int) $product['product_id']; ?>" target="_blank">View</a>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary" type="button" disabled>Hidden</button>
                                    <?php endif; ?>
                                    <a class="btn btn-primary" href="/edit-product/<?php echo (int) $product['product_id']; ?>">Edit</a>
                                    <form
                                        class="d-inline"
                                        action="/admin/delete-listing"
                                        method="POST"
                                        onsubmit="return confirm('Delete this listing, its database records, and its stored images? This cannot be undone.');"
                                    >
                                        <input type="hidden" name="product_id" value="<?php echo (int) $product['product_id']; ?>">
                                        <input type="hidden" name="return_status" value="<?php echo htmlspecialchars($selectedStatus); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($deleteListingToken); ?>">
                                        <button class="btn btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

<?php
$pageTitle = 'Dashboard';
$active = 'dashboard';
require_once __DIR__ . '/../config/database.php';
require __DIR__ . '/partials/header.php';

$stats = [
    'active_users' => 0,
    'total_users' => 0,
    'active_listings' => 0,
    'total_listings' => 0,
    'total_orders' => 0,
    'pending_orders' => 0,
    'successful_payments' => 0,
    'revenue' => 0,
];
$recentOrders = [];
$recentListings = [];
$error = '';

try {
    $pdo = getDbConnection();

    $stats = $pdo->query(
        "SELECT
            (SELECT COUNT(*) FROM users WHERE active = TRUE) AS active_users,
            (SELECT COUNT(*) FROM users) AS total_users,
            (SELECT COUNT(*) FROM products WHERE active = TRUE AND status = 'active') AS active_listings,
            (SELECT COUNT(*) FROM products) AS total_listings,
            (SELECT COUNT(*) FROM orders) AS total_orders,
            (SELECT COUNT(*) FROM orders WHERE status = 'pending') AS pending_orders,
            (SELECT COUNT(*) FROM payments WHERE status = 'successful') AS successful_payments,
            COALESCE((SELECT SUM(amount) FROM payments WHERE status = 'successful'), 0) AS revenue"
    )->fetch() ?: $stats;

    $recentOrders = $pdo->query(
        'SELECT
            o.order_id,
            o.order_date,
            o.total_amount,
            o.status,
            buyer.first_name AS buyer_first_name,
            buyer.last_name AS buyer_last_name,
            seller.first_name AS seller_first_name,
            seller.last_name AS seller_last_name
         FROM orders o
         LEFT JOIN users buyer ON buyer.user_id = o.buyer_id
         LEFT JOIN users seller ON seller.user_id = o.seller_id
         ORDER BY o.order_date DESC
         LIMIT 5'
    )->fetchAll();

    $recentListings = $pdo->query(
        'SELECT
            p.product_id,
            p.title,
            p.price,
            p.status,
            p.active,
            p.created_at,
            c.category_name,
            u.first_name,
            u.last_name
         FROM products p
         LEFT JOIN categories c ON c.category_id = p.category_id
         LEFT JOIN users u ON u.user_id = p.seller_id
         ORDER BY p.created_at DESC
         LIMIT 5'
    )->fetchAll();
} catch (Throwable $exception) {
    $error = 'Dashboard data could not be loaded: ' . $exception->getMessage();
}

$statusClasses = [
    'active' => 'success',
    'sold' => 'secondary',
    'reserved' => 'warning',
    'inactive' => 'dark',
    'pending' => 'warning',
    'paid' => 'success',
    'shipped' => 'info',
    'completed' => 'primary',
    'cancelled' => 'danger',
];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">Dashboard</h1>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4 mb-4">
        <div class="col">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Active Users</h6>
                    <h2 class="card-title mb-1"><?php echo number_format((int) $stats['active_users']); ?></h2>
                    <p class="card-text text-muted small"><?php echo number_format((int) $stats['total_users']); ?> total users</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Active Listings</h6>
                    <h2 class="card-title mb-1"><?php echo number_format((int) $stats['active_listings']); ?></h2>
                    <p class="card-text text-muted small"><?php echo number_format((int) $stats['total_listings']); ?> total listings</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Orders</h6>
                    <h2 class="card-title mb-1"><?php echo number_format((int) $stats['total_orders']); ?></h2>
                    <p class="card-text text-warning small"><?php echo number_format((int) $stats['pending_orders']); ?> pending</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <h6 class="card-subtitle mb-2 text-muted">Successful Payments</h6>
                    <h2 class="card-title mb-1"><?php echo number_format((int) $stats['successful_payments']); ?></h2>
                    <p class="card-text text-success small">R<?php echo number_format((float) $stats['revenue'], 2); ?> received</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Orders</h5>
                    <a class="btn btn-sm btn-outline-primary" href="/admin/orders">View Orders</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recentOrders === []): ?>
                        <div class="p-4 text-muted">No orders found in the database.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Order</th>
                                        <th>Buyer</th>
                                        <th>Seller</th>
                                        <th>Status</th>
                                        <th class="pe-4 text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <?php $statusClass = $statusClasses[$order['status']] ?? 'secondary'; ?>
                                        <tr>
                                            <td class="ps-4 fw-medium">#C2M-<?php echo (int) $order['order_id']; ?></td>
                                            <td><?php echo htmlspecialchars(trim(($order['buyer_first_name'] ?? '') . ' ' . ($order['buyer_last_name'] ?? '')) ?: 'Unknown buyer'); ?></td>
                                            <td><?php echo htmlspecialchars(trim(($order['seller_first_name'] ?? '') . ' ' . ($order['seller_last_name'] ?? '')) ?: 'Unknown seller'); ?></td>
                                            <td><span class="badge rounded-pill text-bg-<?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></td>
                                            <td class="pe-4 text-end">R<?php echo number_format((float) $order['total_amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Listings</h5>
                    <a class="btn btn-sm btn-outline-primary" href="/admin/products">Manage Listings</a>
                </div>
                <div class="list-group list-group-flush">
                    <?php if ($recentListings === []): ?>
                        <div class="p-4 text-muted">No listings found in the database.</div>
                    <?php else: ?>
                        <?php foreach ($recentListings as $listing): ?>
                            <?php $statusClass = $statusClasses[$listing['status']] ?? 'secondary'; ?>
                            <a class="list-group-item list-group-item-action" href="/edit-product/<?php echo (int) $listing['product_id']; ?>">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($listing['title']); ?></div>
                                        <div class="text-muted small">
                                            <?php echo htmlspecialchars($listing['category_name'] ?? 'Uncategorized'); ?> ·
                                            <?php echo htmlspecialchars(trim(($listing['first_name'] ?? '') . ' ' . ($listing['last_name'] ?? '')) ?: 'Unknown seller'); ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge text-bg-<?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($listing['status'])); ?></span>
                                        <div class="small fw-semibold mt-1">R<?php echo number_format((float) $listing['price'], 2); ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

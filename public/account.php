<?php
require_once __DIR__ . '/../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../config/database.php';

$currentUser = require_user_from_jwt();
$account = null;
$stats = [
    'total_listings' => 0,
    'active_listings' => 0,
    'purchase_orders' => 0,
    'selling_orders' => 0,
    'active_orders' => 0,
];
$purchaseHistory = [];
$sellingHistory = [];
$activeOrders = [];
$error = '';
$statusClasses = [
    'pending' => 'warning',
    'paid' => 'success',
    'shipped' => 'info',
    'completed' => 'primary',
    'cancelled' => 'danger',
];

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT user_id, first_name, last_name, email, phone, active, created_at
         FROM users
         WHERE user_id = :user_id
           AND active = TRUE
         LIMIT 1'
    );
    $stmt->execute([':user_id' => $currentUser['user_id']]);
    $account = $stmt->fetch();

    if (!$account) {
        clear_user_jwt();
        header('Location: /login');
        exit;
    }

    $statsStmt = $pdo->prepare(
        'SELECT
            (SELECT COUNT(*) FROM products WHERE seller_id = :user_id) AS total_listings,
            (
                SELECT COUNT(*)
                FROM products
                WHERE seller_id = :user_id
                  AND status = :active_status
                  AND active = TRUE
            ) AS active_listings,
            (SELECT COUNT(*) FROM orders WHERE buyer_id = :user_id) AS purchase_orders,
            (SELECT COUNT(*) FROM orders WHERE seller_id = :user_id) AS selling_orders,
            (
                SELECT COUNT(*)
                FROM orders
                WHERE (buyer_id = :user_id OR seller_id = :user_id)
                  AND status IN (:pending_status, :paid_status, :shipped_status)
            ) AS active_orders'
    );
    $statsStmt->execute([
        ':active_status' => 'active',
        ':pending_status' => 'pending',
        ':paid_status' => 'paid',
        ':shipped_status' => 'shipped',
        ':user_id' => $currentUser['user_id'],
    ]);
    $stats = $statsStmt->fetch() ?: $stats;

    $orderSelect = 'SELECT
            o.order_id,
            o.buyer_id,
            o.seller_id,
            o.order_date,
            o.total_amount,
            o.status,
            o.shipping_city,
            o.shipping_province,
            buyer.first_name AS buyer_first_name,
            buyer.last_name AS buyer_last_name,
            seller.first_name AS seller_first_name,
            seller.last_name AS seller_last_name,
            COALESCE(order_items_summary.total_items, 0) AS total_items,
            COALESCE(order_items_summary.product_titles, \'No items listed\') AS product_titles,
            payment.status AS payment_status,
            payment.payment_method
         FROM orders o
         LEFT JOIN users buyer ON buyer.user_id = o.buyer_id
         LEFT JOIN users seller ON seller.user_id = o.seller_id
         LEFT JOIN LATERAL (
            SELECT
                SUM(oi.quantity) AS total_items,
                STRING_AGG(p.title || \' x\' || oi.quantity, \', \' ORDER BY p.title) AS product_titles
            FROM order_items oi
            LEFT JOIN products p ON p.product_id = oi.product_id
            WHERE oi.order_id = o.order_id
         ) order_items_summary ON TRUE
         LEFT JOIN LATERAL (
            SELECT status, payment_method
            FROM payments
            WHERE order_id = o.order_id
            ORDER BY paid_at DESC NULLS LAST, payment_id DESC
            LIMIT 1
         ) payment ON TRUE';

    $purchaseStmt = $pdo->prepare($orderSelect . '
         WHERE o.buyer_id = :user_id
         ORDER BY o.order_date DESC
         LIMIT 25');
    $purchaseStmt->execute([':user_id' => $currentUser['user_id']]);
    $purchaseHistory = $purchaseStmt->fetchAll();

    $sellingStmt = $pdo->prepare($orderSelect . '
         WHERE o.seller_id = :user_id
         ORDER BY o.order_date DESC
         LIMIT 25');
    $sellingStmt->execute([':user_id' => $currentUser['user_id']]);
    $sellingHistory = $sellingStmt->fetchAll();

    $activeStmt = $pdo->prepare($orderSelect . '
         WHERE (o.buyer_id = :user_id OR o.seller_id = :user_id)
           AND o.status IN (:pending_status, :paid_status, :shipped_status)
         ORDER BY o.order_date DESC
         LIMIT 25');
    $activeStmt->execute([
        ':pending_status' => 'pending',
        ':paid_status' => 'paid',
        ':shipped_status' => 'shipped',
        ':user_id' => $currentUser['user_id'],
    ]);
    $activeOrders = $activeStmt->fetchAll();
} catch (Throwable $exception) {
    $error = 'Account details could not be loaded: ' . $exception->getMessage();
}

if (!function_exists('render_order_table')) {
    function render_order_table(array $orders, array $statusClasses, string $emptyMessage, string $personColumn, ?int $currentUserId = null): void
    {
        if ($orders === []) {
            echo '<div class="alert alert-info mb-0">' . htmlspecialchars($emptyMessage) . '</div>';

            return;
        }
        ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order</th>
                        <th>Items</th>
                        <th><?php echo htmlspecialchars($personColumn); ?></th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $statusClass = $statusClasses[$order['status']] ?? 'secondary';
                        $resolvedPersonColumn = $personColumn;

                        if ($personColumn === 'With' && $currentUserId !== null) {
                            $resolvedPersonColumn = (int) $order['seller_id'] === $currentUserId ? 'Buyer' : 'Seller';
                        }

                        $personName = $resolvedPersonColumn === 'Buyer'
                            ? trim(($order['buyer_first_name'] ?? '') . ' ' . ($order['buyer_last_name'] ?? ''))
                            : trim(($order['seller_first_name'] ?? '') . ' ' . ($order['seller_last_name'] ?? ''));
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold">#C2M-<?php echo (int) $order['order_id']; ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars(date('d M Y H:i', strtotime($order['order_date']))); ?></div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($order['product_titles']); ?></div>
                                <div class="text-muted small"><?php echo (int) $order['total_items']; ?> item(s)</div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($personName ?: 'Unknown'); ?></div>
                                <?php if (!empty($order['shipping_city']) || !empty($order['shipping_province'])): ?>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars(trim(($order['shipping_city'] ?? '') . ', ' . ($order['shipping_province'] ?? ''), ', ')); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge rounded-pill text-bg-<?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></td>
                            <td>
                                <?php if ($order['payment_status']): ?>
                                    <div><?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars(strtoupper((string) $order['payment_method'])); ?></div>
                                <?php else: ?>
                                    <span class="text-muted">No payment</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-semibold">R<?php echo number_format((float) $order['total_amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Account | LocalMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/marketplace.css">
</head>
<body>
<div class="market-page">
    <?php include __DIR__ . '/includes/market_nav.php'; ?>

    <main class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">My account</h1>
                <p class="text-secondary mb-0">Track your account, purchases, sales, and active orders.</p>
            </div>
            <a class="btn btn-primary" href="/my-listings">View My Listings</a>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($account): ?>
            <div class="row g-4 align-items-start">
                <div class="col-lg-3">
                    <div class="market-card bg-white p-3 account-side-panel">
                        <div class="nav flex-column nav-pills account-tabs" id="account-tab" role="tablist" aria-orientation="vertical">
                            <button class="nav-link active text-start" id="details-tab" data-bs-toggle="pill" data-bs-target="#details-panel" type="button" role="tab" aria-controls="details-panel" aria-selected="true">Account details</button>
                            <button class="nav-link text-start" id="active-orders-tab" data-bs-toggle="pill" data-bs-target="#active-orders-panel" type="button" role="tab" aria-controls="active-orders-panel" aria-selected="false">Active orders <span class="badge text-bg-light ms-2"><?php echo (int) $stats['active_orders']; ?></span></button>
                            <button class="nav-link text-start" id="purchase-history-tab" data-bs-toggle="pill" data-bs-target="#purchase-history-panel" type="button" role="tab" aria-controls="purchase-history-panel" aria-selected="false">Purchase history <span class="badge text-bg-light ms-2"><?php echo (int) $stats['purchase_orders']; ?></span></button>
                            <button class="nav-link text-start" id="selling-history-tab" data-bs-toggle="pill" data-bs-target="#selling-history-panel" type="button" role="tab" aria-controls="selling-history-panel" aria-selected="false">Selling history <span class="badge text-bg-light ms-2"><?php echo (int) $stats['selling_orders']; ?></span></button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="tab-content" id="account-tab-content">
                        <section class="tab-pane fade show active market-card bg-white p-4" id="details-panel" role="tabpanel" aria-labelledby="details-tab" tabindex="0">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                                <div>
                                    <h2 class="h4 fw-bold mb-1">Account details</h2>
                                    <p class="text-secondary mb-0">Your profile and seller summary.</p>
                                </div>
                                <a class="btn btn-success" href="/create-product">Create New Listing</a>
                            </div>
                            <div class="row g-4">
                                <div class="col-xl-7">
                                    <dl class="account-details mb-0">
                                        <dt>First name</dt>
                                        <dd><?php echo htmlspecialchars($account['first_name']); ?></dd>

                                        <dt>Last name</dt>
                                        <dd><?php echo htmlspecialchars($account['last_name']); ?></dd>

                                        <dt>Email</dt>
                                        <dd><?php echo htmlspecialchars($account['email']); ?></dd>

                                        <dt>Phone</dt>
                                        <dd><?php echo htmlspecialchars($account['phone'] ?: 'Not added'); ?></dd>

                                        <dt>Joined</dt>
                                        <dd><?php echo htmlspecialchars(date('d M Y', strtotime($account['created_at']))); ?></dd>
                                    </dl>
                                </div>
                                <div class="col-xl-5">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="account-stat">
                                                <span>Total listings</span>
                                                <strong><?php echo (int) $stats['total_listings']; ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="account-stat">
                                                <span>Active listings</span>
                                                <strong><?php echo (int) $stats['active_listings']; ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="account-stat">
                                                <span>Purchases</span>
                                                <strong><?php echo (int) $stats['purchase_orders']; ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="account-stat">
                                                <span>Sales</span>
                                                <strong><?php echo (int) $stats['selling_orders']; ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="tab-pane fade market-card bg-white p-4" id="active-orders-panel" role="tabpanel" aria-labelledby="active-orders-tab" tabindex="0">
                            <h2 class="h4 fw-bold mb-1">Active orders</h2>
                            <p class="text-secondary mb-4">Orders currently pending, paid, or shipped.</p>
                            <?php render_order_table($activeOrders, $statusClasses, 'You do not have active orders right now.', 'With', (int) $currentUser['user_id']); ?>
                        </section>

                        <section class="tab-pane fade market-card bg-white p-4" id="purchase-history-panel" role="tabpanel" aria-labelledby="purchase-history-tab" tabindex="0">
                            <h2 class="h4 fw-bold mb-1">Purchase history</h2>
                            <p class="text-secondary mb-4">Orders you have placed as a buyer.</p>
                            <?php render_order_table($purchaseHistory, $statusClasses, 'You have not purchased anything yet.', 'Seller'); ?>
                        </section>

                        <section class="tab-pane fade market-card bg-white p-4" id="selling-history-panel" role="tabpanel" aria-labelledby="selling-history-tab" tabindex="0">
                            <h2 class="h4 fw-bold mb-1">Selling history</h2>
                            <p class="text-secondary mb-4">Orders received for your listings.</p>
                            <?php render_order_table($sellingHistory, $statusClasses, 'You have not sold anything yet.', 'Buyer'); ?>
                        </section>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$pageTitle = 'Orders';
$active = 'orders';
require_once __DIR__ . '/../config/database.php';
require __DIR__ . '/partials/header.php';

$orders = [];
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
    $stmt = $pdo->query(
        'SELECT
            o.order_id,
            o.order_date,
            o.total_amount,
            o.status,
            o.shipping_full_name,
            o.shipping_phone,
            o.shipping_address_line1,
            o.shipping_address_line2,
            o.shipping_city,
            o.shipping_province,
            o.shipping_postal_code,
            buyer.first_name AS buyer_first_name,
            buyer.last_name AS buyer_last_name,
            buyer.email AS buyer_email,
            seller.first_name AS seller_first_name,
            seller.last_name AS seller_last_name,
            seller.email AS seller_email,
            COALESCE(item_count.total_items, 0) AS total_items,
            payment.status AS payment_status,
            payment.payment_method,
            payment.amount AS payment_amount
         FROM orders o
         LEFT JOIN users buyer ON buyer.user_id = o.buyer_id
         LEFT JOIN users seller ON seller.user_id = o.seller_id
         LEFT JOIN LATERAL (
            SELECT COUNT(*) AS total_items
            FROM order_items
            WHERE order_id = o.order_id
         ) item_count ON TRUE
         LEFT JOIN LATERAL (
            SELECT status, payment_method, amount
            FROM payments
            WHERE order_id = o.order_id
            ORDER BY paid_at DESC NULLS LAST, payment_id DESC
            LIMIT 1
         ) payment ON TRUE
         ORDER BY o.order_date DESC'
    );
    $orders = $stmt->fetchAll();
} catch (Throwable $exception) {
    $error = 'Orders could not be loaded: ' . $exception->getMessage();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">Orders</h1>
        <p class="text-muted mb-0">Order records from PostgreSQL.</p>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php elseif ($orders === []): ?>
    <div class="alert alert-info">No orders found in the database.</div>
<?php else: ?>
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Orders</h5>
            <span class="badge bg-primary-subtle text-primary"><?php echo count($orders); ?> total</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Order</th>
                        <th>Buyer</th>
                        <th>Shipping</th>
                        <th>Seller</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th class="pe-4 text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php $statusClass = $statusClasses[$order['status']] ?? 'secondary'; ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">#C2M-<?php echo (int) $order['order_id']; ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars(date('d M Y H:i', strtotime($order['order_date']))); ?></div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars(trim(($order['buyer_first_name'] ?? '') . ' ' . ($order['buyer_last_name'] ?? '')) ?: 'Unknown buyer'); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($order['buyer_email'] ?? 'No email'); ?></div>
                            </td>
                            <td>
                                <?php if ($order['shipping_full_name']): ?>
                                    <div><?php echo htmlspecialchars($order['shipping_full_name']); ?></div>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars($order['shipping_address_line1']); ?>
                                        <?php if ($order['shipping_address_line2']): ?>
                                            <?php echo htmlspecialchars(', ' . $order['shipping_address_line2']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars(trim(($order['shipping_city'] ?? '') . ', ' . ($order['shipping_province'] ?? '') . ' ' . ($order['shipping_postal_code'] ?? ''))); ?>
                                    </div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($order['shipping_phone'] ?? ''); ?></div>
                                <?php else: ?>
                                    <span class="text-muted">No shipping details</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars(trim(($order['seller_first_name'] ?? '') . ' ' . ($order['seller_last_name'] ?? '')) ?: 'Unknown seller'); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($order['seller_email'] ?? 'No email'); ?></div>
                            </td>
                            <td><?php echo (int) $order['total_items']; ?></td>
                            <td><span class="badge rounded-pill text-bg-<?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></td>
                            <td>
                                <?php if ($order['payment_status']): ?>
                                    <div><?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></div>
                                    <div class="text-muted small">
                                        <?php echo htmlspecialchars(strtoupper((string) $order['payment_method'])); ?> ·
                                        R<?php echo number_format((float) $order['payment_amount'], 2); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">No payment</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 text-end fw-semibold">R<?php echo number_format((float) $order['total_amount'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

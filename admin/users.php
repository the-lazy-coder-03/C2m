<?php
$pageTitle = 'Users';
$active = 'users';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';
require __DIR__ . '/partials/header.php';

$users = [];
$error = '';
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
$statusToken = getCsrfToken('admin_user_status');

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query(
        'SELECT
            u.user_id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone,
            u.active,
            u.created_at,
            COALESCE(listing_count.total_listings, 0) AS total_listings,
            COALESCE(buyer_count.total_buyer_orders, 0) AS total_buyer_orders,
            COALESCE(seller_count.total_seller_orders, 0) AS total_seller_orders
         FROM users u
         LEFT JOIN LATERAL (
            SELECT COUNT(*) AS total_listings
            FROM products
            WHERE seller_id = u.user_id
         ) listing_count ON TRUE
         LEFT JOIN LATERAL (
            SELECT COUNT(*) AS total_buyer_orders
            FROM orders
            WHERE buyer_id = u.user_id
         ) buyer_count ON TRUE
         LEFT JOIN LATERAL (
            SELECT COUNT(*) AS total_seller_orders
            FROM orders
            WHERE seller_id = u.user_id
         ) seller_count ON TRUE
         ORDER BY u.created_at DESC'
    );
    $users = $stmt->fetchAll();
} catch (Throwable $exception) {
    $error = 'Users could not be loaded: ' . $exception->getMessage();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">Users</h1>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php elseif ($users === []): ?>
    <div class="alert alert-info">No users found in the database.</div>
<?php else: ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Users</h5>
            <span class="badge bg-primary-subtle text-primary"><?php echo count($users); ?> total</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">User</th>
                        <th>Phone</th>
                        <th>Marketplace Activity</th>
                        <th>Status</th>
                        <th class="pe-4">Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $isActive = in_array($user['active'], [true, 1, '1', 't', 'true'], true);
                        $roles = [];
                        if ((int) $user['total_listings'] > 0 || (int) $user['total_seller_orders'] > 0) {
                            $roles[] = 'Seller';
                        }
                        if ((int) $user['total_buyer_orders'] > 0) {
                            $roles[] = 'Buyer';
                        }
                        if ($roles === []) {
                            $roles[] = 'Registered user';
                        }
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold"><?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($user['phone'] ?: 'Not added'); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars(implode(' / ', $roles)); ?></div>
                                <div class="text-muted small">
                                    <?php echo (int) $user['total_listings']; ?> listing(s),
                                    <?php echo (int) $user['total_buyer_orders']; ?> buyer order(s),
                                    <?php echo (int) $user['total_seller_orders']; ?> seller order(s)
                                </div>
                            </td>
                            <td>
                                <form class="d-flex align-items-center gap-2" action="/admin/users/status" method="POST">
                                    <input type="hidden" name="user_id" value="<?php echo (int) $user['user_id']; ?>">
                                    <input type="hidden" name="active" value="0">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($statusToken); ?>">
                                    <div class="form-check form-switch mb-0">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            name="active"
                                            value="1"
                                            aria-label="Toggle account status for <?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?>"
                                            <?php echo $isActive ? 'checked' : ''; ?>
                                            onchange="this.form.submit();"
                                        >
                                    </div>
                                    <?php if ($isActive): ?>
                                        <span class="badge rounded-pill text-bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill text-bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </form>
                            </td>
                            <td class="pe-4"><?php echo htmlspecialchars(date('d M Y', strtotime($user['created_at']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

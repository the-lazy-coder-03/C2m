<?php
require_once __DIR__ . '/../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../config/database.php';

$currentUser = require_user_from_jwt();
$account = null;
$stats = [
    'total_listings' => 0,
    'active_listings' => 0,
];
$error = '';

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
        header('Location: login.php');
        exit;
    }

    $statsStmt = $pdo->prepare(
        'SELECT
            COUNT(*) AS total_listings,
            COUNT(*) FILTER (WHERE status = :active_status AND active = TRUE) AS active_listings
         FROM products
         WHERE seller_id = :seller_id'
    );
    $statsStmt->execute([
        ':active_status' => 'active',
        ':seller_id' => $currentUser['user_id'],
    ]);
    $stats = $statsStmt->fetch() ?: $stats;
} catch (Throwable $exception) {
    $error = 'Account details could not be loaded: ' . $exception->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Account | LocalMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/marketplace.css">
</head>
<body>
<div class="market-page">
    <?php include __DIR__ . '/includes/market_nav.php'; ?>

    <main class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">My account</h1>
                <p class="text-secondary mb-0">View your LocalMarket profile details and seller activity.</p>
            </div>
            <a class="btn btn-primary" href="my_listings.php">View My Listings</a>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif ($account): ?>
            <div class="row g-4">
                <div class="col-lg-7">
                    <section class="market-card bg-white p-4">
                        <h2 class="h4 fw-bold mb-4">Account details</h2>
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
                    </section>
                </div>

                <div class="col-lg-5">
                    <section class="market-card bg-white p-4">
                        <h2 class="h4 fw-bold mb-4">Seller summary</h2>
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
                        </div>
                        <a class="btn btn-success w-100 mt-4" href="sell_product.php">Create New Listing</a>
                    </section>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>

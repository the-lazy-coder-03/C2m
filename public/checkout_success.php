<?php
require_once __DIR__ . '/../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';

require_user_from_jwt();
startUserSession();

$success = $_SESSION['checkout_success'] ?? null;
unset($_SESSION['checkout_success']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Complete | LocalMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/marketplace.css">
</head>
<body>
<div class="market-page">
    <?php include __DIR__ . '/includes/market_nav.php'; ?>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <section class="market-card bg-white p-5 text-center">
                    <?php if (!$success): ?>
                        <h1 class="fw-bold">No recent checkout found</h1>
                        <p class="text-secondary">Browse products and add items to your cart to start a new order.</p>
                        <a class="btn btn-primary" href="/products">Browse Items</a>
                    <?php else: ?>
                        <span class="checkout-success-icon">✓</span>
                        <h1 class="fw-bold mt-3">Payment successful</h1>
                        <p class="text-secondary mb-4">Your order has been saved, payment was recorded, and the purchased item(s) are now marked as sold.</p>

                        <div class="checkout-success-summary text-start mx-auto mb-4">
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span>Order number(s)</span>
                                <strong>
                                    <?php echo htmlspecialchars(implode(', ', array_map(static fn ($id): string => '#C2M-' . (int) $id, $success['order_ids'] ?? []))); ?>
                                </strong>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span>Payment method</span>
                                <strong><?php echo htmlspecialchars($success['payment_method'] ?? 'Payment'); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between py-2">
                                <span>Total paid</span>
                                <strong class="text-success">R<?php echo number_format((float) ($success['total'] ?? 0), 2); ?></strong>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a class="btn btn-primary" href="/products">Continue Shopping</a>
                            <a class="btn btn-outline-secondary" href="/account">View Account</a>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>
</div>
</body>
</html>

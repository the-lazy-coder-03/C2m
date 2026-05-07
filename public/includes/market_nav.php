<?php
require_once __DIR__ . '/../../app/helpers/jwt_helper.php';

$navUser = current_user_from_jwt();
?>
<nav class="navbar navbar-expand-lg market-nav">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="../index.php">LocalMarket</a>
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <a class="btn btn-outline-secondary btn-sm" href="../index.php">Home</a>
            <a class="btn btn-outline-primary btn-sm" href="products.php">Browse Items</a>
            <a class="btn btn-success btn-sm" href="sell_product.php">Sell Item</a>
            <?php if ($navUser): ?>
                <a class="btn btn-outline-secondary btn-sm" href="account.php">Account</a>
                <a class="btn btn-outline-secondary btn-sm" href="my_listings.php">My Listings</a>
                <a class="btn btn-outline-danger btn-sm" href="logout.php">Logout</a>
            <?php else: ?>
                <a class="btn btn-outline-secondary btn-sm" href="login.php">Login</a>
                <a class="btn btn-primary btn-sm" href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

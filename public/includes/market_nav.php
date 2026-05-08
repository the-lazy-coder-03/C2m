<?php
require_once __DIR__ . '/../../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../../app/helpers/cart_helper.php';

$navUser = current_user_from_jwt();
$cartCount = $navUser ? cart_item_count() : 0;
?>
<nav class="market-nav">
    <div class="container">
        <div class="market-nav-shell">
            <a class="market-brand-mark" href="../index.php" aria-label="LocalMarket home">LocalMarket</a>
            <div class="market-nav-links">
                <a href="../index.php#home">Home</a>
                <a href="products.php">Browse Items</a>
                <a href="sell_product.php">Sell Item</a>
            <?php if ($navUser): ?>
                    <a href="cart.php">Cart (<?php echo $cartCount; ?>)</a>
                    <a href="account.php">Account</a>
                    <a href="my_listings.php">My Listings</a>
                    <a class="market-nav-register" href="logout.php">Logout</a>
            <?php else: ?>
                    <a href="login.php">Login</a>
                    <a class="market-nav-register" href="register.php">Register</a>
            <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

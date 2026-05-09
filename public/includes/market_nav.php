<?php
require_once __DIR__ . '/../../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../../app/helpers/cart_helper.php';

$navUser = current_user_from_jwt();
$cartCount = $navUser ? cart_item_count() : 0;
?>
<nav class="market-nav">
    <div class="container">
        <div class="market-nav-shell">
            <a class="market-brand-mark" href="/" aria-label="LocalMarket home">LocalMarket</a>
            <div class="market-nav-links">
                <a href="/#home">Home</a>
                <a href="/products">Browse Items</a>
                <a href="/create-product">Sell Item</a>
            <?php if ($navUser): ?>
                    <a href="/cart">Cart (<?php echo $cartCount; ?>)</a>
                    <a href="/account">Account</a>
                    <a href="/my-listings">My Listings</a>
                    <a class="market-nav-register" href="/logout">Logout</a>
            <?php else: ?>
                    <a href="/login">Login</a>
                    <a class="market-nav-register" href="/register">Register</a>
            <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

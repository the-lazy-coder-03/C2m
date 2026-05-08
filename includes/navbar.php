<?php
require_once __DIR__ . '/../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../app/helpers/cart_helper.php';

$navUser = current_user_from_jwt();
$cartCount = $navUser ? cart_item_count() : 0;
$isAdminLoggedIn = $isAdminLoggedIn ?? (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['admin_id']));
?>
<nav class="market-navbar" id="top">
    <div class="container">
        <div class="nav-shell">
            <a href="index.php" class="brand-mark" aria-label="LocalMarket home">
                <span class="brand-icon"><i class="bi bi-shop-window"></i></span>
                <span>LocalMarket</span>
            </a>

            <button
                class="nav-toggle"
                type="button"
                aria-expanded="false"
                aria-label="Toggle navigation"
                data-nav-toggle
            >
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="nav-links" data-nav-menu>
                <a href="index.php#home">Home</a>
                <a href="public/products.php">Browse Items</a>
                <a href="public/sell_product.php">Sell Item</a>
                <?php if ($isAdminLoggedIn): ?>
                    <a href="admin/dashboard.php" class="nav-admin">Admin Panel</a>
                <?php endif; ?>
                <?php if ($navUser): ?>
                    <a href="public/cart.php">Cart (<?php echo $cartCount; ?>)</a>
                    <a href="public/account.php">Account</a>
                    <a href="public/my_listings.php">My Listings</a>
                    <a href="public/logout.php" class="nav-register">Logout</a>
                <?php else: ?>
                    <a href="public/login.php">Login</a>
                    <a href="public/register.php" class="nav-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

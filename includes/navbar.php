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
            <a href="/" class="brand-mark" aria-label="LocalMarket home">
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
                <a href="/#home">Home</a>
                <a href="/products">Browse Items</a>
                <a href="/create-product">Sell Item</a>
                <?php if ($isAdminLoggedIn): ?>
                    <a href="/admin/dashboard" class="nav-admin">Admin Panel</a>
                <?php endif; ?>
                <?php if ($navUser): ?>
                    <a href="/cart">Cart (<?php echo $cartCount; ?>)</a>
                    <a href="/account">Account</a>
                    <a href="/my-listings">My Listings</a>
                    <a href="/logout" class="nav-register">Logout</a>
                <?php else: ?>
                    <a href="/login">Login</a>
                    <a href="/register" class="nav-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

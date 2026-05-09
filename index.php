<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/product_image_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdminLoggedIn = isset($_SESSION['admin_id']);
$featuredProducts = [];
$featuredProductsError = '';
$marketStats = [
    'active_listings' => 0,
    'active_sellers' => 0,
];

if (!function_exists('format_home_stat')) {
    function format_home_stat(int $value): string
    {
        return number_format($value);
    }
}

try {
    $pdo = getDbConnection();
    $statsStmt = $pdo->query(
        "SELECT
            (SELECT COUNT(*) FROM products WHERE active = TRUE AND status = 'active') AS active_listings,
            (
                SELECT COUNT(DISTINCT p.seller_id)
                FROM products p
                INNER JOIN users u ON u.user_id = p.seller_id
                WHERE p.active = TRUE
                  AND p.status = 'active'
                  AND u.active = TRUE
            ) AS active_sellers"
    );
    $marketStats = array_merge($marketStats, $statsStmt->fetch() ?: []);

    $stmt = $pdo->prepare(
        'SELECT
            p.product_id,
            p.title,
            p.price,
            p.location,
            c.category_name,
            primary_image.image_path AS primary_image_path
         FROM products p
         LEFT JOIN categories c ON c.category_id = p.category_id
         LEFT JOIN LATERAL (
            SELECT image_path
            FROM product_images
            WHERE product_id = p.product_id
            ORDER BY is_primary DESC, uploaded_at ASC, image_id ASC
            LIMIT 1
         ) primary_image ON TRUE
         WHERE p.active = TRUE AND p.status = :status
         ORDER BY p.created_at DESC
         LIMIT 6'
    );
    $stmt->execute([':status' => 'active']);
    $featuredProducts = $stmt->fetchAll();
} catch (Throwable $exception) {
    $featuredProductsError = 'Live listings could not be loaded right now.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocalMarket | South African C2C Marketplace</title>
    <meta name="description" content="LocalMarket helps South Africans buy and sell items locally with a trusted C2C marketplace experience.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<main>
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="hero-badge">
                        <i class="bi bi-geo-alt-fill"></i>
                        Built for buyers and sellers across South Africa
                    </span>
                    <h1 class="hero-title">Buy and Sell Anything Locally</h1>
                    <p class="hero-copy">South Africa's trusted C2C marketplace.</p>
                    <div class="hero-actions">
                        <a href="/products" class="btn btn-market-primary btn-lg btn-animated">Browse Items</a>
                        <a href="/create-product" class="btn btn-market-outline btn-lg btn-animated">Start Selling</a>
                    </div>

                    <div class="hero-search-card">
                        <label for="marketSearch" class="form-label">Search featured items</label>
                        <div class="hero-search-group">
                            <i class="bi bi-search"></i>
                            <input
                                type="text"
                                id="marketSearch"
                                class="form-control"
                                placeholder="Try iPhone, bike, laptop..."
                                data-search-input
                            >
                        </div>
                        <p class="search-status" data-search-status>
                            Showing <?php echo count($featuredProducts); ?> live listings ready for local deals.
                        </p>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="hero-showcase">
                        <div class="hero-card hero-card-primary">
                            <span class="hero-card-label">Live Marketplace</span>
                            <h2>Fast local deals with safer buyer confidence.</h2>
                            <div class="hero-stats">
                                <div>
                                    <strong><?php echo format_home_stat((int) $marketStats['active_listings']); ?></strong>
                                    <span>Active listings</span>
                                </div>
                                <div>
                                    <strong><?php echo format_home_stat((int) $marketStats['active_sellers']); ?></strong>
                                    <span>Active sellers</span>
                                </div>
                            </div>
                        </div>
                        <div class="hero-card hero-card-floating hero-card-secondary">
                            <span class="mini-pill"><i class="bi bi-shield-check"></i> Trusted transactions</span>
                            <p>Secure messaging, profile trust signals, and clear delivery options.</p>
                        </div>
                        <div class="hero-card hero-card-floating hero-card-tertiary">
                            <span class="mini-pill"><i class="bi bi-lightning-charge"></i> Quick listing flow</span>
                            <p>Post your item, reach nearby buyers, and close deals faster.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-space" id="categories">
        <div class="container">
            <div class="section-heading">
                <span class="section-kicker">Featured Categories</span>
                <h2>Popular categories people browse every day</h2>
                <p>Explore the most active sections on LocalMarket.</p>
            </div>

            <div class="row g-4">
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="feature-card" href="/products?category=Electronics">
                        <div class="feature-icon"><i class="bi bi-phone"></i></div>
                        <h3>Electronics</h3>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="feature-card" href="/products?category=Clothing">
                        <div class="feature-icon"><i class="bi bi-bag"></i></div>
                        <h3>Clothing</h3>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="feature-card" href="/products?category=Vehicle">
                        <div class="feature-icon"><i class="bi bi-car-front"></i></div>
                        <h3>Vehicles</h3>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="feature-card" href="/products?category=Furniture">
                        <div class="feature-icon"><i class="bi bi-lamp"></i></div>
                        <h3>Furniture</h3>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="feature-card" href="/products?category=Sport">
                        <div class="feature-icon"><i class="bi bi-bicycle"></i></div>
                        <h3>Sports</h3>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="feature-card" href="/products?category=Other">
                        <div class="feature-icon"><i class="bi bi-grid-3x3-gap"></i></div>
                        <h3>Other</h3>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section id="browse" class="section-space section-soft">
        <div class="container">
            <div class="section-heading">
                <span class="section-kicker">Featured Products</span>
                <h2>Fresh listings from sellers near you</h2>
                <p>These cards are loaded from products that sellers have uploaded.</p>
            </div>

            <?php if ($featuredProductsError !== ''): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($featuredProductsError); ?></div>
            <?php elseif ($featuredProducts === []): ?>
                <div class="empty-listings-card">
                    <h3>No listings uploaded yet</h3>
                    <p>Be the first seller to add a product to LocalMarket.</p>
                    <a href="/create-product" class="btn btn-market-primary btn-animated">Create First Listing</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($featuredProducts as $product): ?>
                        <?php
                        $searchText = strtolower(trim(
                            $product['title'] . ' ' .
                            ($product['location'] ?? '') . ' ' .
                            ($product['category_name'] ?? '')
                        ));
                        ?>
                        <div class="col-sm-6 col-xl-4 product-item" data-product-card data-product-name="<?php echo htmlspecialchars($searchText); ?>">
                            <article class="product-card">
                                <div class="product-image-wrap">
                                    <img
                                        src="<?php echo htmlspecialchars(public_asset_url($product['primary_image_path'])); ?>"
                                        alt="<?php echo htmlspecialchars($product['title']); ?>"
                                        class="img-fluid product-image"
                                        loading="lazy"
                                    >
                                </div>
                                <div class="product-body">
                                    <div class="product-meta">
                                        <span class="product-location">
                                            <i class="bi bi-geo-alt"></i>
                                            <?php echo htmlspecialchars($product['location'] ?: 'Location not listed'); ?>
                                        </span>
                                    </div>
                                    <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                                    <p class="product-price">R<?php echo number_format((float) $product['price'], 2); ?></p>
                                    <a href="/product/<?php echo (int) $product['product_id']; ?>" class="btn btn-market-outline btn-animated">View Item</a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="text-center mt-5">
                <a href="/products" class="btn btn-market-primary btn-lg btn-animated">View All Listings</a>
            </div>
        </div>
    </section>

    <section id="sell" class="section-space">
        <div class="container">
            <div class="section-heading">
                <span class="section-kicker">How It Works</span>
                <h2>Get started in three simple steps</h2>
                <p>LocalMarket keeps the selling journey simple for first-time and repeat sellers.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <article class="process-card">
                        <div class="process-icon"><i class="bi bi-person-plus"></i></div>
                        <h3>Create Account</h3>
                        <p>Join the marketplace with your basic details and set up your seller profile.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="process-card">
                        <div class="process-icon"><i class="bi bi-card-image"></i></div>
                        <h3>List Your Item</h3>
                        <p>Add photos, pricing, and a short description so local buyers know what you offer.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="process-card">
                        <div class="process-icon"><i class="bi bi-cash-coin"></i></div>
                        <h3>Sell &amp; Earn</h3>
                        <p>Connect with buyers, confirm the deal, and grow your side income with local sales.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="section-space section-soft" id="trust">
        <div class="container">
            <div class="section-heading">
                <span class="section-kicker">Why Buyers Trust Us</span>
                <h2>Confidence built into every local transaction</h2>
                <p>Trust indicators help buyers and sellers trade with more certainty.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <article class="trust-card">
                        <div class="trust-icon"><i class="bi bi-credit-card-2-front"></i></div>
                        <h3>Secure Payments</h3>
                        <p>Designed for safer payments with clear checkout expectations and transaction records.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="trust-card">
                        <div class="trust-icon"><i class="bi bi-patch-check"></i></div>
                        <h3>Verified Sellers</h3>
                        <p>Seller trust badges and profile checks help buyers identify reliable listings faster.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="trust-card">
                        <div class="trust-icon"><i class="bi bi-truck"></i></div>
                        <h3>Nationwide Delivery</h3>
                        <p>Flexible delivery and collection options support deals across major South African cities.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section id="cta" class="section-space">
        <div class="container">
            <div class="cta-panel">
                <div>
                    <span class="section-kicker section-kicker-light">Ready To Sell</span>
                    <h2>Start selling today</h2>
                    <p>Create your free LocalMarket account and reach nearby buyers in minutes.</p>
                </div>
                <a href="/register" class="btn btn-light btn-lg btn-animated cta-button">Create Free Account</a>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>

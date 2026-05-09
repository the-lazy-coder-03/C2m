<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found | LocalMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/marketplace.css">
</head>
<body>
<div class="market-page">
    <?php include __DIR__ . '/includes/market_nav.php'; ?>
    <main class="container py-5">
        <section class="market-card bg-white p-5 text-center mx-auto" style="max-width: 640px;">
            <p class="text-primary fw-bold mb-2">404</p>
            <h1 class="fw-bold mb-3">Page not found</h1>
            <p class="text-secondary mb-4">The page you are looking for does not exist or has moved.</p>
            <a class="btn btn-market-primary" href="/products">Browse Items</a>
        </section>
    </main>
</div>
</body>
</html>

<?php
$pageTitle = 'Products';
$active = 'products';
require __DIR__ . '/partials/header.php';
?>

<section class="page-head">
    <div>
        <h2>Products</h2>
        <p class="muted">Review and manage active listings.</p>
    </div>
    <button class="btn primary" type="button">Add Listing</button>
</section>

<section class="grid">
    <div class="card">
        <h3>Vintage Camera</h3>
        <p class="muted">Seller: Alex Kim</p>
        <div class="meta">
            <span class="status success">Active</span>
            <span class="pill">$120</span>
        </div>
    </div>
    <div class="card">
        <h3>Gaming Laptop</h3>
        <p class="muted">Seller: Maria Gomez</p>
        <div class="meta">
            <span class="status warning">Pending</span>
            <span class="pill">$980</span>
        </div>
    </div>
    <div class="card">
        <h3>Air Sneakers</h3>
        <p class="muted">Seller: Jamie Lee</p>
        <div class="meta">
            <span class="status neutral">Featured</span>
            <span class="pill">$74</span>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>

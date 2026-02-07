<?php
$pageTitle = 'Settings';
$active = 'settings';
require __DIR__ . '/partials/header.php';
?>

<section class="page-head">
    <div>
        <h2>Settings</h2>
        <p class="muted">Control platform preferences and admin rules.</p>
    </div>
    <button class="btn primary" type="button">Save Changes</button>
</section>

<section class="grid">
    <div class="card">
        <h3>Marketplace</h3>
        <p class="muted">Fees, categories, and listing rules.</p>
        <button class="btn" type="button">Manage</button>
    </div>
    <div class="card">
        <h3>Security</h3>
        <p class="muted">Password policies and access logs.</p>
        <button class="btn" type="button">Review</button>
    </div>
    <div class="card">
        <h3>Notifications</h3>
        <p class="muted">Email templates and alerts.</p>
        <button class="btn" type="button">Configure</button>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>

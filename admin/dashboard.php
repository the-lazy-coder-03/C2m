<?php
$pageTitle = 'Dashboard';
$active = 'dashboard';
require __DIR__ . '/partials/header.php';
?>

<section class="page-head">
    <div>
        <h2>Dashboard</h2>
        <p class="muted">Quick overview of marketplace activity.</p>
    </div>
    <button class="btn primary" type="button">Create Report</button>
</section>

<section class="grid">
    <div class="card">
        <p class="label">Active users</p>
        <p class="metric">1,248</p>
        <p class="muted">+12% this week</p>
    </div>
    <div class="card">
        <p class="label">Listings</p>
        <p class="metric">3,560</p>
        <p class="muted">78 new today</p>
    </div>
    <div class="card">
        <p class="label">Orders</p>
        <p class="metric">482</p>
        <p class="muted">Pending: 23</p>
    </div>
    <div class="card">
        <p class="label">Support tickets</p>
        <p class="metric">14</p>
        <p class="muted">Resolved: 9</p>
    </div>
</section>

<section class="card" style="margin-top: 24px;">
    <div class="card-header">
        <h3>Recent Orders</h3>
        <span class="pill">Last 7 days</span>
    </div>
    <div class="table">
        <div class="row head">
            <div>Order</div>
            <div>Buyer</div>
            <div>Status</div>
            <div>Total</div>
        </div>
        <div class="row">
            <div>#C2M-1032</div>
            <div>Jamie Lee</div>
            <div><span class="status success">Paid</span></div>
            <div>$129.00</div>
        </div>
        <div class="row">
            <div>#C2M-1031</div>
            <div>Samuel Watts</div>
            <div><span class="status warning">Pending</span></div>
            <div>$58.50</div>
        </div>
        <div class="row">
            <div>#C2M-1029</div>
            <div>Priya Singh</div>
            <div><span class="status neutral">Shipped</span></div>
            <div>$250.00</div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>

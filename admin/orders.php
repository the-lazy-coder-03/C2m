<?php
$pageTitle = 'Orders';
$active = 'orders';
require __DIR__ . '/partials/header.php';
?>

<section class="page-head">
    <div>
        <h2>Orders</h2>
        <p class="muted">Track customer purchases and fulfillment.</p>
    </div>
    <button class="btn primary" type="button">Export CSV</button>
</section>

<section class="card">
    <div class="table">
        <div class="row head">
            <div>Order</div>
            <div>Buyer</div>
            <div>Seller</div>
            <div>Status</div>
            <div>Total</div>
        </div>
        <div class="row">
            <div>#C2M-1040</div>
            <div>Priya Singh</div>
            <div>Alex Kim</div>
            <div><span class="status success">Paid</span></div>
            <div>$129.00</div>
        </div>
        <div class="row">
            <div>#C2M-1039</div>
            <div>Jordan Wells</div>
            <div>Maria Gomez</div>
            <div><span class="status warning">Pending</span></div>
            <div>$58.50</div>
        </div>
        <div class="row">
            <div>#C2M-1038</div>
            <div>Alex Kim</div>
            <div>Jamie Lee</div>
            <div><span class="status neutral">Shipped</span></div>
            <div>$74.00</div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>

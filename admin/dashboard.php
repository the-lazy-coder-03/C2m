<?php
$pageTitle = 'Dashboard';
$active = 'dashboard';
require __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-outline-primary">Create Report</button>
    </div>
</div>

<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-4">
    <div class="col">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Active users</h6>
                <h2 class="card-title mb-1">1,248</h2>
                <p class="card-text text-success small"><i class="bi bi-arrow-up"></i> +12% this week</p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Listings</h6>
                <h2 class="card-title mb-1">3,560</h2>
                <p class="card-text text-muted small">78 new today</p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Orders</h6>
                <h2 class="card-title mb-1">482</h2>
                <p class="card-text text-warning small">Pending: 23</p>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Support tickets</h6>
                <h2 class="card-title mb-1">14</h2>
                <p class="card-text text-primary small">Resolved: 9</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Orders</h5>
        <span class="badge bg-light text-dark">Last 7 days</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Order</th>
                        <th>Buyer</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="ps-4 fw-medium">#C2M-1032</td>
                        <td>Jamie Lee</td>
                        <td><span class="badge rounded-pill bg-success-subtle text-success">Paid</span></td>
                        <td class="pe-4 text-end">$129.00</td>
                    </tr>
                    <tr>
                        <td class="ps-4 fw-medium">#C2M-1031</td>
                        <td>Samuel Watts</td>
                        <td><span class="badge rounded-pill bg-warning-subtle text-warning">Pending</span></td>
                        <td class="pe-4 text-end">$58.50</td>
                    </tr>
                    <tr>
                        <td class="ps-4 fw-medium">#C2M-1029</td>
                        <td>Priya Singh</td>
                        <td><span class="badge rounded-pill bg-info-subtle text-info">Shipped</span></td>
                        <td class="pe-4 text-end">$250.00</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>

<?php
$pageTitle = 'Users';
$active = 'users';
require __DIR__ . '/partials/header.php';
?>

<section class="page-head">
    <div>
        <h2>Users</h2>
        <p class="muted">Manage buyers and sellers in the marketplace.</p>
    </div>
    <button class="btn primary" type="button">Add User</button>
</section>

<section class="card">
    <div class="table">
        <div class="row head">
            <div>Name</div>
            <div>Email</div>
            <div>Role</div>
            <div>Status</div>
        </div>
        <div class="row">
            <div>Alex Kim</div>
            <div>alex@example.com</div>
            <div>Seller</div>
            <div><span class="status success">Active</span></div>
        </div>
        <div class="row">
            <div>Maria Gomez</div>
            <div>maria@example.com</div>
            <div>Buyer</div>
            <div><span class="status neutral">Verified</span></div>
        </div>
        <div class="row">
            <div>Omar Patel</div>
            <div>omar@example.com</div>
            <div>Admin</div>
            <div><span class="status warning">Review</span></div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>

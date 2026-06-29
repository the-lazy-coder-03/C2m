<?php
session_start();
require_once __DIR__ . '/../app/helpers/admin_auth_helper.php';

if (current_admin_user() !== null) {
    header('Location: /admin/dashboard');
    exit;
}
$err = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login</title>
    <link rel="stylesheet" href="/assets/admin/css/admin.css" />
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="head">
            <div class="brand">
                <img class="brand-logo" src="/assets/admin/images/logo.svg" alt="Admin logo" />
                <div>
                    <h1>Admin Panel</h1>
                    <p>Sign in to manage the platform</p>
                </div>
            </div>
        </div>

        <?php if ($err === '1'): ?>
            <div class="error">Invalid username or password.</div>
        <?php endif; ?>

        <form method="POST" action="/admin/auth" autocomplete="off">
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" placeholder="Enter admin username" required />
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="password-toggle-wrap">
                    <input id="password" name="password" type="password" placeholder="••••••••" required data-password-input />
                    <button class="password-toggle-btn" type="button" data-password-toggle aria-label="Show password">Show</button>
                </div>
            </div>

            <div class="helper">
                <label class="inline">
                    <input type="checkbox" name="remember" /> Remember me
                </label>
            </div>

            <button class="btn primary" type="submit" style="width:100%;">Login</button>
        </form>
    </div>
</div>

<script src="/assets/admin/js/admin.js"></script>
</body>
</html>

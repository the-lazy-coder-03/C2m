<?php
session_start();
$adminBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/login.php')), '/');

if (isset($_SESSION['admin_id'])) {
    header('Location: ' . $adminBasePath . '/dashboard.php');
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
    <link rel="stylesheet" href="assets/css/admin.css" />
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="head">
            <div class="brand">
                <img class="brand-logo" src="assets/images/logo.svg" alt="Admin logo" />
                <div>
                    <h1>Admin Panel</h1>
                    <p>Sign in to manage the platform</p>
                </div>
            </div>
            <button class="btn" type="button" data-action="toggle-theme" aria-label="Toggle theme">🌓</button>
        </div>

        <?php if ($err === '1'): ?>
            <div class="error">Invalid username or password.</div>
        <?php endif; ?>

        <form method="POST" action="auth.php" autocomplete="off">
            <div class="field">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" placeholder="admin" required />
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="••••••••" required />
            </div>

            <div class="helper">
                <label class="inline">
                    <input type="checkbox" name="remember" /> Remember me
                </label>
                <span class="small">Tip: press 🌓 to toggle theme</span>
            </div>

            <button class="btn primary" type="submit" style="width:100%;">Login</button>

            <p class="small" style="margin-top:12px;">
                Demo login credentials are configured in <code>config/.env</code>.
            </p>
        </form>
    </div>
</div>

<script src="assets/js/admin.js"></script>
</body>
</html>

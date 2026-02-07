<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
$name = $_SESSION['admin_name'] ?? 'Admin';
$pageTitle = $pageTitle ?? 'Admin';
$active = $active ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle); ?> - Admin</title>
    <link rel="stylesheet" href="assets/css/admin.css" />
</head>
<body>
<div class="container">
    <aside class="sidebar">
        <div class="brand">
            <img class="brand-logo" src="assets/images/logo.svg" alt="Admin logo" />
            <div>
                <h1>Admin Panel</h1>
                <p>Welcome, <?php echo htmlspecialchars($name); ?></p>
            </div>
        </div>

        <nav class="nav" aria-label="Sidebar navigation">
            <a class="<?php echo $active === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php"><span class="dot"></span> Dashboard</a>
            <a class="<?php echo $active === 'users' ? 'active' : ''; ?>" href="users.php"><span class="dot"></span> Users</a>
            <a class="<?php echo $active === 'orders' ? 'active' : ''; ?>" href="orders.php"><span class="dot"></span> Orders</a>
            <a class="<?php echo $active === 'products' ? 'active' : ''; ?>" href="products.php"><span class="dot"></span> Products</a>
            <a class="<?php echo $active === 'settings' ? 'active' : ''; ?>" href="settings.php"><span class="dot"></span> Settings</a>
        </nav>
    </aside>

    <main class="main">
        <div class="topbar">
            <div class="search">
                <span class="icon" aria-hidden="true">🔎</span>
                <input data-role="search" type="text" placeholder="Search… (press /)" />
                <span class="kbd">/</span>
            </div>

            <div class="actions">
                <button class="btn" type="button" data-action="toggle-theme">🌓 Theme</button>
                <button class="btn danger" type="button" data-action="logout">Logout</button>
            </div>
        </div>

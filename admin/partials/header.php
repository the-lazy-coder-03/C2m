<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$adminBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/dashboard.php')), '/');
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . $adminBasePath . '/login.php');
    exit;
}
$pageTitle = $pageTitle ?? 'Admin';
$active = $active ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle); ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/admin/css/admin.css" />
</head>
<body class="bg-light">

<div class="wrapper d-flex align-items-stretch">
    <!-- Sidebar -->
    <nav id="sidebar" class="bg-dark text-white">
        <div class="p-4 pt-5">
            <div class="brand d-flex align-items-center mb-4">
                <img class="brand-logo me-2" src="/assets/admin/images/logo.svg" alt="Admin logo" style="width: 40px;" />
                <h4 class="mb-0 text-white">Admin Panel</h4>
            </div>
            
            <ul class="list-unstyled components mb-5 nav flex-column">
                <li class="nav-item mb-1">
                    <a href="/admin/dashboard" class="nav-link text-white <?php echo $active === 'dashboard' ? 'active bg-primary rounded' : ''; ?>">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="/admin/users" class="nav-link text-white <?php echo $active === 'users' ? 'active bg-primary rounded' : ''; ?>">
                        <i class="bi bi-people me-2"></i> Users
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="/admin/orders" class="nav-link text-white <?php echo $active === 'orders' ? 'active bg-primary rounded' : ''; ?>">
                        <i class="bi bi-cart me-2"></i> Orders
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="/admin/products" class="nav-link text-white <?php echo $active === 'products' ? 'active bg-primary rounded' : ''; ?>">
                        <i class="bi bi-box-seam me-2"></i> Listings
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="/admin/products?status=sold" class="nav-link text-white <?php echo $active === 'sold_products' ? 'active bg-primary rounded' : ''; ?>">
                        <i class="bi bi-bag-check me-2"></i> Sold Items
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="/admin/settings" class="nav-link text-white <?php echo $active === 'settings' ? 'active bg-primary rounded' : ''; ?>">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                </li>
            </ul>

        </div>
    </nav>

    <!-- Page Content -->
    <div id="content" class="p-4 p-md-5 pt-5 w-100">
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom mb-4 rounded shadow-sm">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn btn-primary">
                    <i class="bi bi-list"></i>
                    <span class="sr-only">Toggle Menu</span>
                </button>
                
                <div class="ms-auto d-flex align-items-center">
                    <button class="btn btn-outline-secondary me-2 btn-sm" type="button" data-action="toggle-theme">
                        <i class="bi bi-moon-stars"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" type="button" data-action="logout">Logout</button>
                </div>
            </div>
        </nav>

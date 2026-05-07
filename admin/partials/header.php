<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$adminBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/dashboard.php')), '/');
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . $adminBasePath . '/login.php');
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css" />
</head>
<body class="bg-light">

<div class="wrapper d-flex align-items-stretch">
    <!-- Sidebar -->
    <nav id="sidebar" class="bg-dark text-white">
        <div class="p-4 pt-5">
            <div class="brand d-flex align-items-center mb-4">
                <img class="brand-logo me-2" src="assets/images/logo.svg" alt="Admin logo" style="width: 40px;" />
                <h4 class="mb-0 text-white">Admin Panel</h4>
            </div>
            
            <ul class="list-unstyled components mb-5 nav flex-column">
                <li class="nav-item mb-1">
                    <a href="dashboard.php" class="nav-link text-white <?php echo $active === 'dashboard' ? 'active bg-primary rounded' : ''; ?>">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a href="#userSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="nav-link text-white dropdown-toggle">
                        <i class="bi bi-people me-2"></i> Users
                    </a>
                    <ul class="collapse list-unstyled ps-4 <?php echo $active === 'users' ? 'show' : ''; ?>" id="userSubmenu">
                        <li><a href="users.php" class="nav-link text-white-50">All Users</a></li>
                        <li><a href="#" class="nav-link text-white-50">Add User</a></li>
                    </ul>
                </li>
                <li class="nav-item mb-1">
                    <a href="#orderSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="nav-link text-white dropdown-toggle">
                        <i class="bi bi-cart me-2"></i> Orders
                    </a>
                    <ul class="collapse list-unstyled ps-4 <?php echo $active === 'orders' ? 'show' : ''; ?>" id="orderSubmenu">
                        <li><a href="orders.php" class="nav-link text-white-50">All Orders</a></li>
                        <li><a href="#" class="nav-link text-white-50">Pending</a></li>
                    </ul>
                </li>
                <li class="nav-item mb-1">
                    <a href="#productSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="nav-link text-white dropdown-toggle">
                        <i class="bi bi-box-seam me-2"></i> Products
                    </a>
                    <ul class="collapse list-unstyled ps-4 <?php echo $active === 'products' ? 'show' : ''; ?>" id="productSubmenu">
                        <li><a href="products.php" class="nav-link text-white-50">All Products</a></li>
                        <li><a href="#" class="nav-link text-white-50">Categories</a></li>
                    </ul>
                </li>
                <li class="nav-item mb-1">
                    <a href="settings.php" class="nav-link text-white <?php echo $active === 'settings' ? 'active bg-primary rounded' : ''; ?>">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                </li>
            </ul>

            <div class="footer mt-auto">
                <p class="small text-white-50">Logged in as: <?php echo htmlspecialchars($name); ?></p>
            </div>
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
                    <div class="search me-3 d-none d-md-block">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                            <input data-role="search" type="text" class="form-control bg-light border-0" placeholder="Search… (press /)" style="width: 250px;">
                        </div>
                    </div>
                    
                    <button class="btn btn-outline-secondary me-2 btn-sm" type="button" data-action="toggle-theme">
                        <i class="bi bi-moon-stars"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" type="button" data-action="logout">Logout</button>
                </div>
            </div>
        </nav>

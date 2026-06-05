<?php

$router->get('/', 'public/index.html');
$router->get('/index.html', 'public/index.html');
$router->get('/index.php', 'public/index.html');
$router->get('/favicon.ico', 'public/favicon.php');
$router->get('/favicon.png', 'public/favicon.php');
$router->get('/favicon.svg', 'public/favicon.php');

$router->get('/products', 'public/products.php');
$router->get('/products.php', 'public/products.php');
$router->get('/product/{id}', 'public/product.php');
$router->get('/product.php', 'public/product.php');

$router->get('/login', 'public/login.php');
$router->get('/login.php', 'public/login.php');
$router->post('/login', 'public/login_process.php');
$router->post('/login_process.php', 'public/login_process.php');

$router->get('/register', 'public/register.php');
$router->get('/register.php', 'public/register.php');
$router->post('/register', 'public/register_process.php');
$router->post('/register_process.php', 'public/register_process.php');

$router->get('/dashboard', 'public/account.php');
$router->get('/account', 'public/account.php');
$router->get('/account.php', 'public/account.php');
$router->get('/orders', 'public/account.php');

$router->get('/create-product', 'public/sell_product.php');
$router->get('/sell_product.php', 'public/sell_product.php');
$router->post('/create-product', 'public/sell_product.php');
$router->post('/sell_product.php', 'public/sell_product.php');

$router->get('/edit-product/{id}', 'admin/product_edit.php');
$router->post('/edit-product/{id}', 'admin/product_edit.php');
$router->get('/admin/product_edit.php', 'admin/product_edit.php');
$router->post('/admin/product_edit.php', 'admin/product_edit.php');
$router->post('/delete-product/{id}', 'public/delete_listing.php');
$router->post('/delete-listing', 'public/delete_listing.php');
$router->post('/delete_listing.php', 'public/delete_listing.php');

$router->get('/cart', 'public/cart.php');
$router->get('/cart.php', 'public/cart.php');
$router->post('/cart/add', 'public/add_to_cart.php');
$router->post('/add_to_cart.php', 'public/add_to_cart.php');
$router->post('/cart/remove', 'public/remove_from_cart.php');
$router->post('/remove_from_cart.php', 'public/remove_from_cart.php');

$router->get('/checkout', 'public/checkout.php');
$router->get('/checkout.php', 'public/checkout.php');
$router->post('/checkout', 'public/checkout.php');
$router->post('/checkout.php', 'public/checkout.php');
$router->get('/checkout-success', 'public/checkout_success.php');
$router->get('/checkout_success.php', 'public/checkout_success.php');

$router->get('/my-listings', 'public/my_listings.php');
$router->get('/my_listings.php', 'public/my_listings.php');
$router->get('/logout', 'public/logout.php');
$router->get('/logout.php', 'public/logout.php');

$router->get('/admin', 'admin/login.php');
$router->get('/admin/login', 'admin/login.php');
$router->get('/admin/login.php', 'admin/login.php');
$router->post('/admin/auth', 'admin/auth.php');
$router->post('/admin/auth.php', 'admin/auth.php');
$router->get('/admin/dashboard', 'admin/dashboard.php');
$router->get('/admin/dashboard.php', 'admin/dashboard.php');
$router->get('/admin/users', 'admin/users.php');
$router->get('/admin/users.php', 'admin/users.php');
$router->get('/admin/orders', 'admin/orders.php');
$router->get('/admin/orders.php', 'admin/orders.php');
$router->get('/admin/products', 'admin/products.php');
$router->get('/admin/products.php', 'admin/products.php');
$router->get('/admin/settings', 'admin/settings.php');
$router->get('/admin/settings.php', 'admin/settings.php');
$router->get('/admin/logout', 'admin/logout.php');
$router->get('/admin/logout.php', 'admin/logout.php');

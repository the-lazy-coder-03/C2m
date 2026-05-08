<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';

startUserSession();

$errors = $_SESSION['login_errors'] ?? [];
$old = $_SESSION['login_old'] ?? [];
unset($_SESSION['login_errors'], $_SESSION['login_old']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | LocalMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/marketplace.css">
</head>
<body>
<div class="market-page auth-page">
    <?php include __DIR__ . '/includes/market_nav.php'; ?>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-xl-5">
                <div class="mb-4 text-center">
                    <h1 class="fw-bold">Welcome back</h1>
                    <p class="text-secondary mb-0">Log in to buy, sell, and manage your LocalMarket listings.</p>
                </div>

                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form class="market-card bg-white p-4" action="login_process.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="login">Email address</label>
                        <input class="form-control" id="login" name="login" type="text" value="<?php echo htmlspecialchars($old['login'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <div class="password-toggle-wrap">
                            <input class="form-control" id="password" name="password" type="password" required data-password-input>
                            <button class="password-toggle-btn" type="button" data-password-toggle aria-label="Show password">Show</button>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 mt-2" type="submit">Login</button>

                    <p class="auth-switch text-center mb-0 mt-4">
                        New to LocalMarket?
                        <a href="register.php">Create an account</a>
                    </p>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="assets/js/marketplace.js"></script>
</body>
</html>

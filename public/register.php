<?php
require_once __DIR__ . '/../app/helpers/session_helper.php';

startUserSession();

$errors = $_SESSION['register_errors'] ?? [];
$old = $_SESSION['register_old'] ?? [];
unset($_SESSION['register_errors'], $_SESSION['register_old']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | LocalMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/marketplace.css">
</head>
<body>
<div class="market-page auth-page">
    <?php include __DIR__ . '/includes/market_nav.php'; ?>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-xl-6">
                <div class="mb-4 text-center">
                    <h1 class="fw-bold">Create your account</h1>
                    <p class="text-secondary mb-0">Join LocalMarket to buy and sell items across South Africa.</p>
                </div>

                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form class="market-card bg-white p-4" action="register_process.php" method="POST" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="first_name">First name</label>
                            <input class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="last_name">Last name</label>
                            <input class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="email">Email address</label>
                            <input class="form-control" id="email" name="email" type="email" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="phone">Phone number</label>
                            <input class="form-control" id="phone" name="phone" type="tel" value="<?php echo htmlspecialchars($old['phone'] ?? ''); ?>" placeholder="Optional">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="password">Password</label>
                            <input class="form-control" id="password" name="password" type="password" required>
                            <div class="form-help mt-2">Use at least 8 characters.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="confirm_password">Confirm password</label>
                            <input class="form-control" id="confirm_password" name="confirm_password" type="password" required>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 mt-4" type="submit">Create Account</button>

                    <p class="auth-switch text-center mb-0 mt-4">
                        Already have an account?
                        <a href="login.php">Login</a>
                    </p>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>

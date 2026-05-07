<?php
require_once __DIR__ . '/../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/product_image_helper.php';

startUserSession();

$currentUser = require_user_from_jwt();
$errors = [];
$successMessage = '';
$categories = [];
$maxImageCount = get_product_image_max_count();
$formData = [
    'category_id' => '',
    'title' => '',
    'description' => '',
    'price' => '',
    'quantity' => '1',
    'condition' => 'used',
    'location' => '',
];

try {
    $pdo = getDbConnection();
    $categories = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll();
} catch (Throwable $exception) {
    $errors[] = 'Database connection failed: ' . $exception->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo)) {
    $formData = [
        'category_id' => trim($_POST['category_id'] ?? ''),
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'price' => trim($_POST['price'] ?? ''),
        'quantity' => trim($_POST['quantity'] ?? '1'),
        'condition' => trim($_POST['condition'] ?? 'used'),
        'location' => trim($_POST['location'] ?? ''),
    ];

    if ($formData['category_id'] === '' || !ctype_digit($formData['category_id'])) {
        $errors[] = 'Please choose a valid category.';
    }

    if ($formData['title'] === '') {
        $errors[] = 'Product title is required.';
    }

    if ($formData['price'] === '' || !is_numeric($formData['price']) || (float) $formData['price'] < 0) {
        $errors[] = 'Price must be a valid positive number.';
    }

    if ($formData['quantity'] === '' || !ctype_digit($formData['quantity'])) {
        $errors[] = 'Quantity must be a whole number.';
    }

    if (!in_array($formData['condition'], ['new', 'used', 'refurbished'], true)) {
        $errors[] = 'Please choose a valid condition.';
    }

    if ($errors === []) {
        $sellerId = (int) $currentUser['user_id'];
        $savedImagePaths = [];

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO products
                    (category_id, seller_id, title, description, price, quantity, condition, status, location, active)
                 VALUES
                    (:category_id, :seller_id, :title, :description, :price, :quantity, :condition, :status, :location, TRUE)
                 RETURNING product_id'
            );
            $stmt->execute([
                ':category_id' => (int) $formData['category_id'],
                ':seller_id' => $sellerId,
                ':title' => $formData['title'],
                ':description' => $formData['description'],
                ':price' => (float) $formData['price'],
                ':quantity' => (int) $formData['quantity'],
                ':condition' => $formData['condition'],
                ':status' => 'active',
                ':location' => $formData['location'],
            ]);

            $productId = (int) $stmt->fetchColumn();
            $savedImagePaths = store_product_images($pdo, $productId, $_FILES['product_images'] ?? []);

            $pdo->commit();
            header('Location: product.php?id=' . $productId);
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            delete_uploaded_product_images($savedImagePaths);
            $errors[] = 'Product could not be created: ' . $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sell Item | LocalMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/marketplace.css">
</head>
<body>
<div class="market-page">
    <?php include __DIR__ . '/includes/market_nav.php'; ?>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="mb-4">
                    <h1 class="fw-bold">Create a product listing</h1>
                    <p class="text-secondary mb-0">Upload JPG, PNG, or WEBP images. The first image becomes the primary image.</p>
                </div>

                <?php if ($errors !== []): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($categories === []): ?>
                    <div class="alert alert-warning">Add categories to the database before creating a product.</div>
                <?php endif; ?>

                <form class="market-card bg-white p-4" method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="category_id">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Choose category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int) $category['category_id']; ?>" <?php echo $formData['category_id'] === (string) $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="condition">Condition</label>
                            <select class="form-select" id="condition" name="condition">
                                <option value="new" <?php echo $formData['condition'] === 'new' ? 'selected' : ''; ?>>New</option>
                                <option value="used" <?php echo $formData['condition'] === 'used' ? 'selected' : ''; ?>>Used</option>
                                <option value="refurbished" <?php echo $formData['condition'] === 'refurbished' ? 'selected' : ''; ?>>Refurbished</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="title">Product title</label>
                            <input class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($formData['title']); ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="price">Price</label>
                            <input class="form-control" id="price" name="price" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars($formData['price']); ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="quantity">Quantity</label>
                            <input class="form-control" id="quantity" name="quantity" type="number" min="0" value="<?php echo htmlspecialchars($formData['quantity']); ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="location">Location</label>
                            <input class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($formData['location']); ?>" placeholder="Cape Town">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="product_images">Product images</label>
                            <input
                                class="form-control"
                                id="product_images"
                                name="product_images[]"
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                multiple
                                data-image-input
                                data-max-images="<?php echo $maxImageCount; ?>"
                            >
                            <div class="form-help mt-2">
                                Select up to <?php echo $maxImageCount; ?> photos. The first photo becomes the main image.
                                Maximum size: 5MB per image.
                            </div>
                            <div class="image-preview-grid mt-3" data-image-preview aria-live="polite"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button class="btn btn-primary px-4" type="submit">Create Listing</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="assets/js/marketplace.js"></script>
</body>
</html>

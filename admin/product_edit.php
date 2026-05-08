<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/product_image_helper.php';

$adminBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/product_edit.php')), '/');

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . $adminBasePath . '/login.php');
    exit;
}

$pageTitle = 'Edit Listing';
$active = 'products';
$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = null;
$categories = [];
$images = [];
$errors = [];
$loadError = '';

if (!$productId) {
    $loadError = 'A valid listing ID is required.';
} else {
    try {
        $pdo = getDbConnection();
        $categories = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $submittedToken = $_POST['csrf_token'] ?? '';

            if (!is_string($submittedToken) || !hash_equals($_SESSION['admin_product_edit_token'] ?? '', $submittedToken)) {
                $errors[] = 'The form expired. Please try again.';
            }

            $formData = [
                'category_id' => trim($_POST['category_id'] ?? ''),
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'price' => trim($_POST['price'] ?? ''),
                'quantity' => trim($_POST['quantity'] ?? ''),
                'condition' => trim($_POST['condition'] ?? ''),
                'status' => trim($_POST['status'] ?? ''),
                'location' => trim($_POST['location'] ?? ''),
                'active' => isset($_POST['active']),
            ];

            if ($formData['category_id'] === '' || !ctype_digit($formData['category_id'])) {
                $errors[] = 'Choose a valid category.';
            }

            if ($formData['title'] === '') {
                $errors[] = 'Title is required.';
            }

            if ($formData['price'] === '' || !is_numeric($formData['price']) || (float) $formData['price'] < 0) {
                $errors[] = 'Price must be a valid positive number.';
            }

            if ($formData['quantity'] === '' || !ctype_digit($formData['quantity'])) {
                $errors[] = 'Quantity must be a whole number.';
            }

            if (!in_array($formData['condition'], ['new', 'used', 'refurbished'], true)) {
                $errors[] = 'Choose a valid condition.';
            }

            if (!in_array($formData['status'], ['active', 'sold', 'reserved', 'inactive'], true)) {
                $errors[] = 'Choose a valid status.';
            }

            if ($errors === []) {
                if ($formData['status'] === 'active') {
                    $formData['active'] = true;

                    if ((int) $formData['quantity'] < 1) {
                        $formData['quantity'] = '1';
                    }
                }

                $updateStmt = $pdo->prepare(
                    'UPDATE products
                     SET category_id = :category_id,
                         title = :title,
                         description = :description,
                         price = :price,
                         quantity = :quantity,
                         condition = :condition,
                         status = :status,
                         location = :location,
                         active = :active,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE product_id = :product_id'
                );
                $updateStmt->bindValue(':category_id', (int) $formData['category_id'], PDO::PARAM_INT);
                $updateStmt->bindValue(':title', $formData['title'], PDO::PARAM_STR);
                $updateStmt->bindValue(':description', $formData['description'], PDO::PARAM_STR);
                $updateStmt->bindValue(':price', (float) $formData['price']);
                $updateStmt->bindValue(':quantity', (int) $formData['quantity'], PDO::PARAM_INT);
                $updateStmt->bindValue(':condition', $formData['condition'], PDO::PARAM_STR);
                $updateStmt->bindValue(':status', $formData['status'], PDO::PARAM_STR);
                $updateStmt->bindValue(':location', $formData['location'], PDO::PARAM_STR);
                $updateStmt->bindValue(':active', $formData['active'], PDO::PARAM_BOOL);
                $updateStmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
                $updateStmt->execute();

                $_SESSION['admin_flash'] = [
                    'type' => 'success',
                    'message' => 'Listing updated successfully.',
                ];

                header('Location: ' . $adminBasePath . '/products.php');
                exit;
            }
        }

        $productStmt = $pdo->prepare(
            'SELECT
                p.product_id,
                p.category_id,
                p.seller_id,
                p.title,
                p.description,
                p.price,
                p.quantity,
                p.condition,
                p.status,
                p.location,
                p.active,
                p.created_at,
                p.updated_at,
                c.category_name,
                u.first_name,
                u.last_name,
                u.email
             FROM products p
             LEFT JOIN categories c ON c.category_id = p.category_id
             LEFT JOIN users u ON u.user_id = p.seller_id
             WHERE p.product_id = :product_id
             LIMIT 1'
        );
        $productStmt->execute([':product_id' => $productId]);
        $product = $productStmt->fetch();

        if (!$product) {
            $loadError = 'Listing was not found.';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $errors !== []) {
            $product = array_merge($product, $formData);
        }

        $imageStmt = $pdo->prepare(
            'SELECT image_path, is_primary
             FROM product_images
             WHERE product_id = :product_id
             ORDER BY is_primary DESC, uploaded_at ASC, image_id ASC'
        );
        $imageStmt->execute([':product_id' => $productId]);
        $images = $imageStmt->fetchAll();
    } catch (Throwable $exception) {
        $loadError = 'Listing could not be loaded: ' . $exception->getMessage();
    }
}

$_SESSION['admin_product_edit_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['admin_product_edit_token'];

require __DIR__ . '/partials/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2 mb-1">Edit Listing</h1>
        <p class="text-muted mb-0">Update seller listing details as the admin.</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="products.php">
        <i class="bi bi-arrow-left me-1"></i> Back to Listings
    </a>
</div>

<?php if ($loadError !== ''): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($loadError); ?></div>
<?php elseif ($product): ?>
    <?php $isProductActive = in_array($product['active'], [true, 1, '1', 't', 'true'], true); ?>
    <?php if ($errors !== []): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <form class="card shadow-sm border-0" method="POST">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Listing Details</h5>
                </div>
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="category_id">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int) $category['category_id']; ?>" <?php echo (int) $product['category_id'] === (int) $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status" data-role="listing-status" required>
                                <?php foreach (['active', 'reserved', 'sold', 'inactive'] as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo $product['status'] === $status ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucfirst($status)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="title">Title</label>
                            <input class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($product['title']); ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="price">Price</label>
                            <input class="form-control" id="price" name="price" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars((string) $product['price']); ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="quantity">Quantity</label>
                            <input class="form-control" id="quantity" name="quantity" type="number" min="0" value="<?php echo (int) $product['quantity']; ?>" data-role="listing-quantity" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="condition">Condition</label>
                            <select class="form-select" id="condition" name="condition" required>
                                <?php foreach (['new', 'used', 'refurbished'] as $condition): ?>
                                    <option value="<?php echo $condition; ?>" <?php echo $product['condition'] === $condition ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucfirst($condition)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="location">Location</label>
                            <input class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($product['location'] ?? ''); ?>">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" id="active" name="active" type="checkbox" data-role="listing-active" <?php echo $isProductActive ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="active">Visible on public marketplace</label>
                            </div>
                            <div class="form-text">Active listings are automatically made visible and need at least one item in stock.</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-end gap-2">
                    <a class="btn btn-outline-secondary" href="products.php">Cancel</a>
                    <button class="btn btn-primary" type="submit">Save Changes</button>
                </div>
            </form>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Seller</h5>
                </div>
                <div class="card-body">
                    <p class="fw-semibold mb-1">
                        <?php echo htmlspecialchars(trim(($product['first_name'] ?? '') . ' ' . ($product['last_name'] ?? '')) ?: 'Unknown seller'); ?>
                    </p>
                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($product['email'] ?? 'No email'); ?></p>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between">
                    <h5 class="mb-0">Images</h5>
                    <span class="badge bg-light text-dark"><?php echo count($images); ?></span>
                </div>
                <div class="card-body">
                    <?php if ($images === []): ?>
                        <p class="text-muted mb-0">This listing has no uploaded images.</p>
                    <?php else: ?>
                        <div class="row g-2">
                            <?php foreach ($images as $image): ?>
                                <?php $isPrimary = in_array($image['is_primary'], [true, 1, '1', 't', 'true'], true); ?>
                                <div class="col-6">
                                    <img
                                        class="admin-edit-image"
                                        src="<?php echo htmlspecialchars(public_asset_url($image['image_path'])); ?>"
                                        alt="<?php echo htmlspecialchars($product['title']); ?>"
                                    >
                                    <?php if ($isPrimary): ?>
                                        <span class="badge bg-primary mt-1">Primary</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

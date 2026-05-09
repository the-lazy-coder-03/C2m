<?php
require_once __DIR__ . '/../app/helpers/jwt_helper.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/product_image_helper.php';

startUserSession();

$currentUser = require_user_from_jwt();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /my-listings');
    exit;
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$productId = $productId ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$csrfToken = $_POST['csrf_token'] ?? '';

if (!$productId || !is_string($csrfToken) || !isValidCsrfToken('delete_listing', $csrfToken)) {
    $_SESSION['listing_flash'] = [
        'type' => 'danger',
        'message' => 'The delete request was not valid. Please try again.',
    ];
    header('Location: /my-listings');
    exit;
}

$imagePaths = [];

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    $ownerStmt = $pdo->prepare(
        'SELECT product_id, status
         FROM products
         WHERE product_id = :product_id
           AND seller_id = :seller_id
         LIMIT 1'
    );
    $ownerStmt->execute([
        ':product_id' => $productId,
        ':seller_id' => $currentUser['user_id'],
    ]);

    $listing = $ownerStmt->fetch();

    if (!$listing) {
        $pdo->rollBack();
        $_SESSION['listing_flash'] = [
            'type' => 'danger',
            'message' => 'Listing was not found, or you do not have permission to delete it.',
        ];
        header('Location: /my-listings');
        exit;
    }

    if ($listing['status'] === 'sold') {
        $pdo->rollBack();
        $_SESSION['listing_flash'] = [
            'type' => 'warning',
            'message' => 'Sold listings are kept for order records and cannot be deleted.',
        ];
        header('Location: /my-listings');
        exit;
    }

    $imageStmt = $pdo->prepare(
        'SELECT image_path
         FROM product_images
         WHERE product_id = :product_id'
    );
    $imageStmt->execute([':product_id' => $productId]);
    $imagePaths = array_column($imageStmt->fetchAll(), 'image_path');

    $deleteStmt = $pdo->prepare(
        'DELETE FROM products
         WHERE product_id = :product_id
           AND seller_id = :seller_id'
    );
    $deleteStmt->execute([
        ':product_id' => $productId,
        ':seller_id' => $currentUser['user_id'],
    ]);

    $pdo->commit();
    delete_uploaded_product_images($imagePaths);

    $_SESSION['listing_flash'] = [
        'type' => 'success',
        'message' => 'Listing deleted successfully.',
    ];
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['listing_flash'] = [
        'type' => 'danger',
        'message' => 'Listing could not be deleted: ' . $exception->getMessage(),
    ];
}

header('Location: /my-listings');
exit;

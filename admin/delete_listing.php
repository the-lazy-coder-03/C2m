<?php
require_once __DIR__ . '/../app/helpers/admin_auth_helper.php';
require_once __DIR__ . '/../app/helpers/session_helper.php';
require_once __DIR__ . '/../app/helpers/product_image_helper.php';
require_once __DIR__ . '/../config/database.php';

if (!function_exists('delete_admin_listing_images_strict')) {
    function delete_admin_listing_images_strict(array $relativePaths): void
    {
        $errors = [];

        foreach ($relativePaths as $relativePath) {
            $relativePath = trim((string) $relativePath, '/');

            if ($relativePath === '') {
                continue;
            }

            if (is_product_image_s3_enabled() && str_starts_with($relativePath, get_product_upload_relative_dir() . '/')) {
                try {
                    send_product_image_s3_request('DELETE', product_image_s3_object_url($relativePath));
                } catch (Throwable $exception) {
                    $errors[] = $relativePath . ': ' . $exception->getMessage();
                }

                continue;
            }

            $absolutePath = project_public_path($relativePath);

            if (is_file($absolutePath) && !unlink($absolutePath)) {
                $errors[] = $relativePath . ': local file could not be deleted.';
            }
        }

        if ($errors !== []) {
            throw new RuntimeException('Listing image cleanup failed: ' . implode(' ', $errors));
        }
    }
}

startUserSession();
require_admin_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/products');
    exit;
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$csrfToken = $_POST['csrf_token'] ?? '';
$returnStatus = strtolower(trim((string) ($_POST['return_status'] ?? 'all')));
$validReturnStatuses = ['all', 'active', 'sold', 'reserved', 'inactive'];
$returnUrl = '/admin/products';

if (in_array($returnStatus, $validReturnStatuses, true) && $returnStatus !== 'all') {
    $returnUrl .= '?status=' . urlencode($returnStatus);
}

if (!$productId || !is_string($csrfToken) || !isValidCsrfToken('admin_delete_listing', $csrfToken)) {
    $_SESSION['admin_flash'] = [
        'type' => 'danger',
        'message' => 'The delete request was not valid. Please try again.',
    ];

    header('Location: ' . $returnUrl);
    exit;
}

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    $productStmt = $pdo->prepare(
        'SELECT product_id, title
         FROM products
         WHERE product_id = :product_id
         FOR UPDATE'
    );
    $productStmt->execute([':product_id' => $productId]);
    $product = $productStmt->fetch();

    if (!$product) {
        throw new RuntimeException('Listing was not found.');
    }

    $imageStmt = $pdo->prepare(
        'SELECT image_path
         FROM product_images
         WHERE product_id = :product_id'
    );
    $imageStmt->execute([':product_id' => $productId]);
    $imagePaths = array_column($imageStmt->fetchAll(), 'image_path');

    $orderStmt = $pdo->prepare(
        'SELECT DISTINCT order_id
         FROM order_items
         WHERE product_id = :product_id'
    );
    $orderStmt->execute([':product_id' => $productId]);
    $orderIds = array_map('intval', array_column($orderStmt->fetchAll(), 'order_id'));

    $deleteOrderItemsStmt = $pdo->prepare(
        'DELETE FROM order_items
         WHERE product_id = :product_id'
    );
    $deleteOrderItemsStmt->execute([':product_id' => $productId]);

    if ($orderIds !== []) {
        $orderPlaceholders = [];
        $orderParams = [];

        foreach ($orderIds as $index => $orderId) {
            $placeholder = ':order_id_' . $index;
            $orderPlaceholders[] = $placeholder;
            $orderParams[$placeholder] = $orderId;
        }

        $orderInClause = implode(', ', $orderPlaceholders);

        $deleteEmptyOrdersStmt = $pdo->prepare(
            'DELETE FROM orders o
             WHERE o.order_id IN (' . $orderInClause . ')
               AND NOT EXISTS (
                    SELECT 1
                    FROM order_items oi
                    WHERE oi.order_id = o.order_id
               )'
        );
        $deleteEmptyOrdersStmt->execute($orderParams);

        $recalculateOrdersStmt = $pdo->prepare(
            'UPDATE orders o
             SET total_amount = remaining_items.total_amount
             FROM (
                SELECT order_id, SUM(quantity * price) AS total_amount
                FROM order_items
                WHERE order_id IN (' . $orderInClause . ')
                GROUP BY order_id
             ) remaining_items
             WHERE o.order_id = remaining_items.order_id'
        );
        $recalculateOrdersStmt->execute($orderParams);

        $recalculatePaymentsStmt = $pdo->prepare(
            'UPDATE payments p
             SET amount = o.total_amount
             FROM orders o
             WHERE p.order_id = o.order_id
               AND p.order_id IN (' . $orderInClause . ')'
        );
        $recalculatePaymentsStmt->execute($orderParams);
    }

    $deleteProductStmt = $pdo->prepare(
        'DELETE FROM products
         WHERE product_id = :product_id'
    );
    $deleteProductStmt->execute([':product_id' => $productId]);

    delete_admin_listing_images_strict($imagePaths);

    $pdo->commit();

    $_SESSION['admin_flash'] = [
        'type' => 'success',
        'message' => 'Listing "' . $product['title'] . '" was deleted from the database and storage.',
    ];
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['admin_flash'] = [
        'type' => 'danger',
        'message' => 'Listing could not be deleted: ' . $exception->getMessage(),
    ];
}

header('Location: ' . $returnUrl);
exit;

<?php
/**
 * OBU Student Marketplace - Delete Product Action (Module 4)
 */
require_once __DIR__ . '/../config/app.php';
require_login();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = current_user();
$userId = (int)$user['id'];

if ($productId <= 0 || !db()) {
    set_flash('danger', 'Invalid deletion request.');
    header('Location: ' . base_url('student/my-products.php'));
    exit;
}

// CSRF check
$csrf = $_GET['csrf_token'] ?? '';
if (!verify_csrf_token($csrf)) {
    set_flash('danger', t('error_csrf'));
    header('Location: ' . base_url('student/my-products.php'));
    exit;
}

try {
    // Check product and verify ownership
    $stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        set_flash('danger', 'Product not found.');
        header('Location: ' . base_url('student/my-products.php'));
        exit;
    }

    if ((int)$product['user_id'] !== $userId && $user['role'] !== ROLE_ADMIN) {
        set_flash('danger', t('error_unauthorized'));
        header('Location: ' . base_url('student/my-products.php'));
        exit;
    }

    // Retrieve images to unlink uploaded files
    $stmtImg = db()->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
    $stmtImg->execute([$productId]);
    $images = $stmtImg->fetchAll();

    foreach ($images as $img) {
        $imgPath = $img['image_path'];
        if (!str_starts_with($imgPath, 'assets/')) {
            $fileOnDisk = PRODUCTS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $imgPath;
            if (file_exists($fileOnDisk)) {
                @unlink($fileOnDisk);
            }
        }
    }

    // Delete product (cascade deletes images, favorites, etc.)
    $stmtDel = db()->prepare("DELETE FROM products WHERE id = ?");
    $stmtDel->execute([$productId]);

    log_activity('PRODUCT_DELETE', "Deleted product: {$product['title']} (ID: $productId)", $userId);
    set_flash('info', t('msg_product_deleted'));

} catch (Exception $e) {
    set_flash('danger', 'Error deleting listing: ' . $e->getMessage());
}

$back = $_SERVER['HTTP_REFERER'] ?? base_url('student/my-products.php');
header('Location: ' . $back);
exit;

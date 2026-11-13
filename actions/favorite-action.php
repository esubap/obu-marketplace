<?php
/**
 * OBU Student Marketplace - Favorite Toggle Action (Module 8)
 */
require_once __DIR__ . '/../config/app.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if (!is_logged_in()) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'status' => 'unauthenticated']);
        exit;
    }
    set_flash('warning', 'Please log in to save items to your favorites.');
    header('Location: ' . base_url('login.php'));
    exit;
}

$productId = isset($_REQUEST['product_id']) ? (int)$_REQUEST['product_id'] : 0;
$csrfToken = $_REQUEST['csrf_token'] ?? '';

if (!verify_csrf_token($csrfToken)) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    set_flash('danger', t('error_csrf'));
    header('Location: ' . base_url('products.php'));
    exit;
}

$userId = (int)current_user_id();

if ($productId > 0 && db()) {
    try {
        // Check if currently favorited
        $stmt = db()->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Remove from favorites
            $stmtDel = db()->prepare("DELETE FROM favorites WHERE id = ?");
            $stmtDel->execute([$existing['id']]);
            $isFavorited = false;
            $msg = t('msg_favorite_removed');
        } else {
            // Add to favorites (INSERT IGNORE)
            $stmtAdd = db()->prepare("INSERT IGNORE INTO favorites (user_id, product_id) VALUES (?, ?)");
            $stmtAdd->execute([$userId, $productId]);
            $isFavorited = true;
            $msg = t('msg_favorite_added');
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'is_favorited' => $isFavorited, 'message' => $msg]);
            exit;
        }

        set_flash('success', $msg);

    } catch (Exception $e) {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        set_flash('danger', 'Error updating favorites.');
    }
}

$back = $_SERVER['HTTP_REFERER'] ?? base_url('student/favorites.php');
header('Location: ' . $back);
exit;

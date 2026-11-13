<?php
/**
 * OBU Student Marketplace - Purchase Request Action Handler (Module 7)
 */
require_once __DIR__ . '/../config/app.php';
require_login();

$action = clean_input($_REQUEST['action'] ?? '');
$currentUser = current_user();
$userId = (int)$currentUser['id'];

if (!db()) {
    set_flash('danger', t('error_database'));
    header('Location: ' . base_url('student/purchase-requests.php'));
    exit;
}

// -------------------------------------------------------------
// 1. CREATE PURCHASE REQUEST
// -------------------------------------------------------------
if ($action === 'create_request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $productId = (int)($_POST['product_id'] ?? 0);
    $message = clean_input($_POST['message'] ?? '');

    if ($productId <= 0) {
        set_flash('danger', 'Invalid product selected.');
        header('Location: ' . base_url('products.php'));
        exit;
    }

    try {
        $stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product || $product['status'] !== 'active') {
            set_flash('danger', 'This item is no longer available for purchase.');
            header('Location: ' . base_url('products.php'));
            exit;
        }

        $sellerId = (int)$product['user_id'];
        if ($sellerId === $userId) {
            set_flash('danger', 'You cannot purchase your own product.');
            header('Location: ' . base_url('product-details.php?id=' . $productId));
            exit;
        }

        // Check if there is already an active pending request from this buyer
        $stmtCheck = db()->prepare("
            SELECT id FROM purchase_requests 
            WHERE product_id = ? AND buyer_id = ? AND status = 'pending'
        ");
        $stmtCheck->execute([$productId, $userId]);
        if ($stmtCheck->fetch()) {
            set_flash('warning', 'You already have a pending purchase request for this item.');
            header('Location: ' . base_url('student/purchase-requests.php'));
            exit;
        }

        // Insert request
        $stmtIns = db()->prepare("
            INSERT INTO purchase_requests (product_id, buyer_id, seller_id, message, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmtIns->execute([$productId, $userId, $sellerId, $message]);
        $reqId = (int)db()->lastInsertId();

        // Notify seller
        create_notification(
            $sellerId,
            'New Purchase Offer!',
            "{$currentUser['full_name']} sent a purchase request for '{$product['title']}'.",
            'purchase_request',
            $reqId,
            'student/purchase-requests.php'
        );

        log_activity('PURCHASE_REQ_SENT', "Purchase request sent for {$product['title']} (ID: $productId)", $userId);
        set_flash('success', t('msg_request_sent'));
        header('Location: ' . base_url('student/purchase-requests.php'));
        exit;

    } catch (Exception $e) {
        set_flash('danger', 'Error submitting purchase request: ' . $e->getMessage());
        header('Location: ' . base_url('product-details.php?id=' . $productId));
        exit;
    }
}

// -------------------------------------------------------------
// 2. ACCEPT PURCHASE REQUEST (Seller Only)
// -------------------------------------------------------------
if ($action === 'accept') {
    $reqId = (int)($_GET['id'] ?? 0);
    $csrf = $_GET['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf) || $reqId <= 0) {
        set_flash('danger', t('error_csrf'));
        header('Location: ' . base_url('student/purchase-requests.php'));
        exit;
    }

    try {
        $stmt = db()->prepare("
            SELECT pr.*, p.title AS product_title, p.user_id AS owner_id
            FROM purchase_requests pr
            JOIN products p ON pr.product_id = p.id
            WHERE pr.id = ?
        ");
        $stmt->execute([$reqId]);
        $req = $stmt->fetch();

        if (!$req || (int)$req['seller_id'] !== $userId) {
            set_flash('danger', t('error_unauthorized'));
            header('Location: ' . base_url('student/purchase-requests.php'));
            exit;
        }

        // Prevent conflicting accepted offers on the same product
        $stmtConflict = db()->prepare("
            SELECT id FROM purchase_requests 
            WHERE product_id = ? AND status = 'accepted' AND id != ?
        ");
        $stmtConflict->execute([$req['product_id'], $reqId]);
        if ($stmtConflict->fetch()) {
            set_flash('warning', 'You have already accepted another request for this item. Complete or decline it first.');
            header('Location: ' . base_url('student/purchase-requests.php'));
            exit;
        }

        $stmtUp = db()->prepare("UPDATE purchase_requests SET status = 'accepted' WHERE id = ?");
        $stmtUp->execute([$reqId]);

        // Notify Buyer
        create_notification(
            (int)$req['buyer_id'],
            'Offer Accepted!',
            "The seller accepted your purchase request for '{$req['product_title']}'. You can arrange meetup via chat.",
            'purchase_request',
            $reqId,
            'student/purchase-requests.php'
        );

        log_activity('PURCHASE_REQ_ACCEPTED', "Accepted offer #$reqId for {$req['product_title']}", $userId);
        set_flash('success', t('msg_request_accepted'));

    } catch (Exception $e) {
        set_flash('danger', 'Error updating request: ' . $e->getMessage());
    }

    header('Location: ' . base_url('student/purchase-requests.php'));
    exit;
}

// -------------------------------------------------------------
// 3. REJECT PURCHASE REQUEST (Seller Only)
// -------------------------------------------------------------
if ($action === 'reject') {
    $reqId = (int)($_GET['id'] ?? 0);
    $csrf = $_GET['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf) || $reqId <= 0) {
        set_flash('danger', t('error_csrf'));
        header('Location: ' . base_url('student/purchase-requests.php'));
        exit;
    }

    try {
        $stmt = db()->prepare("
            SELECT pr.*, p.title AS product_title
            FROM purchase_requests pr
            JOIN products p ON pr.product_id = p.id
            WHERE pr.id = ? AND pr.seller_id = ?
        ");
        $stmt->execute([$reqId, $userId]);
        $req = $stmt->fetch();

        if ($req) {
            $stmtUp = db()->prepare("UPDATE purchase_requests SET status = 'rejected' WHERE id = ?");
            $stmtUp->execute([$reqId]);

            // Notify buyer
            create_notification(
                (int)$req['buyer_id'],
                'Purchase Request Declined',
                "Your offer for '{$req['product_title']}' was declined by the seller.",
                'purchase_request',
                $reqId,
                'student/purchase-requests.php'
            );

            set_flash('info', t('msg_request_rejected'));
        } else {
            set_flash('danger', t('error_unauthorized'));
        }
    } catch (Exception $e) {
        set_flash('danger', 'Error processing request.');
    }

    header('Location: ' . base_url('student/purchase-requests.php'));
    exit;
}

// -------------------------------------------------------------
// 4. CANCEL PURCHASE REQUEST (Buyer Only)
// -------------------------------------------------------------
if ($action === 'cancel') {
    $reqId = (int)($_GET['id'] ?? 0);
    $csrf = $_GET['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf) || $reqId <= 0) {
        set_flash('danger', t('error_csrf'));
        header('Location: ' . base_url('student/purchase-requests.php'));
        exit;
    }

    try {
        $stmt = db()->prepare("SELECT * FROM purchase_requests WHERE id = ? AND buyer_id = ?");
        $stmt->execute([$reqId, $userId]);
        $req = $stmt->fetch();

        if ($req && in_array($req['status'], ['pending', 'accepted'])) {
            $stmtUp = db()->prepare("UPDATE purchase_requests SET status = 'cancelled' WHERE id = ?");
            $stmtUp->execute([$reqId]);
            set_flash('info', t('msg_request_cancelled'));
        } else {
            set_flash('danger', 'Cannot cancel this request.');
        }
    } catch (Exception $e) {
        set_flash('danger', 'Error cancelling request.');
    }

    header('Location: ' . base_url('student/purchase-requests.php'));
    exit;
}

// -------------------------------------------------------------
// 5. MARK AS COMPLETED (Seller marks transaction in-person done)
// -------------------------------------------------------------
if ($action === 'complete') {
    $reqId = (int)($_GET['id'] ?? 0);
    $csrf = $_GET['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf) || $reqId <= 0) {
        set_flash('danger', t('error_csrf'));
        header('Location: ' . base_url('student/purchase-requests.php'));
        exit;
    }

    try {
        db()->beginTransaction();

        $stmt = db()->prepare("
            SELECT pr.*, p.title AS product_title
            FROM purchase_requests pr
            JOIN products p ON pr.product_id = p.id
            WHERE pr.id = ? AND pr.seller_id = ?
        ");
        $stmt->execute([$reqId, $userId]);
        $req = $stmt->fetch();

        if ($req) {
            // Update request to completed
            $stmtUpReq = db()->prepare("UPDATE purchase_requests SET status = 'completed' WHERE id = ?");
            $stmtUpReq->execute([$reqId]);

            // Mark product as sold
            $stmtUpProd = db()->prepare("UPDATE products SET status = 'sold' WHERE id = ?");
            $stmtUpProd->execute([$req['product_id']]);

            // Auto-cancel any remaining pending requests for this product
            $stmtOther = db()->prepare("UPDATE purchase_requests SET status = 'cancelled' WHERE product_id = ? AND id != ? AND status = 'pending'");
            $stmtOther->execute([$req['product_id'], $reqId]);

            db()->commit();

            // Notify buyer
            create_notification(
                (int)$req['buyer_id'],
                'Transaction Completed!',
                "Your purchase for '{$req['product_title']}' has been completed. Thank you for trading on OBU Marketplace!",
                'transaction',
                $reqId,
                'student/purchase-requests.php'
            );

            log_activity('TRANSACTION_COMPLETE', "Completed sale for product {$req['product_title']} (Req: $reqId)", $userId);
            set_flash('success', 'Transaction successfully marked as completed! Item marked as sold.');
        } else {
            if (db()->inTransaction()) db()->rollBack();
            set_flash('danger', t('error_unauthorized'));
        }
    } catch (Exception $e) {
        if (db()->inTransaction()) db()->rollBack();
        set_flash('danger', 'Error completing transaction: ' . $e->getMessage());
    }

    header('Location: ' . base_url('student/purchase-requests.php'));
    exit;
}

header('Location: ' . base_url('student/purchase-requests.php'));
exit;

<?php
/**
 * OBU Student Marketplace - Internal Messaging Action Handler (Module 6)
 */
require_once __DIR__ . '/../config/app.php';
require_login();

$action = clean_input($_REQUEST['action'] ?? '');
$currentUser = current_user();
$userId = (int)$currentUser['id'];

if (!db()) {
    set_flash('danger', t('error_database'));
    header('Location: ' . base_url('student/messages.php'));
    exit;
}

// -------------------------------------------------------------
// ACTION: START OR OPEN CONVERSATION FOR PRODUCT
// -------------------------------------------------------------
if ($action === 'start') {
    $productId = (int)($_GET['product_id'] ?? 0);
    $csrf = $_GET['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf) || $productId <= 0) {
        set_flash('danger', 'Invalid chat request.');
        header('Location: ' . base_url('products.php'));
        exit;
    }

    try {
        $stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            set_flash('danger', 'Product not found.');
            header('Location: ' . base_url('products.php'));
            exit;
        }

        $sellerId = (int)$product['user_id'];

        // Cannot message yourself
        if ($sellerId === $userId) {
            set_flash('warning', 'You cannot start a conversation with yourself.');
            header('Location: ' . base_url('product-details.php?id=' . $productId));
            exit;
        }

        // Check if conversation already exists
        $stmtConv = db()->prepare("
            SELECT id FROM conversations 
            WHERE product_id = ? AND buyer_id = ? AND seller_id = ?
            LIMIT 1
        ");
        $stmtConv->execute([$productId, $userId, $sellerId]);
        $conv = $stmtConv->fetch();

        if ($conv) {
            $convId = (int)$conv['id'];
        } else {
            // Create new conversation
            $stmtNew = db()->prepare("
                INSERT INTO conversations (product_id, buyer_id, seller_id) 
                VALUES (?, ?, ?)
            ");
            $stmtNew->execute([$productId, $userId, $sellerId]);
            $convId = (int)db()->lastInsertId();

            // Initial automated system greeting or inquiry
            $stmtMsg = db()->prepare("
                INSERT INTO messages (conversation_id, sender_id, message, is_read)
                VALUES (?, ?, ?, 0)
            ");
            $stmtMsg->execute([
                $convId,
                $userId,
                "Hello! I am interested in your listing: '{$product['title']}'. Is it still available?"
            ]);

            // Notify seller
            create_notification(
                $sellerId,
                'New Message Inquiry',
                "{$currentUser['full_name']} sent a message regarding '{$product['title']}'.",
                'message',
                $convId,
                "student/conversation.php?id={$convId}"
            );
        }

        header('Location: ' . base_url('student/conversation.php?id=' . $convId));
        exit;

    } catch (Exception $e) {
        set_flash('danger', 'Error starting chat: ' . $e->getMessage());
        header('Location: ' . base_url('product-details.php?id=' . $productId));
        exit;
    }
}

// -------------------------------------------------------------
// ACTION: SEND MESSAGE
// -------------------------------------------------------------
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $convId = (int)($_POST['conversation_id'] ?? 0);
    $message = clean_input($_POST['message'] ?? '');

    if ($convId <= 0 || empty($message)) {
        set_flash('danger', 'Message cannot be empty.');
        header('Location: ' . base_url('student/messages.php'));
        exit;
    }

    try {
        // Fetch conversation and verify authorization
        $stmt = db()->prepare("
            SELECT c.*, p.title AS product_title,
                   u_buyer.status AS buyer_status, u_seller.status AS seller_status
            FROM conversations c
            JOIN products p ON c.product_id = p.id
            JOIN users u_buyer ON c.buyer_id = u_buyer.id
            JOIN users u_seller ON c.seller_id = u_seller.id
            WHERE c.id = ?
        ");
        $stmt->execute([$convId]);
        $conv = $stmt->fetch();

        if (!$conv) {
            set_flash('danger', 'Conversation not found.');
            header('Location: ' . base_url('student/messages.php'));
            exit;
        }

        $buyerId = (int)$conv['buyer_id'];
        $sellerId = (int)$conv['seller_id'];

        // Strict RBAC: Only buyer or seller can post
        if ($userId !== $buyerId && $userId !== $sellerId) {
            set_flash('danger', t('error_unauthorized'));
            header('Location: ' . base_url('student/messages.php'));
            exit;
        }

        // Restrict messaging if either account is suspended
        if ($conv['buyer_status'] === STATUS_SUSPENDED || $conv['seller_status'] === STATUS_SUSPENDED) {
            set_flash('danger', 'Messaging is disabled because an account in this conversation is suspended.');
            header('Location: ' . base_url('student/conversation.php?id=' . $convId));
            exit;
        }

        // Insert message
        $stmtMsg = db()->prepare("
            INSERT INTO messages (conversation_id, sender_id, message, is_read) 
            VALUES (?, ?, ?, 0)
        ");
        $stmtMsg->execute([$convId, $userId, $message]);

        // Touch conversation updated_at
        $stmtTouch = db()->prepare("UPDATE conversations SET updated_at = NOW() WHERE id = ?");
        $stmtTouch->execute([$convId]);

        // Send notification to recipient
        $recipientId = ($userId === $buyerId) ? $sellerId : $buyerId;
        create_notification(
            $recipientId,
            'New Chat Message',
            "{$currentUser['full_name']}: " . str_excerpt($message, 60),
            'message',
            $convId,
            "student/conversation.php?id={$convId}"
        );

        header('Location: ' . base_url('student/conversation.php?id=' . $convId));
        exit;

    } catch (Exception $e) {
        set_flash('danger', 'Failed to send message: ' . $e->getMessage());
        header('Location: ' . base_url('student/conversation.php?id=' . $convId));
        exit;
    }
}

// Default redirect
header('Location: ' . base_url('student/messages.php'));
exit;

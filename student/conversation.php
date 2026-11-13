<?php
/**
 * OBU Student Marketplace - Active Chat Conversation (Module 6)
 */
require_once __DIR__ . '/../config/app.php';
require_student();

$pageTitle = 'Chat Conversation';
$extraCss = ['dashboard.css'];
$user = current_user();
$userId = (int)$user['id'];
$convId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$conversation = null;
$messages = [];
$allConversations = [];

if (db() && $convId > 0) {
    try {
        // Fetch requested conversation
        $stmt = db()->prepare("
            SELECT c.*, p.title AS product_title, p.price AS product_price, p.status AS product_status,
                   u_buyer.id AS buyer_user_id, u_buyer.full_name AS buyer_name, u_buyer.department AS buyer_dept,
                   u_seller.id AS seller_user_id, u_seller.full_name AS seller_name, u_seller.department AS seller_dept,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) AS product_image
            FROM conversations c
            JOIN products p ON c.product_id = p.id
            JOIN users u_buyer ON c.buyer_id = u_buyer.id
            JOIN users u_seller ON c.seller_id = u_seller.id
            WHERE c.id = ?
        ");
        $stmt->execute([$convId]);
        $conversation = $stmt->fetch();

        if ($conversation) {
            // Strict Authorization: User must be buyer or seller
            if ($userId !== (int)$conversation['buyer_id'] && $userId !== (int)$conversation['seller_id'] && $user['role'] !== ROLE_ADMIN) {
                set_flash('danger', t('error_unauthorized'));
                header('Location: ' . base_url('student/messages.php'));
                exit;
            }

            // Mark incoming messages as read
            $stmtRead = db()->prepare("
                UPDATE messages 
                SET is_read = 1 
                WHERE conversation_id = ? AND sender_id != ? AND is_read = 0
            ");
            $stmtRead->execute([$convId, $userId]);

            // Fetch messages for this conversation
            $stmtMsg = db()->prepare("
                SELECT m.*, u.full_name AS sender_name
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = ?
                ORDER BY m.id ASC
            ");
            $stmtMsg->execute([$convId]);
            $messages = $stmtMsg->fetchAll();
        }

        // Fetch user's conversation list for sidebar
        $stmtList = db()->prepare("
            SELECT c.id, p.title AS product_title,
                   u_other.full_name AS other_user_name,
                   (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) AS last_message,
                   (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != ? AND is_read = 0) AS unread_count
            FROM conversations c
            JOIN products p ON c.product_id = p.id
            JOIN users u_other ON (CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END) = u_other.id
            WHERE c.buyer_id = ? OR c.seller_id = ?
            ORDER BY c.updated_at DESC
        ");
        $stmtList->execute([$userId, $userId, $userId, $userId]);
        $allConversations = $stmtList->fetchAll();

    } catch (Exception $e) {}
}

if (!$conversation) {
    set_flash('danger', 'Conversation not found.');
    header('Location: ' . base_url('student/messages.php'));
    exit;
}

$isBuyer = ($userId === (int)$conversation['buyer_id']);
$otherUserName = $isBuyer ? $conversation['seller_name'] : $conversation['buyer_name'];
$otherUserDept = $isBuyer ? $conversation['seller_dept'] : $conversation['buyer_dept'];
$pageTitle = 'Chat with ' . $otherUserName;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Chat Workspace Area -->
        <div class="col-lg-9">
            <div class="chat-wrapper shadow-sm">
                <!-- Left: Conversation Switcher -->
                <div class="chat-conversations-list">
                    <div class="p-3 border-bottom bg-surface-elevated">
                        <h6 class="fw-bold mb-0"><i class="bi bi-chat-left-text me-2 text-primary"></i> <?= t('messages_title') ?></h6>
                    </div>
                    <?php foreach ($allConversations as $cItem): ?>
                        <a href="<?= base_url('student/conversation.php?id=' . $cItem['id']) ?>" class="conversation-item <?= (int)$cItem['id'] === $convId ? 'active' : '' ?>">
                            <div class="user-avatar-circle flex-shrink-0" style="width: 38px; height: 38px; font-size: 0.95rem;">
                                <?= strtoupper(substr($cItem['other_user_name'], 0, 1)) ?>
                            </div>
                            <div class="overflow-hidden flex-grow-1">
                                <h6 class="mb-0 text-truncate fw-bold" style="font-size: 0.88rem;"><?= e($cItem['other_user_name']) ?></h6>
                                <div class="small text-muted text-truncate" style="font-size: 0.76rem;"><?= e($cItem['product_title']) ?></div>
                                <div class="small text-secondary text-truncate" style="font-size: 0.74rem;"><?= e($cItem['last_message'] ?? '...') ?></div>
                            </div>
                            <?php if ($cItem['unread_count'] > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?= $cItem['unread_count'] ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Right: Active Chat Stream -->
                <div class="chat-main-area">
                    <!-- Chat Header -->
                    <div class="chat-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="user-avatar-circle" style="width: 42px; height: 42px; font-size: 1rem;">
                                <?= strtoupper(substr($otherUserName, 0, 1)) ?>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold"><?= e($otherUserName) ?></h6>
                                <div class="small text-muted"><?= e($otherUserDept) ?> &bull; <?= e(t('university_name')) ?></div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="<?= base_url('product-details.php?id=' . $conversation['product_id']) ?>" class="btn btn-sm btn-outline-primary py-1 px-2.5">
                                <i class="bi bi-tag me-1"></i> <?= e($conversation['product_title']) ?> (<?= format_currency($conversation['product_price']) ?>)
                            </a>
                        </div>
                    </div>

                    <!-- Chat Messages Stream -->
                    <div class="chat-messages-stream" id="chatMessagesStream">
                        <div class="text-center my-2">
                            <span class="badge bg-light text-muted border" style="font-size: 0.72rem;">
                                Security notice: Meeting on campus in public spaces is recommended.
                            </span>
                        </div>

                        <?php foreach ($messages as $msg): ?>
                            <?php $isMine = ((int)$msg['sender_id'] === $userId); ?>
                            <div class="chat-bubble <?= $isMine ? 'chat-bubble-mine' : 'chat-bubble-theirs' ?>">
                                <div class="chat-text"><?= nl2br(e($msg['message'])) ?></div>
                                <div class="chat-timestamp">
                                    <?= date('g:i A', strtotime($msg['created_at'])) ?>
                                    <?php if ($isMine): ?>
                                        <i class="bi <?= $msg['is_read'] ? 'bi-check2-all text-info' : 'bi-check2' ?> ms-1"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Chat Input Bar -->
                    <form action="<?= base_url('actions/message-action.php') ?>" method="POST" class="chat-input-bar d-flex gap-2 align-items-center">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="send">
                        <input type="hidden" name="conversation_id" value="<?= $convId ?>">

                        <input type="text" name="message" class="form-control" placeholder="<?= t('type_your_message') ?>" required autocomplete="off" autofocus>
                        <button type="submit" class="btn btn-obu px-3 flex-shrink-0">
                            <i class="bi bi-send-fill me-1"></i> <?= t('send_message') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

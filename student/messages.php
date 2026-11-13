<?php
/**
 * OBU Student Marketplace - Messages Inbox (Module 6)
 */
require_once __DIR__ . '/../config/app.php';
require_student();

$pageTitle = t('messages_title');
$extraCss = ['dashboard.css'];
$user = current_user();
$userId = (int)$user['id'];

$conversations = [];

if (db()) {
    try {
        $stmt = db()->prepare("
            SELECT c.*, p.title AS product_title, p.price AS product_price,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) AS product_image,
                   u_other.id AS other_user_id, u_other.full_name AS other_user_name, u_other.department AS other_user_dept,
                   (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) AS last_message,
                   (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY id DESC LIMIT 1) AS last_message_time,
                   (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != ? AND is_read = 0) AS unread_count
            FROM conversations c
            JOIN products p ON c.product_id = p.id
            JOIN users u_other ON (CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END) = u_other.id
            WHERE c.buyer_id = ? OR c.seller_id = ?
            ORDER BY c.updated_at DESC
        ");
        $stmt->execute([$userId, $userId, $userId, $userId]);
        $conversations = $stmt->fetchAll();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface">
                <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                    <div>
                        <h3 class="fw-bold mb-1"><?= t('messages_title') ?></h3>
                        <p class="text-secondary mb-0">Direct student-to-student marketplace communications</p>
                    </div>
                </div>

                <?php if (empty($conversations)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-chat-dots fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="fw-bold">No conversations yet</h5>
                        <p class="text-muted"><?= t('no_conversations_yet') ?></p>
                        <a href="<?= base_url('products.php') ?>" class="btn btn-obu mt-2">
                            <i class="bi bi-shop me-1"></i> <?= t('nav_browse') ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush rounded-3 border">
                        <?php foreach ($conversations as $conv): ?>
                            <?php 
                                $isUnread = (int)$conv['unread_count'] > 0;
                                $pImg = !empty($conv['product_image']) 
                                    ? (str_starts_with($conv['product_image'], 'assets/') ? asset_url(substr($conv['product_image'], 7)) : upload_url('products/' . $conv['product_image']))
                                    : asset_url('images/placeholders/default-product.svg');
                            ?>
                            <a href="<?= base_url('student/conversation.php?id=' . $conv['id']) ?>" class="list-group-item list-group-item-action p-3 d-flex align-items-center gap-3 <?= $isUnread ? 'bg-surface-alt fw-bold' : '' ?>">
                                <div class="user-avatar-circle flex-shrink-0" style="width: 46px; height: 46px; font-size: 1.1rem;">
                                    <?= strtoupper(substr($conv['other_user_name'], 0, 1)) ?>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0 text-truncate fw-bold <?= $isUnread ? 'text-primary' : '' ?>"><?= e($conv['other_user_name']) ?></h6>
                                        <small class="text-muted" style="font-size: 0.75rem;"><?= time_ago($conv['last_message_time'] ?? $conv['created_at']) ?></small>
                                    </div>
                                    <div class="small text-muted text-truncate mb-1">
                                        <i class="bi bi-tag me-1 text-primary"></i> <?= e($conv['product_title']) ?> &bull; <strong class="text-primary"><?= format_currency($conv['product_price']) ?></strong>
                                    </div>
                                    <p class="small mb-0 text-truncate text-secondary" style="font-weight: <?= $isUnread ? '600' : '400' ?>;">
                                        <?= e($conv['last_message'] ?? 'Start chatting...') ?>
                                    </p>
                                </div>
                                <div class="flex-shrink-0 text-end">
                                    <img src="<?= $pImg ?>" class="rounded" style="width: 44px; height: 44px; object-fit: cover;" alt="Item">
                                    <?php if ($isUnread): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-danger rounded-pill"><?= $conv['unread_count'] ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

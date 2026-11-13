<?php
/**
 * OBU Student Marketplace - Notifications Center (Module 9)
 */
require_once __DIR__ . '/../config/app.php';
require_student();

$pageTitle = t('notifications_title');
$extraCss = ['dashboard.css'];
$user = current_user();
$userId = (int)$user['id'];

$notifications = [];

if (db()) {
    try {
        $stmt = db()->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY id DESC 
            LIMIT 50
        ");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll();
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

        <!-- Main Content Area -->
        <div class="col-lg-9">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface">
                <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                    <div>
                        <h3 class="fw-bold mb-1"><?= t('notifications_title') ?></h3>
                        <p class="text-secondary mb-0">System alerts, chat notices, and trade updates</p>
                    </div>
                    <?php if (!empty($notifications)): ?>
                        <a href="<?= base_url('actions/notification-action.php?action=mark_all_read&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-check2-all me-1"></i> <?= t('mark_all_read') ?>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (empty($notifications)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-bell-slash fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="fw-bold"><?= t('no_notifications') ?></h5>
                        <p class="text-muted">You are up to date on all campus marketplace activity.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush rounded-3 border">
                        <?php foreach ($notifications as $notif): ?>
                            <?php 
                                $isUnread = !$notif['is_read'];
                                $icon = 'bi-bell';
                                $iconColor = 'text-primary';
                                if ($notif['type'] === 'message') {
                                    $icon = 'bi-chat-dots-fill';
                                    $iconColor = 'text-info';
                                } elseif ($notif['type'] === 'purchase_request') {
                                    $icon = 'bi-bag-check-fill';
                                    $iconColor = 'text-success';
                                } elseif ($notif['type'] === 'moderation') {
                                    $icon = 'bi-shield-exclamation';
                                    $iconColor = 'text-warning';
                                } elseif ($notif['type'] === 'welcome') {
                                    $icon = 'bi-stars';
                                    $iconColor = 'text-warning';
                                }
                            ?>
                            <div class="notification-item <?= $isUnread ? 'unread' : '' ?>">
                                <div class="stat-card-icon stat-icon-blue flex-shrink-0" style="width: 42px; height: 42px;">
                                    <i class="bi <?= $icon ?> <?= $iconColor ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0 fw-bold <?= $isUnread ? 'text-primary' : '' ?>"><?= e($notif['title']) ?></h6>
                                        <small class="text-muted" style="font-size: 0.75rem;"><?= time_ago($notif['created_at']) ?></small>
                                    </div>
                                    <p class="small text-secondary mb-2"><?= e($notif['message']) ?></p>
                                    
                                    <div class="d-flex gap-2">
                                        <?php if (!empty($notif['link_url'])): ?>
                                            <a href="<?= base_url($notif['link_url']) ?>" class="btn btn-sm btn-obu-outline py-0 px-2" style="font-size: 0.78rem;">
                                                View &raquo;
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($isUnread): ?>
                                            <a href="<?= base_url('actions/notification-action.php?action=mark_read&id=' . $notif['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-light border py-0 px-2" style="font-size: 0.78rem;">
                                                Mark Read
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

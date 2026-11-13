<?php
/**
 * OBU Student Marketplace - Dashboard Sidebar
 */
$user = current_user();
if (!$user) return;

$currentScript = basename($_SERVER['PHP_SELF']);
$msgCount = get_unread_message_count($user['id']);
$notifCount = get_unread_notification_count($user['id']);

$menuItems = [
    [
        'label' => t('dashboard_overview'),
        'url'   => base_url('student/dashboard.php'),
        'icon'  => 'bi-grid-fill',
        'file'  => 'dashboard.php'
    ],
    [
        'label' => t('nav_browse'),
        'url'   => base_url('products.php'),
        'icon'  => 'bi-shop',
        'file'  => 'products.php'
    ],
    [
        'label' => t('nav_my_products'),
        'url'   => base_url('student/my-products.php'),
        'icon'  => 'bi-box-seam',
        'file'  => 'my-products.php'
    ],
    [
        'label' => t('nav_add_product'),
        'url'   => base_url('student/add-product.php'),
        'icon'  => 'bi-plus-circle',
        'file'  => 'add-product.php'
    ],
    [
        'label' => t('nav_requests'),
        'url'   => base_url('student/purchase-requests.php'),
        'icon'  => 'bi-bag-check',
        'file'  => 'purchase-requests.php'
    ],
    [
        'label' => t('nav_messages'),
        'url'   => base_url('student/messages.php'),
        'icon'  => 'bi-chat-dots',
        'file'  => ['messages.php', 'conversation.php'],
        'badge' => $msgCount
    ],
    [
        'label' => t('nav_favorites'),
        'url'   => base_url('student/favorites.php'),
        'icon'  => 'bi-heart',
        'file'  => 'favorites.php'
    ],
    [
        'label' => t('nav_notifications'),
        'url'   => base_url('student/notifications.php'),
        'icon'  => 'bi-bell',
        'file'  => 'notifications.php',
        'badge' => $notifCount
    ],
    [
        'label' => t('nav_profile'),
        'url'   => base_url('student/profile.php'),
        'icon'  => 'bi-person',
        'file'  => ['profile.php', 'edit-profile.php']
    ],
    [
        'label' => t('nav_settings'),
        'url'   => base_url('student/settings.php'),
        'icon'  => 'bi-gear',
        'file'  => 'settings.php'
    ]
];
?>
<div class="dashboard-sidebar">
    <!-- User Quick Info Header -->
    <div class="dashboard-user-card">
        <div class="user-avatar-circle <?= $user['role'] === ROLE_ADMIN ? 'bg-danger text-white' : '' ?>">
            <?php if (!empty($user['profile_image'])): ?>
                <img src="<?= upload_url('profiles/' . $user['profile_image']) ?>" alt="Avatar">
            <?php elseif ($user['role'] === ROLE_ADMIN): ?>
                A
            <?php else: ?>
                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div class="overflow-hidden">
            <h6 class="mb-0 fw-bold text-truncate" title="<?= e($user['full_name']) ?>">
                <?= $user['role'] === ROLE_ADMIN ? 'Admin' : e($user['full_name']) ?>
            </h6>
            <?php if ($user['role'] === ROLE_ADMIN): ?>
                <span class="badge bg-danger text-white" style="font-size: 0.72rem;">ADMIN</span>
                <div class="small text-muted text-truncate mt-1" style="font-size: 0.76rem;">Platform Administration</div>
            <?php else: ?>
                <span class="badge bg-primary text-white" style="font-size: 0.72rem;">STUDENT</span>
                <div class="small text-muted text-truncate mt-1" style="font-size: 0.76rem;"><?= e($user['department']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Language Switcher Pills in Sidebar (Instant 1-Click for All Dashboard Pages) -->
    <div class="p-2 mb-3 bg-light-subtle rounded-3 border">
        <div class="d-flex justify-content-between align-items-center mb-1 px-1">
            <span class="text-muted fw-bold" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">
                <i class="bi bi-translate me-1 text-primary"></i> <?= t('language') ?>
            </span>
            <span class="badge bg-primary text-white" style="font-size: 0.65rem;"><?= strtoupper(current_lang()) ?></span>
        </div>
        <div class="btn-group w-100 shadow-sm" role="group" aria-label="Sidebar Language Switcher">
            <a href="<?= get_lang_url('en') ?>" class="btn btn-sm <?= current_lang() === 'en' ? 'btn-primary active fw-bold' : 'btn-outline-secondary' ?>" style="font-size: 0.75rem; padding: 4px 6px;">
                🇬🇧 EN
            </a>
            <a href="<?= get_lang_url('am') ?>" class="btn btn-sm <?= current_lang() === 'am' ? 'btn-primary active fw-bold' : 'btn-outline-secondary' ?>" style="font-size: 0.75rem; padding: 4px 6px;">
                🇪🇹 አማ
            </a>
            <a href="<?= get_lang_url('om') ?>" class="btn btn-sm <?= current_lang() === 'om' ? 'btn-primary active fw-bold' : 'btn-outline-secondary' ?>" style="font-size: 0.75rem; padding: 4px 6px;">
                🌳 Oro
            </a>
        </div>
    </div>

    <!-- Navigation Menu -->
    <ul class="dashboard-menu">
        <?php foreach ($menuItems as $item): ?>
            <?php 
                $isActive = is_array($item['file']) 
                    ? in_array($currentScript, $item['file']) 
                    : ($currentScript === $item['file']);
            ?>
            <li class="dashboard-menu-item">
                <a href="<?= $item['url'] ?>" class="dashboard-menu-link <?= $isActive ? 'active' : '' ?>">
                    <span class="dashboard-menu-link-inner">
                        <i class="bi <?= $item['icon'] ?>"></i>
                        <span><?= $item['label'] ?></span>
                    </span>
                    <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
                        <span class="badge rounded-pill bg-danger"><?= $item['badge'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>

        <?php if ($user['role'] === ROLE_ADMIN): ?>
            <li class="dashboard-menu-item mt-3 pt-2 border-top">
                <a href="<?= base_url('admin/dashboard.php') ?>" class="dashboard-menu-link text-danger fw-bold">
                    <span class="dashboard-menu-link-inner">
                        <i class="bi bi-shield-lock-fill"></i>
                        <span><?= t('admin_dashboard') ?></span>
                    </span>
                </a>
            </li>
        <?php elseif ($user['role'] === ROLE_MODERATOR): ?>
            <li class="dashboard-menu-item mt-3 pt-2 border-top">
                <a href="<?= base_url('moderator/dashboard.php') ?>" class="dashboard-menu-link text-warning-emphasis fw-bold">
                    <span class="dashboard-menu-link-inner">
                        <i class="bi bi-shield-check"></i>
                        <span><?= t('moderator_dashboard') ?></span>
                    </span>
                </a>
            </li>
        <?php endif; ?>

        <li class="dashboard-menu-item mt-2 pt-2 border-top">
            <a href="<?= base_url('logout.php') ?>" class="dashboard-menu-link text-danger">
                <span class="dashboard-menu-link-inner">
                    <i class="bi bi-box-arrow-right"></i>
                    <span><?= t('nav_logout') ?></span>
                </span>
            </a>
        </li>
    </ul>
</div>

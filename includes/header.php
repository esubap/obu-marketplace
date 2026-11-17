<?php
/**
 * OBU Student Marketplace - Master Header
 */
require_once __DIR__ . '/../config/app.php';

$activeLang = current_lang();
$activeTheme = current_theme();
$currentUser = current_user();
$unreadMsgCount = $currentUser ? get_unread_message_count($currentUser['id']) : 0;
$unreadNotifCount = $currentUser ? get_unread_notification_count($currentUser['id']) : 0;
?>
<!DOCTYPE html>
<html lang="<?= e($activeLang) ?>" data-theme="<?= e($activeTheme) ?>" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= !empty($pageTitle) ? e($pageTitle) . ' | ' : '' ?><?= e(t('app_name', 'OBU Student Marketplace')) ?></title>
    <meta name="description" content="<?= e(t('sub_tagline')) ?>">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/responsive.css') ?>">
    <?php if (!empty($extraCss)): ?>
        <?php foreach ((array)$extraCss as $css): ?>
            <link rel="stylesheet" href="<?= asset_url('css/' . $css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Global JavaScript Configuration -->
    <script>
        window.OBU_BASE_URL = "<?= rtrim(base_url(), '/') ?>";
        window.OBU_CSRF_TOKEN = "<?= csrf_token() ?>";
        window.OBU_LANG = "<?= e($activeLang) ?>";
        window.OBU_THEME = "<?= e($activeTheme) ?>";
    </script>
</head>
<body>

<!-- TOP BAR: University Notice, Theme Selector, Language Selector -->
<div class="obu-topbar d-none d-md-block">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <span><i class="bi bi-mortarboard-fill text-primary me-1"></i> <?= e(t('university_name')) ?></span>
            <span class="text-muted">|</span>
            <span class="text-muted"><i class="bi bi-shield-check text-success me-1"></i> Verified Campus Community</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- Theme Selector Dropdown (Light, Bright, Dark) -->
            <div class="dropdown">
                <button class="theme-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php if ($activeTheme === 'dark'): ?>
                        <i class="bi bi-moon-stars-fill text-warning"></i> <?= t('theme_dark') ?>
                    <?php elseif ($activeTheme === 'bright'): ?>
                        <i class="bi bi-sun-fill text-amber"></i> <?= t('theme_bright') ?>
                    <?php else: ?>
                        <i class="bi bi-brightness-high text-primary"></i> <?= t('theme_light') ?>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><h6 class="dropdown-header"><?= t('theme') ?></h6></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $activeTheme === 'light' ? 'active' : '' ?>" href="<?= get_theme_url('light') ?>" data-set-theme="light">
                            <i class="bi bi-sun"></i> <?= t('theme_light') ?>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $activeTheme === 'bright' ? 'active' : '' ?>" href="<?= get_theme_url('bright') ?>" data-set-theme="bright">
                            <i class="bi bi-lightning-charge-fill text-warning"></i> <?= t('theme_bright') ?> <span class="badge bg-primary ms-auto" style="font-size: 0.65rem;">VIBRANT</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?= $activeTheme === 'dark' ? 'active' : '' ?>" href="<?= get_theme_url('dark') ?>" data-set-theme="dark">
                            <i class="bi bi-moon-stars"></i> <?= t('theme_dark') ?>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Language Selector Dropdown (EN, AM, OM) -->
            <div class="dropdown">
                <button class="lang-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span><?= SUPPORTED_LANGUAGES[$activeLang]['flag'] ?? '🌐' ?></span>
                    <span><?= SUPPORTED_LANGUAGES[$activeLang]['native'] ?? 'Language' ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><h6 class="dropdown-header"><?= t('language') ?></h6></li>
                    <?php foreach (SUPPORTED_LANGUAGES as $code => $info): ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2 <?= $activeLang === $code ? 'active' : '' ?>" href="<?= get_lang_url($code) ?>">
                                <span><?= $info['flag'] ?></span>
                                <span><?= $info['native'] ?> (<?= strtoupper($code) ?>)</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/navbar.php'; ?>

<!-- FLASH MESSAGES CONTAINER -->
<?php if (has_flash()): ?>
    <div class="container flash-container">
        <?php foreach (get_flash() as $flash): ?>
            <?php 
                $alertType = $flash['type'];
                if ($alertType === 'error') $alertType = 'danger';
                $iconClass = 'bi-info-circle-fill';
                if ($alertType === 'success') $iconClass = 'bi-check-circle-fill text-success';
                if ($alertType === 'danger') $iconClass = 'bi-exclamation-triangle-fill text-danger';
                if ($alertType === 'warning') $iconClass = 'bi-exclamation-circle-fill text-warning';
            ?>
            <div class="alert alert-<?= e($alertType) ?> alert-custom alert-dismissible fade show" role="alert">
                <i class="bi <?= $iconClass ?> fs-5"></i>
                <div class="flex-grow-1"><?= e($flash['message']) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main>

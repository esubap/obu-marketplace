<?php
/**
 * OBU Student Marketplace - User Settings & Preferences
 * Handles Themes (Light, Bright, Dark) and Languages (EN, AM, OM)
 */
require_once __DIR__ . '/../config/app.php';
require_student();

$pageTitle = t('nav_settings');
$extraCss = ['dashboard.css'];
$user = current_user();
$activeTheme = current_theme();
$activeLang = current_lang();

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
            <!-- 1. Visual Theme Settings (Light, Bright, Dark) -->
            <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-surface mb-4">
                <div class="d-flex align-items-center gap-3 pb-3 mb-4 border-bottom">
                    <div class="stat-card-icon stat-icon-amber">
                        <i class="bi bi-palette-fill"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?= t('preferred_theme') ?></h4>
                        <p class="text-secondary small mb-0">Select your preferred viewing experience for campus browsing</p>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Bright Mode Card -->
                    <div class="col-md-4">
                        <div class="card p-3 rounded-3 h-100 border text-center cursor-pointer <?= $activeTheme === 'bright' ? 'border-primary shadow' : '' ?>" style="border-width: 2px !important; background: #FFFFFF;">
                            <div class="text-amber fs-2 mb-2"><i class="bi bi-lightning-charge-fill text-warning"></i></div>
                            <h5 class="fw-bold mb-1" style="color: #0F172A;"><?= t('theme_bright') ?></h5>
                            <p class="small text-muted mb-3">High-vibrancy university energy, crystal clean mint accents & glowing cards.</p>
                            <a href="<?= base_url('actions/user-settings-action.php?action=set_theme&theme=bright') ?>" class="btn btn-sm <?= $activeTheme === 'bright' ? 'btn-primary' : 'btn-outline-primary' ?> w-100 mt-auto fw-bold" data-set-theme="bright">
                                <?= $activeTheme === 'bright' ? 'Active Theme' : 'Select Bright' ?>
                            </a>
                        </div>
                    </div>

                    <!-- Light Mode Card -->
                    <div class="col-md-4">
                        <div class="card p-3 rounded-3 h-100 border text-center cursor-pointer <?= $activeTheme === 'light' ? 'border-primary shadow' : '' ?>" style="border-width: 2px !important; background: #F8FAFC;">
                            <div class="text-primary fs-2 mb-2"><i class="bi bi-sun-fill"></i></div>
                            <h5 class="fw-bold mb-1" style="color: #1E293B;"><?= t('theme_light') ?></h5>
                            <p class="small text-muted mb-3">Classic understated academic layout with soft slate gray backgrounds.</p>
                            <a href="<?= base_url('actions/user-settings-action.php?action=set_theme&theme=light') ?>" class="btn btn-sm <?= $activeTheme === 'light' ? 'btn-primary' : 'btn-outline-secondary' ?> w-100 mt-auto fw-bold" data-set-theme="light">
                                <?= $activeTheme === 'light' ? 'Active Theme' : 'Select Light' ?>
                            </a>
                        </div>
                    </div>

                    <!-- Dark Mode Card -->
                    <div class="col-md-4">
                        <div class="card p-3 rounded-3 h-100 border text-center cursor-pointer <?= $activeTheme === 'dark' ? 'border-primary shadow' : '' ?>" style="border-width: 2px !important; background: #1E293B; color: #FFFFFF;">
                            <div class="text-warning fs-2 mb-2"><i class="bi bi-moon-stars-fill"></i></div>
                            <h5 class="fw-bold mb-1 text-white"><?= t('theme_dark') ?></h5>
                            <p class="small text-secondary mb-3">Deep navy & midnight slate designed for comfortable late-night library study.</p>
                            <a href="<?= base_url('actions/user-settings-action.php?action=set_theme&theme=dark') ?>" class="btn btn-sm <?= $activeTheme === 'dark' ? 'btn-primary' : 'btn-outline-light' ?> w-100 mt-auto fw-bold" data-set-theme="dark">
                                <?= $activeTheme === 'dark' ? 'Active Theme' : 'Select Dark' ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Language Preference -->
            <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-surface mb-4">
                <div class="d-flex align-items-center gap-3 pb-3 mb-4 border-bottom">
                    <div class="stat-card-icon stat-icon-blue">
                        <i class="bi bi-translate"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?= t('preferred_language') ?></h4>
                        <p class="text-secondary small mb-0">Select your primary language for navigation, alerts, and forms</p>
                    </div>
                </div>

                <form action="<?= base_url('actions/user-settings-action.php') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="set_language">

                    <div class="row g-3">
                        <?php foreach (SUPPORTED_LANGUAGES as $code => $info): ?>
                            <div class="col-md-4">
                                <label class="card p-3 rounded-3 border h-100 cursor-pointer d-flex flex-row align-items-center gap-3 <?= $activeLang === $code ? 'border-primary bg-surface-alt' : '' ?>" style="border-width: 2px !important;">
                                    <input type="radio" name="preferred_language" value="<?= $code ?>" class="form-check-input" <?= $activeLang === $code ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <div>
                                        <div class="fs-4"><?= $info['flag'] ?></div>
                                        <strong class="d-block"><?= $info['native'] ?></strong>
                                        <span class="small text-muted"><?= $info['name'] ?> (<?= strtoupper($code) ?>)</span>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>

            <!-- 3. Change Password -->
            <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-surface">
                <div class="d-flex align-items-center gap-3 pb-3 mb-4 border-bottom">
                    <div class="stat-card-icon stat-icon-purple">
                        <i class="bi bi-key-fill"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?= t('change_password') ?></h4>
                        <p class="text-secondary small mb-0">Update your account authentication credentials</p>
                    </div>
                </div>

                <form action="<?= base_url('actions/user-settings-action.php') ?>" method="POST" class="needs-validation">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="row g-3" style="max-width: 600px;">
                        <div class="col-12">
                            <label class="form-label fw-bold"><?= t('current_password') ?> *</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold"><?= t('new_password') ?> *</label>
                            <input type="password" name="new_password" class="form-control" minlength="8" required>
                            <div class="form-text small"><?= t('auth_password_req') ?></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold"><?= t('confirm_new_password') ?> *</label>
                            <input type="password" name="confirm_new_password" class="form-control" minlength="8" required>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-obu px-4 py-2.5">
                                <i class="bi bi-shield-check me-1"></i> Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

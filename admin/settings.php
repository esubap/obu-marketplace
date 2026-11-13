<?php
/**
 * OBU Student Marketplace - System Settings (Admin)
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$pageTitle = t('system_settings');
$extraCss = ['dashboard.css'];
$currentUser = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $settingsKeys = [
        'site_name',
        'university_name',
        'default_currency',
        'contact_email',
        'contact_phone',
        'campuses_list',
        'max_upload_size_mb',
        'theme_mode_default',
        'enable_registration'
    ];

    try {
        $stmt = db()->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        foreach ($settingsKeys as $k) {
            $val = clean_input($_POST[$k] ?? '');
            $stmt->execute([$k, $val]);
        }

        log_activity('ADMIN_SETTINGS_UPDATE', 'Updated system settings', (int)$currentUser['id']);
        set_flash('success', 'System settings saved successfully.');

    } catch (Exception $e) {
        set_flash('danger', 'Error updating settings: ' . $e->getMessage());
    }

    header('Location: ' . base_url('admin/settings.php'));
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-surface mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-gear-fill text-secondary me-2"></i> <?= t('system_settings') ?></h3>
                <p class="text-secondary mb-0">Platform branding, defaults, currency, and campus boundaries</p>
            </div>
            <a href="<?= base_url('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
                &larr; Admin Overview
            </a>
        </div>

        <form action="<?= base_url('admin/settings.php') ?>" method="POST" class="needs-validation">
            <?= csrf_field() ?>

            <div class="row g-4" style="max-width: 800px;">
                <!-- Site Name -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Platform Brand Name</label>
                    <input type="text" name="site_name" class="form-control" value="<?= e(get_setting('site_name', APP_NAME)) ?>" required>
                </div>

                <!-- University Name -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">University Name</label>
                    <input type="text" name="university_name" class="form-control" value="<?= e(get_setting('university_name', UNIVERSITY_NAME)) ?>" required>
                </div>

                <!-- Currency -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Default Currency Code</label>
                    <input type="text" name="default_currency" class="form-control" value="<?= e(get_setting('default_currency', DEFAULT_CURRENCY)) ?>" required>
                </div>

                <!-- Default Theme Mode -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Default Visual Theme</label>
                    <select name="theme_mode_default" class="form-select">
                        <option value="bright" <?= get_setting('theme_mode_default', 'bright') === 'bright' ? 'selected' : '' ?>>Bright Mode (Vibrant Campus)</option>
                        <option value="light" <?= get_setting('theme_mode_default') === 'light' ? 'selected' : '' ?>>Light Mode (Classic Slate)</option>
                        <option value="dark" <?= get_setting('theme_mode_default') === 'dark' ? 'selected' : '' ?>>Dark Mode (Midnight)</option>
                    </select>
                </div>

                <!-- Support Email -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Support Email</label>
                    <input type="email" name="contact_email" class="form-control" value="<?= e(get_setting('contact_email', 'marketplace@obu.edu.et')) ?>" required>
                </div>

                <!-- Support Phone -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Support Hotline</label>
                    <input type="text" name="contact_phone" class="form-control" value="<?= e(get_setting('contact_phone', '+251 25 551 0352')) ?>" required>
                </div>

                <!-- Campus List -->
                <div class="col-12">
                    <label class="form-label fw-bold">Allowed Campuses (Comma-separated)</label>
                    <input type="text" name="campuses_list" class="form-control" value="<?= e(get_setting('campuses_list', 'Main Campus (Chiro), Agricultural Campus, Health Science Campus, Asbe Teferi Campus')) ?>" required>
                </div>

                <!-- Max Upload Size -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Max Photo Upload Limit (MB)</label>
                    <input type="number" min="1" max="25" name="max_upload_size_mb" class="form-control" value="<?= e(get_setting('max_upload_size_mb', '5')) ?>" required>
                </div>

                <!-- Registration Enabled -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Student Self-Registration</label>
                    <select name="enable_registration" class="form-select">
                        <option value="1" <?= get_setting('enable_registration', '1') === '1' ? 'selected' : '' ?>>Enabled (Open to OBU students)</option>
                        <option value="0" <?= get_setting('enable_registration') === '0' ? 'selected' : '' ?>>Temporarily Disabled</option>
                    </select>
                </div>

                <div class="col-12 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-obu px-5 py-2.5">
                        <i class="bi bi-save me-1"></i> Save Platform Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

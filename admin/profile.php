<?php
/**
 * OBU Student Marketplace - Administrator Profile
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$pageTitle = 'Admin Profile';
$extraCss = ['dashboard.css'];
$currentUser = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $fullName = clean_input($_POST['full_name'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $currentPwd = $_POST['current_password'] ?? '';
    $newPwd = $_POST['new_password'] ?? '';
    $confirmPwd = $_POST['confirm_new_password'] ?? '';

    if (!empty($fullName) && !empty($phone)) {
        try {
            $stmt = db()->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$fullName, $phone, $currentUser['id']]);
            $_SESSION['user']['full_name'] = $fullName;
            $_SESSION['user']['phone'] = $phone;
            set_flash('success', 'Profile details updated.');
        } catch (Exception $e) {}
    }

    if (!empty($newPwd)) {
        if (!password_verify($currentPwd, $currentUser['password'])) {
            set_flash('danger', 'Current password is incorrect.');
        } elseif (strlen($newPwd) < 8) {
            set_flash('danger', t('error_password_length'));
        } elseif ($newPwd !== $confirmPwd) {
            set_flash('danger', t('error_password_mismatch'));
        } else {
            $hash = password_hash($newPwd, PASSWORD_BCRYPT);
            $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $currentUser['id']]);
            $_SESSION['user']['password'] = $hash;
            log_activity('ADMIN_PASSWORD_CHANGE', 'Admin updated master password', (int)$currentUser['id']);
            set_flash('success', 'Administrator password updated securely.');
        }
    }

    header('Location: ' . base_url('admin/profile.php'));
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-surface mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-shield-person text-danger me-2"></i> Administrator Security Profile</h3>
                <p class="text-secondary mb-0">Master administrative credentials and emergency contact info</p>
            </div>
            <a href="<?= base_url('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
                &larr; Admin Overview
            </a>
        </div>

        <div class="d-flex align-items-center gap-3 p-3 mb-4 rounded-3 bg-light border">
            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center fw-bold fs-3" style="width: 72px; height: 72px;">
                A
            </div>
            <div>
                <h4 class="fw-bold mb-0">Admin</h4>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <span class="badge bg-danger">ADMINISTRATOR</span>
                    <span class="text-muted small">Master Administration Account</span>
                </div>
            </div>
        </div>

        <form action="<?= base_url('admin/profile.php') ?>" method="POST" class="needs-validation">
            <?= csrf_field() ?>

            <div class="row g-4" style="max-width: 650px;">
                <div class="col-12">
                    <label class="form-label fw-bold">Admin Full Name *</label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($currentUser['full_name']) ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Email Address</label>
                    <input type="email" class="form-control" value="<?= e($currentUser['email']) ?>" readonly disabled>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Emergency Phone *</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($currentUser['phone']) ?>" required>
                </div>

                <div class="col-12 pt-3 border-top">
                    <h5 class="fw-bold mb-2">Change Administrator Password</h5>
                    <p class="small text-muted mb-3">Leave blank if you do not wish to modify your password.</p>
                </div>

                <div class="col-12">
                    <label class="form-label fw-bold">Current Password</label>
                    <input type="password" name="current_password" class="form-control">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">New Password</label>
                    <input type="password" name="new_password" class="form-control" minlength="8">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Confirm New Password</label>
                    <input type="password" name="confirm_new_password" class="form-control" minlength="8">
                </div>

                <div class="col-12 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-obu px-5 py-2.5">
                        <i class="bi bi-shield-lock-fill me-1"></i> Update Admin Credentials
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

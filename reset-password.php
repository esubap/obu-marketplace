<?php
/**
 * OBU Student Marketplace - Reset Password Action Page
 */
require_once __DIR__ . '/config/app.php';

$pageTitle = 'Set New Password';
$extraCss = ['auth.css'];

$token = clean_input($_GET['token'] ?? ($_POST['token'] ?? ''));
$email = clean_input($_GET['email'] ?? ($_POST['email'] ?? ''));
$isValidToken = false;
$error = null;
$userId = null;

if (empty($token) || empty($email) || !db()) {
    $error = 'Invalid password reset request or missing parameters.';
} else {
    try {
        $tokenHash = hash('sha256', $token);
        $stmt = db()->prepare("
            SELECT pr.id AS reset_id, u.id AS user_id, u.email
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token_hash = ? AND u.email = ? AND pr.used_at IS NULL AND pr.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$tokenHash, $email]);
        $record = $stmt->fetch();

        if ($record) {
            $isValidToken = true;
            $userId = (int)$record['user_id'];
            $resetId = (int)$record['reset_id'];
        } else {
            $error = 'This password reset link is invalid, expired, or has already been used.';
        }
    } catch (Exception $e) {
        $error = 'Database error verifying reset token.';
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isValidToken) {
    require_csrf();
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 8) {
        $error = t('error_password_length');
    } elseif ($newPassword !== $confirmPassword) {
        $error = t('error_password_mismatch');
    } else {
        try {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);
            
            // Update password
            $stmtUp = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtUp->execute([$hash, $userId]);

            // Invalidate token
            $stmtInv = db()->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");
            $stmtInv->execute([$resetId]);

            log_activity('PASSWORD_RESET_SUCCESS', "Password reset completed for $email", $userId);

            set_flash('success', 'Your password has been reset successfully! You can now log in.');
            header('Location: ' . base_url('login.php'));
            exit;
        } catch (Exception $e) {
            $error = 'Failed to update password.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="d-inline-flex align-items-center justify-content-center p-2 rounded-circle mb-2" style="background: rgba(255, 255, 255, 0.2);">
                <i class="bi bi-shield-check fs-3"></i>
            </div>
            <h3>Set New Password</h3>
            <p>Choose a secure password for your student account</p>
        </div>

        <div class="auth-body">
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($isValidToken): ?>
                <form action="<?= base_url('reset-password.php') ?>" method="POST" class="needs-validation">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <input type="hidden" name="email" value="<?= e($email) ?>">

                    <div class="mb-3">
                        <label class="form-label"><?= t('new_password') ?> *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-surface-alt"><i class="bi bi-lock"></i></span>
                            <input type="password" name="new_password" class="form-control" minlength="8" required autofocus>
                        </div>
                        <div class="form-text small"><?= t('auth_password_req') ?></div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><?= t('confirm_new_password') ?> *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-surface-alt"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-obu w-100 py-2.5 fs-6">
                        <i class="bi bi-check-circle me-1"></i> Update Password & Log In
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center py-3">
                    <a href="<?= base_url('forgot-password.php') ?>" class="btn btn-obu-outline">
                        Request a New Reset Link
                    </a>
                </div>
            <?php endif; ?>

            <div class="auth-footer-note">
                <a href="<?= base_url('login.php') ?>" class="text-primary fw-semibold">
                    &larr; <?= t('auth_back_to_login') ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

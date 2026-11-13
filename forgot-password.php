<?php
/**
 * OBU Student Marketplace - Forgot Password
 */
require_once __DIR__ . '/config/app.php';

$pageTitle = t('auth_reset_title');
$extraCss = ['auth.css'];
$resetTokenLink = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $identifier = clean_input($_POST['identifier'] ?? ($_POST['email'] ?? ''));

    if (empty($identifier)) {
        $error = 'Please enter your registered university email or Student ID.';
    } elseif (!db()) {
        $error = t('error_database');
    } else {
        try {
            $stmt = db()->prepare("SELECT id, full_name, email FROM users WHERE email = ? OR student_id = ?");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch();

            if ($user) {
                $email = $user['email'];
                // Generate token
                $rawToken = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);

                // Expire existing tokens for this user
                $stmtDel = db()->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $stmtDel->execute([$user['id']]);

                // Insert new reset token valid for 2 hours
                $stmtIns = db()->prepare("
                    INSERT INTO password_resets (user_id, token_hash, expires_at)
                    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL " . PASSWORD_RESET_EXPIRY_HOURS . " HOUR))
                ");
                $stmtIns->execute([$user['id'], $tokenHash]);

                $resetTokenLink = base_url("reset-password.php?token={$rawToken}&email=" . urlencode($email));
                log_activity('PASSWORD_RESET_REQ', "Password reset token generated for {$email}", (int)$user['id']);
            } else {
                // Generic security acknowledgment
                set_flash('info', 'If your account is registered in the system, you can reset your password using the link.');
            }
        } catch (Exception $e) {
            $error = 'Error processing reset request: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="d-inline-flex align-items-center justify-content-center p-2 rounded-circle mb-2" style="background: rgba(255, 255, 255, 0.2);">
                <i class="bi bi-key-fill fs-3"></i>
            </div>
            <h3><?= t('auth_reset_title') ?></h3>
            <p><?= t('auth_reset_sub') ?></p>
        </div>

        <div class="auth-body">
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($resetTokenLink): ?>
                <div class="alert alert-success">
                    <h6 class="fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Reset Token Generated!</h6>
                    <p class="small mb-2">In this university test environment, click the button below to set your new password:</p>
                    <a href="<?= $resetTokenLink ?>" class="btn btn-sm btn-success fw-bold text-break">
                        <i class="bi bi-shield-check me-1"></i> Set New Password Now &raquo;
                    </a>
                </div>
            <?php endif; ?>

            <form action="<?= base_url('forgot-password.php') ?>" method="POST" class="needs-validation">
                <?= csrf_field() ?>

                <div class="mb-4">
                    <label for="reset_identifier" class="form-label fw-semibold">Email or Student ID *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-surface-alt"><i class="bi bi-person-badge"></i></span>
                        <input type="text" name="identifier" id="reset_identifier" class="form-control" placeholder="student@obu.edu.et or OBU/0452/14" required autofocus>
                    </div>
                    <div class="form-text small">Enter the email or Student ID you used when registering.</div>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-obu w-100 py-2.5 fs-6">
                        <i class="bi bi-send me-1"></i> <?= t('auth_send_reset_link') ?>
                    </button>
                </div>
            </form>

            <div class="alert alert-light border small text-muted mt-3 mb-0">
                <i class="bi bi-info-circle text-primary me-1"></i>
                <strong>Notice for Students:</strong> You can also contact the University Marketplace Administrator or ICT office to have your password reset directly.
            </div>
            </form>

            <div class="auth-footer-note">
                <a href="<?= base_url('login.php') ?>" class="text-primary fw-semibold">
                    &larr; <?= t('auth_back_to_login') ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

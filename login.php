<?php
/**
 * OBU Student Marketplace - User Login (Module 2)
 */
require_once __DIR__ . '/config/app.php';

// Redirect if already logged in
if (is_logged_in()) {
    $role = current_user_role();
    if ($role === ROLE_ADMIN) {
        header('Location: ' . base_url('admin/dashboard.php'));
    } elseif ($role === ROLE_MODERATOR) {
        header('Location: ' . base_url('moderator/dashboard.php'));
    } else {
        header('Location: ' . base_url('student/dashboard.php'));
    }
    exit;
}

$pageTitle = t('auth_login_title');
$extraCss = ['auth.css'];

$lastIdentifier = $_SESSION['login_identifier'] ?? '';
unset($_SESSION['login_identifier']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="d-inline-flex align-items-center justify-content-center p-2 rounded-circle mb-2" style="background: rgba(255, 255, 255, 0.2);">
                <i class="bi bi-shield-lock-fill fs-3"></i>
            </div>
            <h3><?= t('auth_login_title') ?></h3>
            <p><?= t('auth_login_sub') ?></p>
        </div>

        <div class="auth-body">
            <form action="<?= base_url('actions/login-action.php') ?>" method="POST" class="needs-validation">
                <?= csrf_field() ?>

                <!-- Email or Student ID -->
                <div class="mb-3">
                    <label for="login_identifier" class="form-label"><?= t('auth_email_or_student_id') ?> *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-surface-alt"><i class="bi bi-person"></i></span>
                        <input type="text" name="identifier" id="login_identifier" class="form-control" placeholder="student@obu.edu.et or OBU/0452/14" value="<?= e($lastIdentifier) ?>" required autofocus>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="login_password" class="form-label mb-0"><?= t('auth_password') ?> *</label>
                        <a href="<?= base_url('forgot-password.php') ?>" class="small text-primary fw-bold text-decoration-none">
                            <i class="bi bi-key-fill text-warning me-1"></i><?= t('auth_forgot_password') ?>
                        </a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-surface-alt"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="login_password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                    <label class="form-check-label small text-secondary" for="rememberMe">
                        <?= t('auth_remember_me') ?>
                    </label>
                </div>

                <div>
                    <button type="submit" class="btn btn-obu w-100 py-2.5 fs-6">
                        <i class="bi bi-box-arrow-in-right me-1"></i> <?= t('auth_login_btn') ?>
                    </button>
                </div>

                <!-- Prominent Password Recovery Helper Card -->
                <div class="mt-3 p-2.5 rounded-3 bg-light border d-flex flex-wrap justify-content-between align-items-center gap-2 small">
                    <span class="text-secondary fw-semibold">
                        <i class="bi bi-question-circle-fill text-warning me-1"></i> Can't log in?
                    </span>
                    <div class="d-flex gap-2">
                        <a href="<?= base_url('forgot-password.php') ?>" class="btn btn-sm btn-outline-primary py-1 px-2.5 fw-bold" title="Reset Student Password">
                            <i class="bi bi-mortarboard me-1"></i> Student Reset
                        </a>
                        <a href="<?= base_url('recover-admin.php') ?>" class="btn btn-sm btn-outline-danger py-1 px-2.5 fw-bold" title="Emergency Admin Password Reset">
                            <i class="bi bi-shield-lock me-1"></i> Admin Reset
                        </a>
                    </div>
                </div>
            </form>

            <div class="auth-footer-note">
                <?= t('auth_no_account') ?> 
                <a href="<?= base_url('register.php') ?>" class="fw-bold text-primary"><?= t('auth_register_here') ?></a>
            </div>

            <!-- Quick Demo Credentials Box (Simplified to 2 Main Roles: Admin & Student) -->
            <div class="mt-4 pt-3 border-top">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-muted text-uppercase" style="letter-spacing: 0.5px;">Quick 1-Click Demo Login</span>
                    <span class="badge bg-success" style="font-size: 0.65rem;">Pass: Password123!</span>
                </div>
                <div class="d-grid gap-2">
                    <!-- 1. Administrator Account -->
                    <button type="button" class="btn btn-sm btn-outline-danger text-start p-2 rounded-3" onclick="fillLogin('admin@obu.edu.et', 'Password123!')">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-shield-lock-fill me-1"></i> <strong>Admin Account:</strong> admin@obu.edu.et</span>
                            <span class="badge bg-danger">Full Control</span>
                        </div>
                        <small class="text-muted d-block mt-1">Platform management, approve/hide listings, user moderation, settings</small>
                    </button>

                    <!-- 2. Student Account (Unified Buy & Sell) -->
                    <button type="button" class="btn btn-sm btn-outline-primary text-start p-2 rounded-3" onclick="fillLogin('student@obu.edu.et', 'Password123!')">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-mortarboard-fill me-1"></i> <strong>Student Account:</strong> student@obu.edu.et</span>
                            <span class="badge bg-primary">Buy & Sell All-in-One</span>
                        </div>
                        <small class="text-muted d-block mt-1">Upload items to sell, browse catalog, send purchase offers, internal chat</small>
                    </button>

                    <!-- Optional Student 2 for Testing Peer Trades -->
                    <button type="button" class="btn btn-sm btn-outline-secondary text-start py-1 px-2 rounded-2" onclick="fillLogin('student2@obu.edu.et', 'Password123!')">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small"><i class="bi bi-person me-1"></i> <strong>Peer Student:</strong> student2@obu.edu.et</span>
                            <span class="badge bg-secondary" style="font-size: 0.65rem;">Peer Trading / Chat</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillLogin(id, pwd) {
    document.getElementById('login_identifier').value = id;
    document.getElementById('login_password').value = pwd;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * OBU Student Marketplace - Administrator Emergency Password Recovery Tool
 * Oda Bultum University
 *
 * Use this file if you forget or lose your administrator password.
 */
require_once __DIR__ . '/config/app.php';

$pageTitle = 'Admin Emergency Password Recovery';
$message = null;
$messageType = null;
$adminUser = null;

if (db()) {
    try {
        // Find existing admin
        $stmt = db()->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
        $adminUser = $stmt->fetch();
    } catch (Exception $e) {}
}

// Handle Reset POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_reset'])) {
    if (!db()) {
        $message = "Cannot connect to MySQL database. Please make sure MySQL is started in XAMPP Control Panel.";
        $messageType = 'danger';
    } else {
        $newPass = trim($_POST['new_admin_password'] ?? 'Password123!');
        if (strlen($newPass) < 6) {
            $message = "Password must be at least 6 characters.";
            $messageType = 'danger';
        } else {
            try {
                $hash = password_hash($newPass, PASSWORD_BCRYPT);
                
                if ($adminUser) {
                    $stmt = db()->prepare("UPDATE users SET password = ?, status = 'active' WHERE id = ?");
                    $stmt->execute([$hash, $adminUser['id']]);
                    $targetEmail = $adminUser['email'];
                } else {
                    // Create default admin user if missing
                    $stmt = db()->prepare("
                        INSERT INTO users (full_name, student_id, university, department, year_of_study, email, phone, password, role, status, preferred_language, theme_preference)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'admin', 'active', 'en', 'bright')
                    ");
                    $targetEmail = 'admin@obu.edu.et';
                    $stmt->execute([
                        'OBU Administrator',
                        'ADMIN-001',
                        'Oda Bultum University',
                        'ICT Directorate',
                        'Staff',
                        $targetEmail,
                        '+251 91 123 4567',
                        $hash
                    ]);
                }

                log_activity('ADMIN_PASSWORD_RECOVERED', "Admin password was reset via recover-admin tool", $adminUser['id'] ?? 1);

                $message = "Administrator password was successfully updated to: <strong>" . e($newPass) . "</strong> for account <code>" . e($targetEmail) . "</code>. You can now log into your Admin Dashboard!";
                $messageType = 'success';

                // Refresh admin user
                $stmt = db()->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
                $adminUser = $stmt->fetch();

            } catch (Exception $e) {
                $message = "Error resetting admin password: " . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="bright">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Emergency Password Recovery | OBU Student Marketplace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0F172A; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; padding: 24px; color: #F8FAFC; }
        .recovery-card { background: #1E293B; border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); max-width: 600px; width: 100%; overflow: hidden; }
        .recovery-header { background: linear-gradient(135deg, #DC2626 0%, #991B1B 100%); color: #FFFFFF; padding: 32px 24px; text-align: center; }
        .sql-box { background: #0F172A; border-radius: 8px; padding: 12px; font-family: monospace; font-size: 0.85rem; color: #38BDF8; border: 1px solid #334155; word-break: break-all; }
    </style>
</head>
<body>

<div class="recovery-card">
    <div class="recovery-header">
        <i class="bi bi-shield-lock-fill display-5 mb-2 d-inline-block"></i>
        <h2 class="fw-bold mb-1">Administrator Password Recovery</h2>
        <p class="mb-0 opacity-90">Emergency Master Reset Tool &bull; Oda Bultum University</p>
    </div>

    <div class="p-4 p-md-5">
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> d-flex gap-2 align-items-center mb-4">
                <i class="bi bi-<?= $messageType === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> fs-4"></i>
                <div><?= $message ?></div>
            </div>

            <?php if ($messageType === 'success'): ?>
                <div class="d-grid gap-2 mb-4">
                    <a href="login.php" class="btn btn-success btn-lg fw-bold">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Log in to Administrator Account
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Current Admin Account Info -->
        <div class="p-3 rounded-3 bg-dark border border-secondary mb-4 small">
            <div class="text-secondary fw-bold text-uppercase mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                Detected Administrator Account
            </div>
            <?php if ($adminUser): ?>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold text-white fs-6"><?= e($adminUser['full_name']) ?></div>
                        <div class="text-info"><?= e($adminUser['email']) ?> (ID: <?= e($adminUser['student_id']) ?>)</div>
                    </div>
                    <span class="badge bg-danger">ADMIN</span>
                </div>
            <?php else: ?>
                <div class="text-warning">No admin user detected in database. Running reset will create default admin <code>admin@obu.edu.et</code>.</div>
            <?php endif; ?>
        </div>

        <!-- 1-Click Reset Form -->
        <form method="POST" action="recover-admin.php" class="mb-4">
            <input type="hidden" name="do_reset" value="1">

            <div class="mb-3">
                <label class="form-label fw-bold text-white small">Set New Admin Password</label>
                <div class="input-group">
                    <input type="text" name="new_admin_password" id="newAdminPassword" class="form-control font-monospace" value="Password123!" required minlength="6">
                    <button type="button" class="btn btn-outline-secondary text-white" onclick="document.getElementById('newAdminPassword').value = 'Password123!'">
                        Use Default
                    </button>
                </div>
                <div class="form-text text-secondary small">Default recommended: <code>Password123!</code></div>
            </div>

            <button type="submit" class="btn btn-danger w-100 py-3 fw-bold fs-6 shadow">
                <i class="bi bi-shield-check me-2"></i> Reset Admin Password Now
            </button>
        </form>

        <hr class="border-secondary my-4">

        <!-- Alternative Solution 2: phpMyAdmin SQL command -->
        <div class="text-secondary small">
            <h6 class="fw-bold text-white mb-2"><i class="bi bi-terminal me-2 text-primary"></i> Alternative: Reset via phpMyAdmin</h6>
            <p class="mb-2">If you cannot access this page, open <strong>phpMyAdmin</strong> (<code>http://localhost/phpmyadmin</code>), select your database, click the <strong>SQL</strong> tab and run this 1-line query:</p>
            <div class="sql-box mb-3">
                UPDATE users SET password = '$2y$10$IYdMLDNPXAK0HK8Eed6J0exh7M.jaYn/7BZQvDfHN9NsgtBSYSyya' WHERE role = 'admin';
            </div>
            <p class="mb-0">This immediately sets the password of all admin accounts back to: <code>Password123!</code></p>
        </div>
    </div>

    <div class="card-footer bg-dark border-top border-secondary text-center small text-secondary py-3">
        <a href="login.php" class="text-decoration-none text-light me-3">&larr; Back to Login</a>
        <a href="index.php" class="text-decoration-none text-light">Marketplace Home</a>
    </div>
</div>

</body>
</html>

<?php
/**
 * OBU Student Marketplace - Web Installation & Database Setup Wizard
 * Oda Bultum University
 */
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

$requirements = [
    'php_version' => [
        'name' => 'PHP Version 8.0+',
        'passed' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'current' => PHP_VERSION
    ],
    'pdo_mysql' => [
        'name' => 'PDO MySQL Extension',
        'passed' => extension_loaded('pdo_mysql'),
        'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Missing'
    ],
    'fileinfo' => [
        'name' => 'Fileinfo Extension (Image verification)',
        'passed' => extension_loaded('fileinfo'),
        'current' => extension_loaded('fileinfo') ? 'Enabled' : 'Missing'
    ],
    'session' => [
        'name' => 'Session Support',
        'passed' => extension_loaded('session'),
        'current' => extension_loaded('session') ? 'Enabled' : 'Missing'
    ],
    'uploads_writable' => [
        'name' => 'Uploads Directory Writable',
        'passed' => is_writable(__DIR__ . '/uploads'),
        'current' => is_writable(__DIR__ . '/uploads') ? 'Writable' : 'Read-only'
    ]
];

$allRequirementsPassed = true;
foreach ($requirements as $r) {
    if (!$r['passed']) {
        $allRequirementsPassed = false;
        break;
    }
}

$dbConnected = Database::isConnected();
$installMessage = null;
$installSuccess = false;

// Handle Installation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_install'])) {
    $host = trim($_POST['db_host'] ?? '127.0.0.1');
    $dbname = trim($_POST['db_name'] ?? 'obu_marketplace');
    $user = trim($_POST['db_user'] ?? 'root');
    $pass = $_POST['db_pass'] ?? '';
    $port = trim($_POST['db_port'] ?? '3306');

    Database::configure($host, $dbname, $user, $pass, $port);

    try {
        // 1. Connect to MySQL server
        $rawPdo = Database::getServerConnection();
        if (!$rawPdo) {
            throw new Exception("Could not connect to MySQL server at {$host}:{$port}. Please make sure Apache and MySQL are started in the XAMPP Control Panel. Error: " . Database::getError());
        }

        // 2. Create database if not exists
        $rawPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // 3. Connect to the created database
        $pdo = Database::getConnection();
        if (!$pdo) {
            throw new Exception("Database created, but failed to establish full connection. Check user permissions.");
        }

        // 4. Import SQL schema & seed data
        $sqlFile = file_exists(__DIR__ . '/database/obu_marketplace.sql') 
            ? __DIR__ . '/database/obu_marketplace.sql' 
            : __DIR__ . '/database/university_marketplace.sql';

        if (!file_exists($sqlFile)) {
            throw new Exception("SQL schema file not found at {$sqlFile}.");
        }

        $sqlContent = file_get_contents($sqlFile);
        
        // Execute multiline SQL queries
        $pdo->exec($sqlContent);

        $installSuccess = true;
        $installMessage = "OBU Student Marketplace database successfully installed and initialized with default categories, sample products, administrator, and student accounts!";

    } catch (Exception $e) {
        $installSuccess = false;
        $installMessage = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="bright">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OBU Marketplace - System Setup Wizard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #F0FDF4; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; color: #0F172A; }
        .setup-card { background: #FFFFFF; border-radius: 16px; box-shadow: 0 10px 30px rgba(2, 132, 199, 0.12); border: 1px solid #E2E8F0; }
        .brand-header { background: linear-gradient(135deg, #0284C7 0%, #0D9488 100%); color: #FFFFFF; padding: 36px 24px; border-radius: 16px 16px 0 0; text-align: center; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="setup-card">
                <div class="brand-header">
                    <img src="assets/images/logo.svg" alt="OBU Logo" height="52" style="filter: brightness(0) invert(1);" class="mb-2">
                    <h2 class="fw-bold mb-1">OBU Student Marketplace</h2>
                    <p class="mb-0 opacity-90">Oda Bultum University • System Installation & Database Setup Wizard</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if ($installMessage): ?>
                        <div class="alert alert-<?= $installSuccess ? 'success' : 'danger' ?> d-flex gap-3 align-items-center mb-4">
                            <i class="bi bi-<?= $installSuccess ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> fs-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1"><?= $installSuccess ? 'Installation Complete!' : 'Setup Error' ?></h6>
                                <div><?= e($installMessage) ?></div>
                            </div>
                        </div>

                        <?php if ($installSuccess): ?>
                            <div class="card p-3 rounded-3 bg-light border mb-4">
                                <h6 class="fw-bold mb-2"><i class="bi bi-key-fill text-primary me-2"></i> Ready-to-Use Accounts (2 Roles)</h6>
                                <div class="row g-3 small">
                                    <div class="col-md-6">
                                        <div class="p-2 border rounded bg-white">
                                            <span class="badge bg-danger mb-1"><i class="bi bi-shield-lock-fill me-1"></i> Admin Account</span><br>
                                            <strong>Email:</strong> <code>admin@obu.edu.et</code><br>
                                            <strong>Pass:</strong> <code>Password123!</code><br>
                                            <span class="text-muted small">Controls listings, users, categories, reports</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-2 border rounded bg-white">
                                            <span class="badge bg-primary mb-1"><i class="bi bi-mortarboard-fill me-1"></i> Student Account (Buy & Sell)</span><br>
                                            <strong>Email:</strong> <code>student@obu.edu.et</code><br>
                                            <strong>Pass:</strong> <code>Password123!</code><br>
                                            <span class="text-muted small">Upload items to sell + browse & buy from students</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center">
                                <a href="index.php" class="btn btn-primary btn-lg px-5 fw-bold">
                                    <i class="bi bi-shop me-2"></i> Launch Marketplace Homepage
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!$installSuccess): ?>
                        <!-- Environment Verification Check -->
                        <h5 class="fw-bold mb-3"><i class="bi bi-cpu me-2 text-primary"></i> 1. Server Environment Check</h5>
                        <div class="list-group list-group-flush border rounded-3 mb-4">
                            <?php foreach ($requirements as $req): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                                    <span><?= $req['name'] ?> (<?= $req['current'] ?>)</span>
                                    <?php if ($req['passed']): ?>
                                        <span class="badge bg-success rounded-pill"><i class="bi bi-check-lg me-1"></i> OK</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill"><i class="bi bi-x-lg me-1"></i> Missing</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Database Configuration -->
                        <h5 class="fw-bold mb-3"><i class="bi bi-database me-2 text-primary"></i> 2. MySQL Database Connection (XAMPP)</h5>
                        
                        <div class="alert alert-info small mb-3">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            <strong>XAMPP Quick Tip:</strong> Open your <strong>XAMPP Control Panel</strong> and ensure the <strong>MySQL</strong> and <strong>Apache</strong> modules are turned <strong>Start</strong>. The default username is <code>root</code> with an empty password.
                        </div>

                        <form action="install.php" method="POST">
                            <input type="hidden" name="run_install" value="1">

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">MySQL Host</label>
                                    <input type="text" name="db_host" class="form-control" value="127.0.0.1" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">MySQL Port</label>
                                    <input type="text" name="db_port" class="form-control" value="3306" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Database Name</label>
                                    <input type="text" name="db_name" class="form-control" value="obu_marketplace" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Database Username</label>
                                    <input type="text" name="db_user" class="form-control" value="root" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Database Password (Empty by default in XAMPP)</label>
                                    <input type="password" name="db_pass" class="form-control" placeholder="Leave empty for XAMPP default">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold fs-6 shadow">
                                <i class="bi bi-database-check me-2"></i> Install Database & Seed Sample OBU Data
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="card-footer bg-light text-center small text-muted py-3">
                    Oda Bultum University Student Marketplace &bull; Academic Year 2026
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

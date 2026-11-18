<?php
/**
 * OBU Student Marketplace - Web Installation & Database Setup Wizard
 * Oda Bultum University
 */
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

// Helper function e() for safe escaping
if (!function_exists('e')) {
    function e(?string $str): string {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$isServerless = !empty($_SERVER['VERCEL']) || !empty(getenv('VERCEL'));

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
    'session' => [
        'name' => 'Session Support',
        'passed' => extension_loaded('session'),
        'current' => extension_loaded('session') ? 'Enabled' : 'Missing'
    ]
];

if (!$isServerless) {
    $requirements['uploads_writable'] = [
        'name' => 'Uploads Directory Writable',
        'passed' => is_writable(__DIR__ . '/uploads'),
        'current' => is_writable(__DIR__ . '/uploads') ? 'Writable' : 'Read-only'
    ];
}

$dbConnected = Database::isConnected();
$installMessage = null;
$installSuccess = false;

// Handle Installation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_install'])) {
    $customHost = trim($_POST['db_host'] ?? '');
    if (!empty($customHost)) {
        $host = $customHost;
        $dbname = trim($_POST['db_name'] ?? 'obu_marketplace');
        $user = trim($_POST['db_user'] ?? 'root');
        $pass = $_POST['db_pass'] ?? '';
        $port = trim($_POST['db_port'] ?? '3306');
        Database::configure($host, $dbname, $user, $pass, $port);
    }

    try {
        // 1. Establish connection
        $pdo = Database::getConnection();
        if (!$pdo) {
            throw new Exception("Could not connect to MySQL server. Error: " . Database::getError());
        }

        // 2. Select SQL file to import
        $sqlCandidates = [
            __DIR__ . '/database/tidb_cloud.sql',
            __DIR__ . '/database/obu_marketplace.sql',
            __DIR__ . '/database/university_marketplace.sql'
        ];

        $sqlContent = null;
        foreach ($sqlCandidates as $candidate) {
            if (is_file($candidate)) {
                $sqlContent = file_get_contents($candidate);
                break;
            }
        }

        if (!$sqlContent) {
            throw new Exception("SQL schema file not found in database/ directory.");
        }

        // 3. Execute multiline SQL queries
        $pdo->exec($sqlContent);

        $installSuccess = true;
        $installMessage = "OBU Student Marketplace database successfully installed! All 15 tables created with default categories, sample products, administrator, and student accounts!";

    } catch (Exception $e) {
        $installSuccess = false;
        $installMessage = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OBU Marketplace - Web Setup Wizard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #0b1329; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; color: #F1F5F9; }
        .setup-card { background: #0f172a; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); border: 1px solid rgba(255, 255, 255, 0.1); }
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
                    <p class="mb-0 opacity-90">Oda Bultum University &bull; System Installation & Database Setup Wizard</p>
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
                            <div class="card p-3 rounded-3 bg-dark border border-secondary mb-4 text-white">
                                <h6 class="fw-bold mb-2 text-warning"><i class="bi bi-key-fill text-warning me-2"></i> Ready-to-Use Accounts</h6>
                                <div class="row g-3 small">
                                    <div class="col-md-6">
                                        <div class="p-3 border border-danger rounded bg-black">
                                            <span class="badge bg-danger mb-2"><i class="bi bi-shield-lock-fill me-1"></i> Admin Account</span><br>
                                            <strong>Email:</strong> <code>admin@obu.edu.et</code><br>
                                            <strong>Password:</strong> <code>password123</code><br>
                                            <span class="text-secondary small">Controls all student products, approvals, removals</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 border border-primary rounded bg-black">
                                            <span class="badge bg-primary mb-2"><i class="bi bi-mortarboard-fill me-1"></i> Student Account (Buy & Sell)</span><br>
                                            <strong>Email:</strong> <code>student1@obu.edu.et</code><br>
                                            <strong>Password:</strong> <code>password123</code><br>
                                            <span class="text-secondary small">Single account to buy and sell products</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-3 justify-content-center">
                                <a href="login.php" class="btn btn-success btn-lg px-4 fw-bold">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Go to Login Page
                                </a>
                                <a href="index.php" class="btn btn-outline-light btn-lg px-4 fw-bold">
                                    <i class="bi bi-shop me-2"></i> View Homepage
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!$installSuccess): ?>
                        <!-- Environment Verification Check -->
                        <h5 class="fw-bold mb-3 text-info"><i class="bi bi-cpu me-2"></i> 1. Server Environment Check</h5>
                        <div class="list-group list-group-flush border border-secondary rounded-3 mb-4 bg-dark">
                            <?php foreach ($requirements as $req): ?>
                                <div class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center py-2.5 border-secondary">
                                    <span><?= $req['name'] ?> (<?= $req['current'] ?>)</span>
                                    <?php if ($req['passed']): ?>
                                        <span class="badge bg-success rounded-pill"><i class="bi bi-check-lg me-1"></i> OK</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill"><i class="bi bi-x-lg me-1"></i> Missing</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Database Status -->
                        <h5 class="fw-bold mb-3 text-info"><i class="bi bi-database me-2"></i> 2. Database Connection</h5>

                        <div class="p-3 rounded-3 bg-dark border <?= $dbConnected ? 'border-success' : 'border-danger' ?> mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-secondary fw-bold text-uppercase small">Status</span>
                                <?php if ($dbConnected): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> CONNECTED</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i> DISCONNECTED</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($dbConnected): ?>
                                <div class="text-success small">Connected to MySQL database <code><?= e(Database::getDbName()) ?></code> successfully.</div>
                            <?php else: ?>
                                <div class="text-danger small mb-1"><?= e(Database::getError() ?? 'Cannot reach MySQL server.') ?></div>
                            <?php endif; ?>
                        </div>

                        <form action="install.php" method="POST">
                            <input type="hidden" name="run_install" value="1">

                            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold fs-6 shadow">
                                <i class="bi bi-database-check me-2"></i> Install Database Tables & Seed OBU Data
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="card-footer bg-black border-top border-secondary text-center small text-secondary py-3">
                    Oda Bultum University Student Marketplace &bull; Academic Year 2026
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

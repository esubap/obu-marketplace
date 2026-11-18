<?php
/**
 * OBU Student Marketplace - Administrator Dashboard (Module 10)
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$pageTitle = t('admin_dashboard');
$extraCss = ['dashboard.css'];
$currentUser = current_user();

$stats = [
    'total_students'    => 0,
    'active_students'   => 0,
    'suspended_students'=> 0,
    'total_products'    => 0,
    'active_products'   => 0,
    'sold_products'     => 0,
    'pending_reports'   => 0,
    'total_requests'    => 0
];

// Direct Product Action Handler from Dashboard (Remove, Hide, Approve)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $targetId = (int)$_GET['id'];
    $act = clean_input($_GET['action']);
    $csrf = $_GET['csrf_token'] ?? '';

    if (verify_csrf_token($csrf) && $targetId > 0) {
        if ($act === 'delete_product') {
            $stmtImg = db()->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
            $stmtImg->execute([$targetId]);
            $images = $stmtImg->fetchAll();
            foreach ($images as $img) {
                if (!str_starts_with($img['image_path'], 'assets/') && !str_starts_with($img['image_path'], 'data:')) {
                    $fileOnDisk = PRODUCTS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $img['image_path'];
                    if (file_exists($fileOnDisk)) { @unlink($fileOnDisk); }
                }
            }
            $stmt = db()->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$targetId]);
            log_activity('ADMIN_DEL_PROD', "Admin permanently removed product #$targetId from dashboard", (int)$currentUser['id']);
            set_flash('info', "Product #$targetId has been permanently removed.");
        } elseif ($act === 'hide_product') {
            $stmt = db()->prepare("UPDATE products SET status = 'hidden' WHERE id = ?");
            $stmt->execute([$targetId]);
            log_activity('ADMIN_HIDE_PROD', "Admin hid product #$targetId from dashboard", (int)$currentUser['id']);
            set_flash('warning', "Product #$targetId is now hidden from students.");
        } elseif ($act === 'approve_product') {
            $stmt = db()->prepare("UPDATE products SET status = 'active' WHERE id = ?");
            $stmt->execute([$targetId]);
            log_activity('ADMIN_APPROVE_PROD', "Admin approved product #$targetId from dashboard", (int)$currentUser['id']);
            set_flash('success', "Product #$targetId is now active on the marketplace.");
        }
        header('Location: ' . base_url('admin/dashboard.php'));
        exit;
    }
}

$categoryBreakdown = [];
$statusBreakdown = [];
$recentStudents = [];
$recentLogs = [];
$studentProducts = [];

if (db()) {
    try {
        // Student metrics
        $stats['total_students'] = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
        $stats['active_students'] = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active'")->fetchColumn();
        $stats['suspended_students'] = (int)db()->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();

        // Product metrics
        $stats['total_products'] = (int)db()->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $stats['active_products'] = (int)db()->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
        $stats['sold_products'] = (int)db()->query("SELECT COUNT(*) FROM products WHERE status = 'sold'")->fetchColumn();

        // Reports and Requests
        $stats['pending_reports'] = (int)db()->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
        $stats['total_requests'] = (int)db()->query("SELECT COUNT(*) FROM purchase_requests")->fetchColumn();

        // Student uploaded products control
        $stmtSp = db()->query("
            SELECT p.*, c.name_en AS cat_en, c.name_am AS cat_am, c.name_om AS cat_om,
                   u.full_name AS seller_name, u.student_id AS seller_student_id,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) AS primary_image
            FROM products p
            JOIN categories c ON p.category_id = c.id
            JOIN users u ON p.user_id = u.id
            ORDER BY p.id DESC
            LIMIT 10
        ");
        $studentProducts = $stmtSp->fetchAll();

        // Category Breakdown
        $stmtCat = db()->query("
            SELECT c.name_en, COUNT(p.id) AS product_count
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            GROUP BY c.id, c.name_en
            ORDER BY product_count DESC
            LIMIT 6
        ");
        $categoryBreakdown = $stmtCat->fetchAll();

        // Product Status Distribution
        $stmtStat = db()->query("
            SELECT status, COUNT(*) AS count
            FROM products
            GROUP BY status
        ");
        $statusBreakdown = $stmtStat->fetchAll(PDO::FETCH_KEY_PAIR);

        // Recent student registrations
        $stmtStudents = db()->query("
            SELECT * FROM users 
            WHERE role = 'student' 
            ORDER BY id DESC 
            LIMIT 5
        ");
        $recentStudents = $stmtStudents->fetchAll();

        // Recent audit logs
        $stmtLogs = db()->query("
            SELECT l.*, u.full_name AS user_name
            FROM activity_logs l
            LEFT JOIN users u ON l.user_id = u.id
            ORDER BY l.id DESC
            LIMIT 6
        ");
        $recentLogs = $stmtLogs->fetchAll();

    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <!-- Admin Top Language Selector Bar (1-Click Switch: English, Amharic, Afaan Oromoo) -->
    <div class="card border-0 rounded-4 shadow-sm p-3 mb-4 bg-surface" style="border-top: 4px solid #DC2626 !important;">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-danger-subtle text-danger p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-translate fs-5"></i>
                </div>
                <div>
                    <span class="fw-bold d-block lh-1 text-danger"><?= t('language') ?> / ቋንቋ / Afaan</span>
                    <small class="text-muted">Administrator language controls</small>
                </div>
            </div>
            <div class="btn-group shadow-sm" role="group" aria-label="Admin Language Selector">
                <a href="<?= get_lang_url('en') ?>" class="btn btn-sm <?= current_lang() === 'en' ? 'btn-danger active fw-bold' : 'btn-outline-secondary' ?> px-3 py-1.5">
                    🇬🇧 English <?= current_lang() === 'en' ? '<i class="bi bi-check2"></i>' : '' ?>
                </a>
                <a href="<?= get_lang_url('am') ?>" class="btn btn-sm <?= current_lang() === 'am' ? 'btn-danger active fw-bold' : 'btn-outline-secondary' ?> px-3 py-1.5">
                    🇪🇹 አማርኛ <?= current_lang() === 'am' ? '<i class="bi bi-check2"></i>' : '' ?>
                </a>
                <a href="<?= get_lang_url('om') ?>" class="btn btn-sm <?= current_lang() === 'om' ? 'btn-danger active fw-bold' : 'btn-outline-secondary' ?> px-3 py-1.5">
                    🌳 Afaan Oromoo <?= current_lang() === 'om' ? '<i class="bi bi-check2"></i>' : '' ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Admin Header & Module Navigation Bar -->
    <div class="card border-0 rounded-4 shadow-sm p-4 mb-4 bg-surface" style="border-left: 6px solid #DC2626 !important;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="badge bg-danger mb-1"><i class="bi bi-shield-lock-fill me-1"></i> MASTER ADMINISTRATION</span>
                <h3 class="fw-bold mb-1"><?= t('admin_dashboard') ?></h3>
                <p class="text-secondary mb-0">Platform overview, compliance monitoring, and university controls</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('admin/students.php') ?>" class="btn btn-sm btn-outline-primary fw-semibold">
                    <i class="bi bi-people me-1"></i> Students
                </a>
                <a href="<?= base_url('admin/products.php') ?>" class="btn btn-sm btn-outline-primary fw-semibold">
                    <i class="bi bi-box-seam me-1"></i> Products
                </a>
                <a href="<?= base_url('admin/categories.php') ?>" class="btn btn-sm btn-outline-primary fw-semibold">
                    <i class="bi bi-tags me-1"></i> Categories
                </a>
                <a href="<?= base_url('admin/reports.php') ?>" class="btn btn-sm btn-outline-danger fw-semibold">
                    <i class="bi bi-flag me-1"></i> Reports (<?= $stats['pending_reports'] ?>)
                </a>
                <a href="<?= base_url('admin/settings.php') ?>" class="btn btn-sm btn-outline-secondary fw-semibold">
                    <i class="bi bi-gear me-1"></i> Settings
                </a>
            </div>
        </div>
    </div>

    <!-- Administrator Identity Card (Circle Avatar with "Admin" name and "ADMINISTRATOR" role) -->
    <div class="d-flex align-items-center gap-3 p-3 mb-4 rounded-4 bg-surface border shadow-sm" style="border-left: 6px solid #DC2626 !important;">
        <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 56px; height: 56px;">
            A
        </div>
        <div>
            <h5 class="fw-bold mb-0">Admin</h5>
            <div class="d-flex align-items-center gap-2 mt-0.5">
                <span class="badge bg-danger">ADMINISTRATOR</span>
                <span class="text-secondary small">&bull; <?= e($currentUser['email']) ?></span>
                <span class="text-muted small">&bull; ICT Directorate</span>
            </div>
        </div>
        <div class="ms-auto d-none d-md-flex gap-2">
            <a href="<?= base_url('admin/products.php') ?>" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-box-seam me-1"></i> Moderate Products
            </a>
            <a href="<?= base_url('admin/students.php') ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-people me-1"></i> Manage Students
            </a>
        </div>
    </div>

    <!-- Key Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <h3><?= $stats['total_students'] ?></h3>
                    <p><?= t('total_students') ?></p>
                </div>
                <div class="stat-card-icon stat-icon-blue"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <h3 class="text-success"><?= $stats['active_students'] ?></h3>
                    <p><?= t('active_students') ?></p>
                </div>
                <div class="stat-card-icon stat-icon-teal"><i class="bi bi-person-check-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <h3 class="text-danger"><?= $stats['suspended_students'] ?></h3>
                    <p><?= t('suspended_students') ?></p>
                </div>
                <div class="stat-card-icon stat-icon-red"><i class="bi bi-person-slash"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <h3><?= $stats['total_products'] ?></h3>
                    <p><?= t('total_listings') ?></p>
                </div>
                <div class="stat-card-icon stat-icon-purple"><i class="bi bi-box-seam-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <h3 class="text-primary"><?= $stats['active_products'] ?></h3>
                    <p><?= t('active_listings') ?></p>
                </div>
                <div class="stat-card-icon stat-icon-blue"><i class="bi bi-shop"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <h3 class="text-success"><?= $stats['sold_products'] ?></h3>
                    <p><?= t('sold_listings') ?></p>
                </div>
                <div class="stat-card-icon stat-icon-teal"><i class="bi bi-check-all"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <h3 class="text-danger"><?= $stats['pending_reports'] ?></h3>
                    <p><?= t('pending_reports') ?></p>
                </div>
                <div class="stat-card-icon stat-icon-red"><i class="bi bi-flag-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <h3 class="text-warning"><?= $stats['total_requests'] ?></h3>
                    <p>Purchase Offers</p>
                </div>
                <div class="stat-card-icon stat-icon-amber"><i class="bi bi-bag-check-fill"></i></div>
            </div>
        </div>
    </div>

    <!-- STUDENT UPLOADED PRODUCTS CONTROL (DIRECT REMOVE & MODERATION) -->
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface mb-4" style="border-top: 4px solid #DC2626 !important;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-3 border-bottom">
            <div>
                <h4 class="fw-bold mb-1 text-danger">
                    <i class="bi bi-box-seam-fill me-2"></i> Student Uploaded Products Control (Direct Remove)
                </h4>
                <p class="text-secondary small mb-0">
                    Review and remove any inappropriate or prohibited student items directly from the administrator dashboard.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/products.php') ?>" class="btn btn-sm btn-outline-danger fw-semibold">
                    <i class="bi bi-sliders me-1"></i> Full Product Catalog & Filters &raquo;
                </a>
            </div>
        </div>

        <?php if (empty($studentProducts)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                No student products currently uploaded.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Student Seller</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Uploaded</th>
                            <th class="text-end">Admin Controls</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentProducts as $pItem): ?>
                            <?php 
                                $pImg = !empty($pItem['primary_image']) 
                                    ? (str_starts_with($pItem['primary_image'], 'assets/') ? asset_url(substr($pItem['primary_image'], 7)) : upload_url('products/' . $pItem['primary_image']))
                                    : asset_url('images/placeholders/default-product.svg');
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= $pImg ?>" alt="Thumb" class="table-thumb" style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover;">
                                        <div>
                                            <a href="<?= base_url('product-details.php?id=' . $pItem['id']) ?>" target="_blank" class="fw-bold d-block text-truncate text-decoration-none" style="max-width: 170px;">
                                                <?= e($pItem['title']) ?>
                                            </a>
                                            <span class="small text-muted"><?= render_condition_badge($pItem['condition']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e(localized(['name_en' => $pItem['cat_en'], 'name_am' => $pItem['cat_am'], 'name_om' => $pItem['cat_om']], 'name')) ?></td>
                                <td>
                                    <strong><?= e($pItem['seller_name']) ?></strong>
                                    <span class="small text-muted d-block font-monospace"><?= e($pItem['seller_student_id']) ?></span>
                                </td>
                                <td class="fw-bold text-primary"><?= format_currency($pItem['price']) ?></td>
                                <td><?= render_status_badge($pItem['status']) ?></td>
                                <td class="small text-muted"><?= time_ago($pItem['created_at']) ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <!-- View -->
                                        <a href="<?= base_url('product-details.php?id=' . $pItem['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Product Details">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <!-- Hide / Approve -->
                                        <?php if ($pItem['status'] === 'active'): ?>
                                            <a href="<?= base_url('admin/dashboard.php?action=hide_product&id=' . $pItem['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-warning" title="Hide listing from students">
                                                <i class="bi bi-eye-slash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= base_url('admin/dashboard.php?action=approve_product&id=' . $pItem['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-success" title="Approve listing">
                                                <i class="bi bi-check-lg"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- REMOVE BUTTON -->
                                        <a href="<?= base_url('admin/dashboard.php?action=delete_product&id=' . $pItem['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-danger fw-semibold" title="Permanently Remove / Delete" onclick="return confirm('Permanently remove and delete this product from the marketplace?');">
                                            <i class="bi bi-trash-fill me-1"></i> Remove
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Analytics & Breakdown Section -->
    <div class="row g-4 mb-4">
        <!-- Category Distribution -->
        <div class="col-lg-6">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-bar-chart-fill me-2 text-primary"></i> Listings by Category</h5>
                    <a href="<?= base_url('admin/categories.php') ?>" class="btn btn-sm btn-obu-outline">Manage</a>
                </div>
                <div class="d-flex flex-column gap-3">
                    <?php 
                        $maxCount = max(1, ...array_column($categoryBreakdown, 'product_count') ?: [1]);
                        foreach ($categoryBreakdown as $c): 
                            $pct = round(($c['product_count'] / $maxCount) * 100);
                    ?>
                        <div>
                            <div class="d-flex justify-content-between small fw-bold mb-1">
                                <span><?= e($c['name_en']) ?></span>
                                <span class="text-primary"><?= $c['product_count'] ?> items</span>
                            </div>
                            <div class="progress" style="height: 10px; border-radius: 6px; background-color: var(--bg-surface-alt);">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $pct ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Product Status Distribution -->
        <div class="col-lg-6">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-pie-chart-fill me-2 text-success"></i> Listing Status Distribution</h5>
                    <a href="<?= base_url('admin/products.php') ?>" class="btn btn-sm btn-obu-outline">View All</a>
                </div>

                <div class="row g-3 my-auto">
                    <div class="col-6">
                        <div class="p-3 rounded-3 bg-surface-alt border text-center">
                            <span class="small text-muted fw-bold d-block">ACTIVE LISTINGS</span>
                            <h2 class="fw-bold text-success mb-0"><?= $statusBreakdown['active'] ?? 0 ?></h2>
                            <span class="small text-muted">Currently browsable</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3 bg-surface-alt border text-center">
                            <span class="small text-muted fw-bold d-block">SOLD ITEMS</span>
                            <h2 class="fw-bold text-secondary mb-0"><?= $statusBreakdown['sold'] ?? 0 ?></h2>
                            <span class="small text-muted">Completed sales</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3 bg-surface-alt border text-center">
                            <span class="small text-muted fw-bold d-block">HIDDEN / REVIEW</span>
                            <h2 class="fw-bold text-warning mb-0"><?= $statusBreakdown['hidden'] ?? 0 ?></h2>
                            <span class="small text-muted">Awaiting clearance</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3 bg-surface-alt border text-center">
                            <span class="small text-muted fw-bold d-block">PENDING APPROVAL</span>
                            <h2 class="fw-bold text-info mb-0"><?= $statusBreakdown['pending'] ?? 0 ?></h2>
                            <span class="small text-muted">New submissions</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables: Recent Registrations & Audit Logs -->
    <div class="row g-4">
        <!-- Recent Students -->
        <div class="col-lg-6">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-person-plus me-2 text-primary"></i> Recent Student Sign-ups</h5>
                    <a href="<?= base_url('admin/students.php') ?>" class="btn btn-sm btn-obu-outline">All Students</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>ID</th>
                                <th>Department</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentStudents as $st): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($st['full_name']) ?></strong>
                                        <div class="small text-muted"><?= e($st['email']) ?></div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= e($st['student_id']) ?></span></td>
                                    <td class="small"><?= e($st['department']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $st['status'] === 'active' ? 'success' : 'danger' ?> rounded-pill">
                                            <?= ucfirst($st['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Activity Audit Logs -->
        <div class="col-lg-6">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-purple"></i> Security Audit Trail</h5>
                    <a href="<?= base_url('admin/activity-logs.php') ?>" class="btn btn-sm btn-obu-outline">View Logs</a>
                </div>
                <div class="list-group list-group-flush border rounded-3">
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="list-group-item p-2.5 small d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-secondary font-monospace" style="font-size: 0.68rem;"><?= e($log['action']) ?></span>
                                <span class="text-secondary ms-2"><?= e($log['description']) ?></span>
                            </div>
                            <span class="text-muted flex-shrink-0 ms-2" style="font-size: 0.72rem;"><?= time_ago($log['created_at']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

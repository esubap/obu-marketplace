<?php
/**
 * OBU Student Marketplace - Platform Analytics (Admin)
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$pageTitle = 'Marketplace Analytics';
$extraCss = ['dashboard.css'];

$stats = [
    'total_students' => 0,
    'total_products' => 0,
    'total_requests' => 0,
    'total_views'    => 0
];
$topViewed = [];
$topDepartments = [];
$reqStats = [];

if (db()) {
    try {
        $stats['total_students'] = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
        $stats['total_products'] = (int)db()->query("SELECT COUNT(*) FROM products")->fetchColumn();
        $stats['total_requests'] = (int)db()->query("SELECT COUNT(*) FROM purchase_requests")->fetchColumn();
        $stats['total_views'] = (int)db()->query("SELECT SUM(views_count) FROM products")->fetchColumn();

        // Top viewed products
        $stmtTop = db()->query("
            SELECT p.title, p.price, p.views_count, u.full_name AS seller_name
            FROM products p
            JOIN users u ON p.user_id = u.id
            ORDER BY p.views_count DESC
            LIMIT 5
        ");
        $topViewed = $stmtTop->fetchAll();

        // Top departments trading
        $stmtDept = db()->query("
            SELECT department, COUNT(*) AS count
            FROM users
            WHERE department IS NOT NULL AND department != ''
            GROUP BY department
            ORDER BY count DESC
            LIMIT 5
        ");
        $topDepartments = $stmtDept->fetchAll();

        // Purchase request breakdown
        $stmtReq = db()->query("
            SELECT status, COUNT(*) AS count
            FROM purchase_requests
            GROUP BY status
        ");
        $reqStats = $stmtReq->fetchAll(PDO::FETCH_KEY_PAIR);

    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow text-primary me-2"></i> Marketplace Analytics & Insights</h3>
                <p class="text-secondary mb-0">Engagement metrics and trading volume across Oda Bultum University</p>
            </div>
            <a href="<?= base_url('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
                &larr; Admin Overview
            </a>
        </div>

        <!-- 4 Metrics -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-card-info">
                        <h3><?= $stats['total_students'] ?></h3>
                        <p>Total Students</p>
                    </div>
                    <div class="stat-card-icon stat-icon-blue"><i class="bi bi-people"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-card-info">
                        <h3><?= $stats['total_products'] ?></h3>
                        <p>Total Listings</p>
                    </div>
                    <div class="stat-card-icon stat-icon-teal"><i class="bi bi-box-seam"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-card-info">
                        <h3><?= $stats['total_requests'] ?></h3>
                        <p>Purchase Requests</p>
                    </div>
                    <div class="stat-card-icon stat-icon-amber"><i class="bi bi-bag-check"></i></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-card-info">
                        <h3><?= number_format($stats['total_views']) ?></h3>
                        <p>Catalog Impressions</p>
                    </div>
                    <div class="stat-card-icon stat-icon-purple"><i class="bi bi-eye"></i></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Top Viewed Listings -->
            <div class="col-lg-6">
                <div class="card p-4 rounded-3 bg-surface-alt border h-100">
                    <h5 class="fw-bold mb-3"><i class="bi bi-fire text-danger me-2"></i> Most Viewed Student Listings</h5>
                    <div class="list-group list-group-flush border rounded-3">
                        <?php foreach ($topViewed as $tv): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="text-truncate" style="max-width: 280px;">
                                    <strong><?= e($tv['title']) ?></strong>
                                    <div class="small text-muted">Seller: <?= e($tv['seller_name']) ?> &bull; <?= format_currency($tv['price']) ?></div>
                                </div>
                                <span class="badge bg-primary rounded-pill"><?= $tv['views_count'] ?> views</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Top Academic Departments -->
            <div class="col-lg-6">
                <div class="card p-4 rounded-3 bg-surface-alt border h-100">
                    <h5 class="fw-bold mb-3"><i class="bi bi-mortarboard-fill text-primary me-2"></i> Most Active Departments</h5>
                    <div class="list-group list-group-flush border rounded-3">
                        <?php foreach ($topDepartments as $td): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <strong><?= e($td['department']) ?></strong>
                                <span class="badge bg-info text-dark rounded-pill"><?= $td['count'] ?> members</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Requests Status Ratio -->
        <div class="card p-4 rounded-3 bg-surface-alt border">
            <h5 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-success me-2"></i> Purchase Request Fulfillment Ratio</h5>
            <div class="row g-3 text-center">
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded-2 bg-surface border">
                        <span class="small text-muted fw-bold d-block">PENDING</span>
                        <h3 class="fw-bold text-warning mb-0"><?= $reqStats['pending'] ?? 0 ?></h3>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded-2 bg-surface border">
                        <span class="small text-muted fw-bold d-block">ACCEPTED</span>
                        <h3 class="fw-bold text-success mb-0"><?= $reqStats['accepted'] ?? 0 ?></h3>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded-2 bg-surface border">
                        <span class="small text-muted fw-bold d-block">COMPLETED</span>
                        <h3 class="fw-bold text-primary mb-0"><?= $reqStats['completed'] ?? 0 ?></h3>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded-2 bg-surface border">
                        <span class="small text-muted fw-bold d-block">DECLINED / CANCELLED</span>
                        <h3 class="fw-bold text-secondary mb-0"><?= ($reqStats['rejected'] ?? 0) + ($reqStats['cancelled'] ?? 0) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

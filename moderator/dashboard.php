<?php
/**
 * OBU Student Marketplace - Moderator Dashboard
 */
require_once __DIR__ . '/../config/app.php';
require_moderator();

$pageTitle = t('moderator_dashboard');
$extraCss = ['dashboard.css'];
$currentUser = current_user();

$stats = [
    'pending_reports' => 0,
    'resolved_reports' => 0,
    'hidden_products' => 0,
    'active_products' => 0
];
$pendingReports = [];

if (db()) {
    try {
        $stats['pending_reports'] = (int)db()->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
        $stats['resolved_reports'] = (int)db()->query("SELECT COUNT(*) FROM reports WHERE status = 'resolved'")->fetchColumn();
        $stats['hidden_products'] = (int)db()->query("SELECT COUNT(*) FROM products WHERE status = 'hidden'")->fetchColumn();
        $stats['active_products'] = (int)db()->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();

        $stmt = db()->query("
            SELECT r.*, u_rep.full_name AS reporter_name, u_rep.student_id AS reporter_student_id,
                   p.title AS product_title, p.price AS product_price, p.status AS product_status,
                   u_tgt.full_name AS reported_user_name
            FROM reports r
            JOIN users u_rep ON r.reporter_id = u_rep.id
            LEFT JOIN products p ON r.product_id = p.id
            LEFT JOIN users u_tgt ON r.reported_user_id = u_tgt.id
            WHERE r.status = 'pending'
            ORDER BY r.id DESC
            LIMIT 10
        ");
        $pendingReports = $stmt->fetchAll();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <!-- Header Banner -->
    <div class="card border-0 rounded-4 shadow-sm p-4 mb-4 bg-surface" style="border-left: 6px solid #F59E0B !important;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="badge bg-warning text-dark mb-1 fw-bold"><i class="bi bi-shield-check me-1"></i> CAMPUS MODERATOR</span>
                <h3 class="fw-bold mb-1"><?= t('moderator_dashboard') ?></h3>
                <p class="text-secondary mb-0">Logged in as <strong><?= e($currentUser['full_name']) ?></strong> (<?= e($currentUser['department']) ?>)</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('moderator/reports.php') ?>" class="btn btn-warning fw-bold text-dark">
                    <i class="bi bi-flag-fill me-1"></i> Review Complaints (<?= $stats['pending_reports'] ?>)
                </a>
                <a href="<?= base_url('student/dashboard.php') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-person me-1"></i> Student View
                </a>
            </div>
        </div>
    </div>

    <!-- Moderator Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <h3 class="text-danger"><?= $stats['pending_reports'] ?></h3>
                    <p>Pending Reports</p>
                </div>
                <div class="stat-card-icon stat-icon-red"><i class="bi bi-exclamation-octagon-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <h3 class="text-success"><?= $stats['resolved_reports'] ?></h3>
                    <p>Resolved</p>
                </div>
                <div class="stat-card-icon stat-icon-teal"><i class="bi bi-check-circle-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <h3 class="text-warning"><?= $stats['hidden_products'] ?></h3>
                    <p>Hidden Items</p>
                </div>
                <div class="stat-card-icon stat-icon-amber"><i class="bi bi-eye-slash-fill"></i></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-card-info">
                    <h3 class="text-primary"><?= $stats['active_products'] ?></h3>
                    <p>Active Listings</p>
                </div>
                <div class="stat-card-icon stat-icon-blue"><i class="bi bi-shop"></i></div>
            </div>
        </div>
    </div>

    <!-- Pending Reports Table -->
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0"><i class="bi bi-flag me-2 text-danger"></i> Urgent Student Reports Requiring Review</h5>
            <a href="<?= base_url('moderator/reports.php') ?>" class="btn btn-sm btn-obu-outline">
                View All Reports &raquo;
            </a>
        </div>

        <?php if (empty($pendingReports)): ?>
            <div class="text-center py-5">
                <i class="bi bi-check2-circle fs-1 text-success mb-2 d-block"></i>
                <h5 class="fw-bold">No pending reports!</h5>
                <p class="text-muted">The marketplace community queue is clear and running smoothly.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Reporter</th>
                            <th>Reported Entity</th>
                            <th>Reason</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingReports as $rep): ?>
                            <tr>
                                <td><strong>#<?= $rep['id'] ?></strong></td>
                                <td>
                                    <strong><?= e($rep['reporter_name']) ?></strong>
                                    <span class="small text-muted d-block"><?= e($rep['reporter_student_id']) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($rep['product_title'])): ?>
                                        <span class="d-block text-truncate" style="max-width: 200px;">
                                            <i class="bi bi-box-seam text-primary me-1"></i>
                                            <a href="<?= base_url('product-details.php?id=' . $rep['product_id']) ?>" target="_blank">
                                                <?= e($rep['product_title']) ?>
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($rep['reported_user_name'])): ?>
                                        <span class="small text-muted d-block">
                                            <i class="bi bi-person text-danger me-1"></i> User: <?= e($rep['reported_user_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        <?= e($rep['reason']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= time_ago($rep['created_at']) ?></td>
                                <td>
                                    <a href="<?= base_url('moderator/review-report.php?id=' . $rep['id']) ?>" class="btn btn-sm btn-primary py-1 px-3">
                                        Review Report &raquo;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

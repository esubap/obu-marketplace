<?php
/**
 * OBU Student Marketplace - Reports List (Moderator)
 */
require_once __DIR__ . '/../config/app.php';
require_moderator();

$pageTitle = 'Community Reports';
$extraCss = ['dashboard.css'];
$statusFilter = clean_input($_GET['status'] ?? '');

$reports = [];

if (db()) {
    try {
        $where = ["1=1"];
        $params = [];

        if (!empty($statusFilter)) {
            $where[] = "r.status = ?";
            $params[] = $statusFilter;
        }

        $whereClause = implode(" AND ", $where);

        $stmt = db()->prepare("
            SELECT r.*, u_rep.full_name AS reporter_name, u_rep.student_id AS reporter_student_id,
                   p.title AS product_title, p.status AS product_status,
                   u_tgt.full_name AS reported_user_name,
                   u_rev.full_name AS reviewer_name
            FROM reports r
            JOIN users u_rep ON r.reporter_id = u_rep.id
            LEFT JOIN products p ON r.product_id = p.id
            LEFT JOIN users u_tgt ON r.reported_user_id = u_tgt.id
            LEFT JOIN users u_rev ON r.reviewed_by = u_rev.id
            WHERE {$whereClause}
            ORDER BY r.id DESC
        ");
        $stmt->execute($params);
        $reports = $stmt->fetchAll();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-shield-exclamation text-warning me-2"></i> User & Listing Reports</h3>
                <p class="text-secondary mb-0">Review reports submitted by campus community members</p>
            </div>
            <a href="<?= base_url('moderator/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
                &larr; Moderator Overview
            </a>
        </div>

        <!-- Filter pills -->
        <div class="d-flex gap-2 mb-4 flex-wrap">
            <a href="<?= base_url('moderator/reports.php') ?>" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill">
                All Reports (<?= count($reports) ?>)
            </a>
            <a href="<?= base_url('moderator/reports.php?status=pending') ?>" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-danger' : 'btn-outline-secondary' ?> rounded-pill">
                Pending
            </a>
            <a href="<?= base_url('moderator/reports.php?status=resolved') ?>" class="btn btn-sm <?= $statusFilter === 'resolved' ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill">
                Resolved
            </a>
            <a href="<?= base_url('moderator/reports.php?status=rejected') ?>" class="btn btn-sm <?= $statusFilter === 'rejected' ? 'btn-secondary' : 'btn-outline-secondary' ?> rounded-pill">
                Rejected
            </a>
        </div>

        <?php if (empty($reports)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted mb-2 d-block"></i>
                <h5 class="fw-bold">No reports found</h5>
                <p class="text-muted">There are no reports matching this filter.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Reporter</th>
                            <th>Reported Product / User</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $r): ?>
                            <tr>
                                <td><strong>#<?= $r['id'] ?></strong></td>
                                <td>
                                    <strong><?= e($r['reporter_name']) ?></strong>
                                    <span class="small text-muted d-block"><?= e($r['reporter_student_id']) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($r['product_title'])): ?>
                                        <div class="text-truncate" style="max-width: 220px;">
                                            <i class="bi bi-box-seam me-1 text-primary"></i>
                                            <a href="<?= base_url('product-details.php?id=' . $r['product_id']) ?>" target="_blank">
                                                <?= e($r['product_title']) ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($r['reported_user_name'])): ?>
                                        <div class="small text-muted">
                                            <i class="bi bi-person me-1 text-danger"></i> <?= e($r['reported_user_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        <?= e($r['reason']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                        $cls = 'bg-warning text-dark';
                                        if ($r['status'] === 'resolved') $cls = 'bg-success text-white';
                                        if ($r['status'] === 'rejected') $cls = 'bg-secondary text-white';
                                    ?>
                                    <span class="badge <?= $cls ?> rounded-pill px-2.5 py-1">
                                        <?= ucfirst($r['status']) ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= time_ago($r['created_at']) ?></td>
                                <td>
                                    <a href="<?= base_url('moderator/review-report.php?id=' . $r['id']) ?>" class="btn btn-sm btn-primary py-1 px-2.5">
                                        Review &raquo;
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

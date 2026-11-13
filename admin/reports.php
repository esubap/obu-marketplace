<?php
/**
 * OBU Student Marketplace - Reports Management (Admin)
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$pageTitle = t('manage_reports');
$extraCss = ['dashboard.css'];
$currentUser = current_user();

$reports = [];
if (db()) {
    try {
        $stmt = db()->query("
            SELECT r.*, u_rep.full_name AS reporter_name, u_rep.student_id AS reporter_student_id,
                   p.title AS product_title, p.price AS product_price, p.status AS product_status,
                   u_tgt.full_name AS target_user_name,
                   u_rev.full_name AS reviewer_name
            FROM reports r
            JOIN users u_rep ON r.reporter_id = u_rep.id
            LEFT JOIN products p ON r.product_id = p.id
            LEFT JOIN users u_tgt ON r.reported_user_id = u_tgt.id
            LEFT JOIN users u_rev ON r.reviewed_by = u_rev.id
            ORDER BY r.id DESC
        ");
        $reports = $stmt->fetchAll();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-flag-fill text-danger me-2"></i> <?= t('manage_reports') ?></h3>
                <p class="text-secondary mb-0">Platform complaints, security violations, and disciplinary oversight</p>
            </div>
            <a href="<?= base_url('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
                &larr; Admin Overview
            </a>
        </div>

        <?php if (empty($reports)): ?>
            <div class="text-center py-5">
                <i class="bi bi-shield-check fs-1 text-success mb-2 d-block"></i>
                <h5 class="fw-bold">No community reports</h5>
                <p class="text-muted">No safety reports or complaints have been submitted.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Report #</th>
                            <th>Reporter</th>
                            <th>Reported Item / User</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Moderator Action / Notes</th>
                            <th>Date</th>
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
                                        <div class="text-truncate" style="max-width: 180px;">
                                            <a href="<?= base_url('product-details.php?id=' . $r['product_id']) ?>" target="_blank">
                                                <?= e($r['product_title']) ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($r['target_user_name'])): ?>
                                        <span class="small text-muted d-block">User: <?= e($r['target_user_name']) ?></span>
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
                                    <span class="badge <?= $cls ?> rounded-pill px-2 py-1">
                                        <?= ucfirst($r['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small text-secondary" style="max-width: 200px;">
                                        <?= !empty($r['admin_notes']) ? e($r['admin_notes']) : '<span class="text-muted fst-italic">Pending review</span>' ?>
                                    </div>
                                    <?php if ($r['reviewer_name']): ?>
                                        <span class="small text-muted" style="font-size: 0.72rem;">By <?= e($r['reviewer_name']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= time_ago($r['created_at']) ?></td>
                                <td>
                                    <a href="<?= base_url('moderator/review-report.php?id=' . $r['id']) ?>" class="btn btn-sm btn-primary py-1 px-2">
                                        Inspect
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

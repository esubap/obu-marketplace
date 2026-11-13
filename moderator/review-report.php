<?php
/**
 * OBU Student Marketplace - Review Report Details (Moderator)
 */
require_once __DIR__ . '/../config/app.php';
require_moderator();

$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$report = null;

if (db() && $reportId > 0) {
    try {
        $stmt = db()->prepare("
            SELECT r.*, u_rep.full_name AS reporter_name, u_rep.student_id AS reporter_student_id, u_rep.email AS reporter_email,
                   p.title AS product_title, p.price AS product_price, p.status AS product_status, p.description AS product_desc,
                   p.user_id AS product_owner_id,
                   u_tgt.full_name AS target_user_name, u_tgt.email AS target_user_email,
                   u_rev.full_name AS reviewer_name,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) AS product_image
            FROM reports r
            JOIN users u_rep ON r.reporter_id = u_rep.id
            LEFT JOIN products p ON r.product_id = p.id
            LEFT JOIN users u_tgt ON r.reported_user_id = u_tgt.id
            LEFT JOIN users u_rev ON r.reviewed_by = u_rev.id
            WHERE r.id = ?
        ");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch();
    } catch (Exception $e) {}
}

if (!$report) {
    set_flash('danger', 'Report not found.');
    header('Location: ' . base_url('moderator/reports.php'));
    exit;
}

$pageTitle = 'Review Report #' . $report['id'];
$extraCss = ['dashboard.css'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-surface mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
            <div>
                <span class="badge bg-danger mb-1">INCIDENT REPORT #<?= $report['id'] ?></span>
                <h3 class="fw-bold mb-1">Investigation & Moderation Review</h3>
                <p class="text-secondary mb-0">Submitted on <?= date('F j, Y, g:i A', strtotime($report['created_at'])) ?></p>
            </div>
            <a href="<?= base_url('moderator/reports.php') ?>" class="btn btn-outline-secondary btn-sm">
                &larr; Back to Reports List
            </a>
        </div>

        <div class="row g-4 mb-4">
            <!-- Left: Complaint Details -->
            <div class="col-lg-6">
                <div class="card p-4 rounded-3 bg-surface-alt border h-100">
                    <h5 class="fw-bold mb-3 text-danger"><i class="bi bi-shield-exclamation me-2"></i> Complaint Details</h5>

                    <div class="mb-3">
                        <label class="small text-muted text-uppercase fw-bold d-block">Report Category</label>
                        <span class="badge bg-danger fs-6"><?= e($report['reason']) ?></span>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted text-uppercase fw-bold d-block">Reported By</label>
                        <strong><?= e($report['reporter_name']) ?></strong> (<?= e($report['reporter_student_id']) ?>)
                        <div class="small text-muted"><?= e($report['reporter_email']) ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted text-uppercase fw-bold d-block">Description of Violation</label>
                        <div class="p-3 rounded-2 bg-surface border text-secondary" style="white-space: pre-line;">
                            <?= nl2br(e($report['description'])) ?>
                        </div>
                    </div>

                    <div>
                        <label class="small text-muted text-uppercase fw-bold d-block">Current Report Status</label>
                        <span class="badge bg-primary rounded-pill px-3 py-1.5"><?= ucfirst($report['status']) ?></span>
                        <?php if ($report['reviewer_name']): ?>
                            <span class="small text-muted ms-2">Reviewed by <?= e($report['reviewer_name']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Reported Product or User Details -->
            <div class="col-lg-6">
                <div class="card p-4 rounded-3 bg-surface-alt border h-100">
                    <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-box-seam me-2"></i> Flagged Item / User</h5>

                    <?php if (!empty($report['product_id'])): ?>
                        <?php 
                            $img = !empty($report['product_image']) 
                                ? (str_starts_with($report['product_image'], 'assets/') ? asset_url(substr($report['product_image'], 7)) : upload_url('products/' . $report['product_image']))
                                : asset_url('images/placeholders/default-product.svg');
                        ?>
                        <div class="d-flex gap-3 align-items-center mb-3 p-3 rounded-2 bg-surface border">
                            <img src="<?= $img ?>" class="rounded" style="width: 64px; height: 64px; object-fit: cover;" alt="Item">
                            <div>
                                <h6 class="fw-bold mb-0">
                                    <a href="<?= base_url('product-details.php?id=' . $report['product_id']) ?>" target="_blank">
                                        <?= e($report['product_title']) ?>
                                    </a>
                                </h6>
                                <div class="text-primary fw-bold"><?= format_currency($report['product_price']) ?></div>
                                <div class="small">Status: <?= render_status_badge($report['product_status']) ?></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted text-uppercase fw-bold d-block">Listing Description</label>
                            <p class="small text-muted mb-0"><?= nl2br(e($report['product_desc'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($report['target_user_name'])): ?>
                        <div class="p-3 rounded-2 bg-surface border mb-3">
                            <label class="small text-muted text-uppercase fw-bold d-block">Reported User</label>
                            <strong><?= e($report['target_user_name']) ?></strong>
                            <div class="small text-muted"><?= e($report['target_user_email']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($report['admin_notes'])): ?>
                        <div class="mt-3 p-3 rounded-2 bg-warning-subtle border border-warning-subtle">
                            <label class="small text-dark text-uppercase fw-bold d-block">Prior Moderator Notes</label>
                            <div class="small text-dark"><?= nl2br(e($report['admin_notes'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Moderation Actions -->
        <h4 class="fw-bold mb-3">Take Moderation Action</h4>
        <div class="row g-3">
            <!-- 1. Hide Product -->
            <?php if (!empty($report['product_id']) && $report['product_status'] !== 'hidden'): ?>
                <div class="col-md-4">
                    <div class="card p-3 rounded-3 border h-100">
                        <h6 class="fw-bold text-warning mb-2"><i class="bi bi-eye-slash-fill me-1"></i> Hide Suspicious Listing</h6>
                        <p class="small text-muted mb-3">Instantly remove this item from public marketplace search while investigation continues.</p>
                        <form action="<?= base_url('moderator/moderation-actions.php') ?>" method="POST" class="mt-auto">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="hide_product">
                            <input type="hidden" name="product_id" value="<?= $report['product_id'] ?>">
                            <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="Reason for hiding..." required>
                            <button type="submit" class="btn btn-sm btn-warning w-100 fw-bold">
                                Hide Listing Now
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 2. Resolve Report -->
            <div class="col-md-4">
                <div class="card p-3 rounded-3 border h-100">
                    <h6 class="fw-bold text-success mb-2"><i class="bi bi-check-circle-fill me-1"></i> Resolve Report</h6>
                    <p class="small text-muted mb-3">Record corrective action taken (item hidden, student warned, or issue fixed).</p>
                    <form action="<?= base_url('moderator/moderation-actions.php') ?>" method="POST" class="mt-auto">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="resolve_report">
                        <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                        <input type="text" name="admin_notes" class="form-control form-control-sm mb-2" placeholder="Resolution notes..." required>
                        <button type="submit" class="btn btn-sm btn-success w-100 fw-bold">
                            Mark as Resolved
                        </button>
                    </form>
                </div>
            </div>

            <!-- 3. Dismiss Report -->
            <div class="col-md-4">
                <div class="card p-3 rounded-3 border h-100">
                    <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-x-circle-fill me-1"></i> Dismiss / Reject Report</h6>
                    <p class="small text-muted mb-3">Dismiss report if false alarm, invalid complaint, or insufficient evidence.</p>
                    <form action="<?= base_url('moderator/moderation-actions.php') ?>" method="POST" class="mt-auto">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="reject_report">
                        <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                        <input type="text" name="admin_notes" class="form-control form-control-sm mb-2" placeholder="Reason for dismissal..." required>
                        <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                            Dismiss Report
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

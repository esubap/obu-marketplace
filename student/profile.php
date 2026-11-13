<?php
/**
 * OBU Student Marketplace - Student Profile View
 */
require_once __DIR__ . '/../config/app.php';
require_student();

$pageTitle = t('profile_title');
$extraCss = ['dashboard.css'];
$user = current_user();
$userId = (int)$user['id'];

$listingCount = 0;
$soldCount = 0;

if (db()) {
    try {
        $stmt = db()->prepare("SELECT COUNT(*) FROM products WHERE user_id = ?");
        $stmt->execute([$userId]);
        $listingCount = (int)$stmt->fetchColumn();

        $stmtSold = db()->prepare("SELECT COUNT(*) FROM products WHERE user_id = ? AND status = 'sold'");
        $stmtSold->execute([$userId]);
        $soldCount = (int)$stmtSold->fetchColumn();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-surface">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
                    <div>
                        <h3 class="fw-bold mb-1"><?= t('profile_title') ?></h3>
                        <p class="text-secondary mb-0">Your registered university student marketplace identity</p>
                    </div>
                    <a href="<?= base_url('student/edit-profile.php') ?>" class="btn btn-obu">
                        <i class="bi bi-pencil-square me-1"></i> <?= t('edit_profile') ?>
                    </a>
                </div>

                <div class="row g-4 align-items-center mb-5">
                    <div class="col-md-3 text-center">
                        <div class="user-avatar-circle mx-auto mb-2" style="width: 100px; height: 100px; font-size: 2.5rem;">
                            <?php if (!empty($user['profile_image'])): ?>
                                <img src="<?= upload_url('profiles/' . $user['profile_image']) ?>" alt="Avatar">
                            <?php else: ?>
                                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                            <i class="bi bi-check-circle-fill me-1"></i> Verified Student
                        </span>
                    </div>

                    <div class="col-md-9">
                        <h4 class="fw-bold mb-1"><?= e($user['full_name']) ?></h4>
                        <div class="text-muted mb-3">
                            <span class="badge bg-primary text-white me-2"><?= e($user['student_id']) ?></span>
                            <span><?= e($user['department']) ?> &bull; <?= e($user['year_of_study']) ?></span>
                        </div>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3 bg-surface-alt border">
                                    <div class="small text-muted">University</div>
                                    <strong><?= e($user['university']) ?></strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3 bg-surface-alt border">
                                    <div class="small text-muted">Email Address</div>
                                    <strong><?= e($user['email']) ?></strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3 bg-surface-alt border">
                                    <div class="small text-muted">Contact Phone</div>
                                    <strong><?= e($user['phone']) ?></strong>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3 bg-surface-alt border">
                                    <div class="small text-muted">Member Since</div>
                                    <strong><?= date('F j, Y', strtotime($user['created_at'])) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Stats -->
                <h5 class="fw-bold mb-3">Marketplace Activity Summary</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-card-info">
                                <h3><?= $listingCount ?></h3>
                                <p>Total Listings</p>
                            </div>
                            <div class="stat-card-icon stat-icon-blue"><i class="bi bi-box-seam"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-card-info">
                                <h3><?= $soldCount ?></h3>
                                <p>Completed Sales</p>
                            </div>
                            <div class="stat-card-icon stat-icon-teal"><i class="bi bi-trophy"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-card-info">
                                <h3>Active</h3>
                                <p>Account Status</p>
                            </div>
                            <div class="stat-card-icon stat-icon-purple"><i class="bi bi-shield-check"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

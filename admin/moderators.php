<?php
/**
 * OBU Student Marketplace - Moderator Staff Management (Admin)
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$pageTitle = t('manage_moderators');
$extraCss = ['dashboard.css'];
$currentUser = current_user();

// Handle Promote / Demote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_csrf();
    $act = clean_input($_POST['action']);

    if ($act === 'promote') {
        $identifier = clean_input($_POST['identifier'] ?? '');
        if (!empty($identifier)) {
            $stmt = db()->prepare("SELECT * FROM users WHERE email = ? OR student_id = ?");
            $stmt->execute([$identifier, $identifier]);
            $u = $stmt->fetch();

            if ($u) {
                if ($u['role'] === ROLE_ADMIN) {
                    set_flash('danger', 'Administrator accounts cannot be changed to moderator.');
                } else {
                    $stmtUp = db()->prepare("UPDATE users SET role = 'moderator' WHERE id = ?");
                    $stmtUp->execute([$u['id']]);
                    log_activity('ADMIN_PROMOTE_MOD', "Promoted {$u['full_name']} to moderator", (int)$currentUser['id']);
                    set_flash('success', "{$u['full_name']} has been granted Moderator privileges.");
                }
            } else {
                set_flash('danger', 'No student found matching that email or Student ID.');
            }
        }
    }

    if ($act === 'demote') {
        $targetId = (int)($_POST['user_id'] ?? 0);
        if ($targetId > 0 && $targetId !== (int)$currentUser['id']) {
            $stmt = db()->prepare("UPDATE users SET role = 'student' WHERE id = ? AND role = 'moderator'");
            $stmt->execute([$targetId]);
            log_activity('ADMIN_DEMOTE_MOD', "Revoked moderator role for user ID #$targetId", (int)$currentUser['id']);
            set_flash('info', 'Moderator privileges revoked.');
        }
    }

    header('Location: ' . base_url('admin/moderators.php'));
    exit;
}

$moderators = [];
if (db()) {
    try {
        $stmt = db()->query("SELECT * FROM users WHERE role = 'moderator' ORDER BY id ASC");
        $moderators = $stmt->fetchAll();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-shield-check text-warning me-2"></i> <?= t('manage_moderators') ?></h3>
                <p class="text-secondary mb-0">Authorized campus staff and senior students appointed to review marketplace listings</p>
            </div>
            <a href="<?= base_url('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
                &larr; Admin Overview
            </a>
        </div>

        <div class="row g-4 mb-4">
            <!-- Appoint New Moderator Form -->
            <div class="col-lg-5">
                <div class="card p-4 rounded-3 bg-surface-alt border">
                    <h5 class="fw-bold mb-2"><i class="bi bi-person-plus-fill text-primary me-2"></i> Appoint Moderator</h5>
                    <p class="small text-muted mb-3">Enter the university email or student ID of the registered user you wish to authorize.</p>

                    <form action="<?= base_url('admin/moderators.php') ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="promote">

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email or Student ID *</label>
                            <input type="text" name="identifier" class="form-control" placeholder="e.g. moderator@obu.edu.et or MOD-001" required>
                        </div>

                        <button type="submit" class="btn btn-obu w-100">
                            <i class="bi bi-shield-plus me-1"></i> Grant Moderator Role
                        </button>
                    </form>
                </div>
            </div>

            <!-- List of Active Moderators -->
            <div class="col-lg-7">
                <h5 class="fw-bold mb-3">Current Active Moderators (<?= count($moderators) ?>)</h5>
                <?php if (empty($moderators)): ?>
                    <p class="text-muted small">No moderators currently appointed.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>Moderator</th>
                                    <th>ID / Department</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($moderators as $m): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($m['full_name']) ?></strong>
                                            <div class="small text-muted"><?= e($m['email']) ?></div>
                                        </td>
                                        <td class="small">
                                            <?= e($m['student_id']) ?>
                                            <div class="text-muted"><?= e($m['department']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-success rounded-pill px-2 py-1"><?= ucfirst($m['status']) ?></span>
                                        </td>
                                        <td>
                                            <form action="<?= base_url('admin/moderators.php') ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="demote">
                                                <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" onclick="return confirm('Revoke moderator privileges for this user?');">
                                                    Revoke
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

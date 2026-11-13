<?php
/**
 * OBU Student Marketplace - Student Management (Admin)
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$pageTitle = t('manage_students');
$extraCss = ['dashboard.css'];

$search = clean_input($_GET['q'] ?? '');
$statusFilter = clean_input($_GET['status'] ?? '');
$currentUser = current_user();

// Handle Admin Setting / Resetting Student Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_reset_password') {
    require_csrf();
    $targetStudentId = (int)($_POST['student_id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';

    if ($targetStudentId > 0 && strlen($newPassword) >= 6) {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'student'");
        $stmt->execute([$hash, $targetStudentId]);

        // Fetch student info
        $stmtS = db()->prepare("SELECT full_name, student_id FROM users WHERE id = ?");
        $stmtS->execute([$targetStudentId]);
        $stInfo = $stmtS->fetch();

        log_activity('ADMIN_RESET_PASSWORD', "Admin assigned new password for student {$stInfo['full_name']} ({$stInfo['student_id']})", (int)$currentUser['id']);
        set_flash('success', "Password successfully reset for <strong>" . e($stInfo['full_name']) . "</strong> (" . e($stInfo['student_id']) . ")! New password: <code class='fs-6 fw-bold text-dark'>" . e($newPassword) . "</code>. You can now provide this password to the student.");
        header('Location: ' . base_url('admin/students.php'));
        exit;
    } else {
        set_flash('danger', "Password must be at least 6 characters long.");
    }
}

// Handle Account Status Toggles (Activate / Suspend)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $targetId = (int)$_GET['id'];
    $act = clean_input($_GET['action']);
    $csrf = $_GET['csrf_token'] ?? '';

    if (verify_csrf_token($csrf) && $targetId > 0 && $targetId !== (int)$currentUser['id']) {
        if ($act === 'suspend') {
            $stmt = db()->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
            $stmt->execute([$targetId]);
            log_activity('ADMIN_SUSPEND_USER', "Administrator suspended user ID #$targetId", (int)$currentUser['id']);
            set_flash('warning', "User #$targetId has been suspended.");
        } elseif ($act === 'activate') {
            $stmt = db()->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmt->execute([$targetId]);
            log_activity('ADMIN_ACTIVATE_USER', "Administrator activated user ID #$targetId", (int)$currentUser['id']);
            set_flash('success', "User #$targetId has been activated.");
        }
        header('Location: ' . base_url('admin/students.php'));
        exit;
    }
}

$students = [];

if (db()) {
    try {
        $where = ["role = 'student'"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(full_name LIKE ? OR email LIKE ? OR student_id LIKE ? OR department LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if (!empty($statusFilter)) {
            $where[] = "status = ?";
            $params[] = $statusFilter;
        }

        $whereClause = implode(" AND ", $where);
        $stmt = db()->prepare("
            SELECT u.*, 
                   (SELECT COUNT(*) FROM products WHERE user_id = u.id) AS product_count,
                   (SELECT COUNT(*) FROM purchase_requests WHERE buyer_id = u.id) AS buy_request_count
            FROM users u
            WHERE {$whereClause}
            ORDER BY u.id DESC
        ");
        $stmt->execute($params);
        $students = $stmt->fetchAll();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i> <?= t('manage_students') ?></h3>
                <p class="text-secondary mb-0">Monitor verified student registrations, academic standing, and account status</p>
            </div>
            <a href="<?= base_url('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
                &larr; Admin Overview
            </a>
        </div>

        <!-- Filter & Search Bar -->
        <form action="<?= base_url('admin/students.php') ?>" method="GET" class="row g-2 mb-4">
            <div class="col-md-5">
                <input type="text" name="q" class="form-control" placeholder="Search by name, student ID, email, department..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Students</option>
                    <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended Accounts</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-obu flex-grow-1"><i class="bi bi-search me-1"></i> Search</button>
                <a href="<?= base_url('admin/students.php') ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <?php if (empty($students)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people fs-1 text-muted mb-2 d-block"></i>
                <h5 class="fw-bold">No students found</h5>
                <p class="text-muted">No student records match your query.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Department & Year</th>
                            <th>Email & Phone</th>
                            <th>Listings</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $st): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary text-white font-monospace"><?= e($st['student_id']) ?></span>
                                </td>
                                <td>
                                    <strong><?= e($st['full_name']) ?></strong>
                                </td>
                                <td class="small">
                                    <?= e($st['department']) ?>
                                    <span class="text-muted d-block"><?= e($st['year_of_study']) ?></span>
                                </td>
                                <td class="small">
                                    <div><?= e($st['email']) ?></div>
                                    <div class="text-muted"><?= e($st['phone']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= $st['product_count'] ?> items</span>
                                </td>
                                <td>
                                    <?php if ($st['status'] === 'active'): ?>
                                        <span class="badge bg-success rounded-pill px-2.5 py-1">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-pill px-2.5 py-1">Suspended</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= date('M j, Y', strtotime($st['created_at'])) ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <!-- Give Password Button -->
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="openResetPasswordModal(<?= $st['id'] ?>, '<?= addslashes(e($st['full_name'])) ?>', '<?= addslashes(e($st['student_id'])) ?>')" title="Assign / Reset student password">
                                            <i class="bi bi-key-fill me-1"></i> Give Pass
                                        </button>

                                        <!-- Suspend / Reactivate -->
                                        <?php if ($st['status'] === 'active'): ?>
                                            <a href="<?= base_url('admin/students.php?action=suspend&id=' . $st['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-danger" title="Suspend Student" onclick="return confirm('Suspend account for <?= addslashes($st['full_name']) ?>?');">
                                                <i class="bi bi-slash-circle me-1"></i> Suspend
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= base_url('admin/students.php?action=activate&id=' . $st['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-success" title="Reactivate Student">
                                                <i class="bi bi-check-circle me-1"></i> Activate
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Admin Reset Student Password -->
<div class="modal fade" id="adminResetPasswordModal" tabindex="-1" aria-labelledby="adminResetPassLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="<?= base_url('admin/students.php') ?>" class="modal-content border-0 shadow-lg rounded-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="admin_reset_password">
            <input type="hidden" name="student_id" id="modalStudentDbId">

            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="adminResetPassLabel">
                    <i class="bi bi-key-fill text-primary me-2"></i> Set New Student Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    Use this tool when a student forgets their password and requests assistance from the university administration or ICT desk.
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Target Student</label>
                    <input type="text" id="modalStudentName" class="form-control bg-light fw-bold" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">New Password to Assign *</label>
                    <div class="input-group">
                        <input type="text" name="new_password" id="modalNewPassword" class="form-control font-monospace fw-bold text-primary" required minlength="6" value="OBU2026!">
                        <button class="btn btn-outline-secondary" type="button" onclick="generateRandomPass()">
                            <i class="bi bi-dice-5 me-1"></i> Random
                        </button>
                    </div>
                    <div class="form-text small">Standard template is <code>OBU2026!</code>, or click <strong>Random</strong>.</div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="bi bi-check2-circle me-1"></i> Save & Give to Student
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openResetPasswordModal(id, name, studentId) {
    document.getElementById('modalStudentDbId').value = id;
    document.getElementById('modalStudentName').value = name + ' (' + studentId + ')';
    document.getElementById('modalNewPassword').value = 'OBU2026!';
    new bootstrap.Modal(document.getElementById('adminResetPasswordModal')).show();
}

function generateRandomPass() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    let res = 'OBU';
    for (let i = 0; i < 5; i++) {
        res += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    res += '!';
    document.getElementById('modalNewPassword').value = res;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

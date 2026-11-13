<?php
/**
 * OBU Student Marketplace - Platform Audit Activity Logs (Admin)
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$pageTitle = t('activity_logs');
$extraCss = ['dashboard.css'];

$actionFilter = clean_input($_GET['action_filter'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$logs = [];
$totalLogs = 0;
$totalPages = 1;

if (db()) {
    try {
        $where = ["1=1"];
        $params = [];

        if (!empty($actionFilter)) {
            $where[] = "l.action LIKE ?";
            $params[] = "%{$actionFilter}%";
        }

        $whereClause = implode(" AND ", $where);

        $countStmt = db()->prepare("SELECT COUNT(*) FROM activity_logs l WHERE {$whereClause}");
        $countStmt->execute($params);
        $totalLogs = (int)$countStmt->fetchColumn();
        $totalPages = max(1, ceil($totalLogs / $perPage));

        $stmt = db()->prepare("
            SELECT l.*, u.full_name AS user_name, u.email AS user_email, u.role AS user_role
            FROM activity_logs l
            LEFT JOIN users u ON l.user_id = u.id
            WHERE {$whereClause}
            ORDER BY l.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-clock-history text-purple me-2"></i> <?= t('activity_logs') ?></h3>
                <p class="text-secondary mb-0">Immutable compliance log recording user authentication, moderation, and system updates</p>
            </div>
            <a href="<?= base_url('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
                &larr; Admin Overview
            </a>
        </div>

        <!-- Filter Bar -->
        <form action="<?= base_url('admin/activity-logs.php') ?>" method="GET" class="row g-2 mb-4">
            <div class="col-md-6">
                <input type="text" name="action_filter" class="form-control form-control-sm" placeholder="Filter by action keyword (LOGIN, REGISTER, PRODUCT, MOD, etc.)..." value="<?= e($actionFilter) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-obu btn-sm flex-grow-1">Filter Logs</button>
                <a href="<?= base_url('admin/activity-logs.php') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>

        <?php if (empty($logs)): ?>
            <div class="text-center py-5">
                <i class="bi bi-journal-text fs-1 text-muted mb-2 d-block"></i>
                <h5 class="fw-bold">No activity logs recorded</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Action Type</th>
                            <th>User</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="font-monospace text-muted">#<?= $log['id'] ?></td>
                                <td>
                                    <span class="badge bg-secondary font-monospace" style="font-size: 0.72rem;">
                                        <?= e($log['action']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['user_name']): ?>
                                        <strong><?= e($log['user_name']) ?></strong>
                                        <span class="small text-muted d-block"><?= e($log['user_role']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">System / Guest</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small" style="max-width: 320px;"><?= e($log['description']) ?></td>
                                <td><code><?= e($log['ip_address'] ?? '127.0.0.1') ?></code></td>
                                <td class="small text-muted"><?= date('M j, Y, g:i A', strtotime($log['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination pagination-sm justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= base_url('admin/activity-logs.php?page=' . ($page - 1) . '&action_filter=' . urlencode($actionFilter)) ?>">&laquo;</a>
                        </li>
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= base_url('admin/activity-logs.php?page=' . $p . '&action_filter=' . urlencode($actionFilter)) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= base_url('admin/activity-logs.php?page=' . ($page + 1) . '&action_filter=' . urlencode($actionFilter)) ?>">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

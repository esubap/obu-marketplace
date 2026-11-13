<?php
/**
 * OBU Student Marketplace - Product Catalog Management (Admin)
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$pageTitle = t('manage_products');
$extraCss = ['dashboard.css'];
$currentUser = current_user();

$search = clean_input($_GET['q'] ?? '');
$statusFilter = clean_input($_GET['status'] ?? '');
$categoryFilter = !empty($_GET['category']) ? (int)$_GET['category'] : 0;
$categories = get_all_categories();

// Handle Admin Actions (Approve, Hide, Delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $targetId = (int)$_GET['id'];
    $act = clean_input($_GET['action']);
    $csrf = $_GET['csrf_token'] ?? '';

    if (verify_csrf_token($csrf) && $targetId > 0) {
        if ($act === 'approve') {
            $stmt = db()->prepare("UPDATE products SET status = 'active' WHERE id = ?");
            $stmt->execute([$targetId]);
            log_activity('ADMIN_APPROVE_PROD', "Admin approved product #$targetId", (int)$currentUser['id']);
            set_flash('success', "Product #$targetId approved and active.");
        } elseif ($act === 'hide') {
            $stmt = db()->prepare("UPDATE products SET status = 'hidden' WHERE id = ?");
            $stmt->execute([$targetId]);
            log_activity('ADMIN_HIDE_PROD', "Admin hid product #$targetId", (int)$currentUser['id']);
            set_flash('warning', "Product #$targetId is now hidden.");
        } elseif ($act === 'delete') {
            $stmt = db()->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$targetId]);
            log_activity('ADMIN_DEL_PROD', "Admin deleted product #$targetId", (int)$currentUser['id']);
            set_flash('info', "Product #$targetId was permanently removed.");
        }
        header('Location: ' . base_url('admin/products.php'));
        exit;
    }
}

$products = [];

if (db()) {
    try {
        $where = ["1=1"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(p.title LIKE ? OR u.full_name LIKE ? OR u.student_id LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if (!empty($statusFilter)) {
            $where[] = "p.status = ?";
            $params[] = $statusFilter;
        }

        if ($categoryFilter > 0) {
            $where[] = "p.category_id = ?";
            $params[] = $categoryFilter;
        }

        $whereClause = implode(" AND ", $where);
        $stmt = db()->prepare("
            SELECT p.*, c.name_en AS cat_en, c.name_am AS cat_am, c.name_om AS cat_om,
                   u.full_name AS seller_name, u.student_id AS seller_student_id,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) AS primary_image
            FROM products p
            JOIN categories c ON p.category_id = c.id
            JOIN users u ON p.user_id = u.id
            WHERE {$whereClause}
            ORDER BY p.id DESC
        ");
        $stmt->execute($params);
        $products = $stmt->fetchAll();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-box-seam-fill text-primary me-2"></i> <?= t('manage_products') ?></h3>
                <p class="text-secondary mb-0">Review, approve, hide, and moderate listings across all campus faculties</p>
            </div>
            <a href="<?= base_url('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
                &larr; Admin Overview
            </a>
        </div>

        <!-- Filter Bar -->
        <form action="<?= base_url('admin/products.php') ?>" method="GET" class="row g-2 mb-4">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" placeholder="Search product or seller..." value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryFilter === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e(localized($cat, 'name')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="sold" <?= $statusFilter === 'sold' ? 'selected' : '' ?>>Sold</option>
                    <option value="hidden" <?= $statusFilter === 'hidden' ? 'selected' : '' ?>>Hidden</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-obu flex-grow-1"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a href="<?= base_url('admin/products.php') ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="bi bi-box-seam fs-1 text-muted mb-2 d-block"></i>
                <h5 class="fw-bold">No listings found</h5>
                <p class="text-muted">No items match your filter criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Seller</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod): ?>
                            <?php 
                                $img = !empty($prod['primary_image']) 
                                    ? (str_starts_with($prod['primary_image'], 'assets/') ? asset_url(substr($prod['primary_image'], 7)) : upload_url('products/' . $prod['primary_image']))
                                    : asset_url('images/placeholders/default-product.svg');
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= $img ?>" alt="Thumb" class="table-thumb">
                                        <div>
                                            <a href="<?= base_url('product-details.php?id=' . $prod['id']) ?>" target="_blank" class="fw-bold d-block text-truncate" style="max-width: 170px;">
                                                <?= e($prod['title']) ?>
                                            </a>
                                            <span class="small text-muted"><?= render_condition_badge($prod['condition']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e(localized(['name_en' => $prod['cat_en'], 'name_am' => $prod['cat_am'], 'name_om' => $prod['cat_om']], 'name')) ?></td>
                                <td>
                                    <strong><?= e($prod['seller_name']) ?></strong>
                                    <span class="small text-muted d-block"><?= e($prod['seller_student_id']) ?></span>
                                </td>
                                <td class="fw-bold text-primary"><?= format_currency($prod['price']) ?></td>
                                <td><?= render_status_badge($prod['status']) ?></td>
                                <td><i class="bi bi-eye text-muted me-1"></i><?= $prod['views_count'] ?></td>
                                <td class="small text-muted"><?= date('M j, Y', strtotime($prod['created_at'])) ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <a href="<?= base_url('product-details.php?id=' . $prod['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($prod['status'] !== 'active'): ?>
                                            <a href="<?= base_url('admin/products.php?action=approve&id=' . $prod['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-success" title="Approve / Activate listing">
                                                <i class="bi bi-check-lg"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($prod['status'] === 'active'): ?>
                                            <a href="<?= base_url('admin/products.php?action=hide&id=' . $prod['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-warning" title="Hide listing from students">
                                                <i class="bi bi-eye-slash"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= base_url('admin/products.php?action=delete&id=' . $prod['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-danger fw-semibold" title="Permanently Remove / Delete listing" onclick="return confirm('Permanently remove and delete this product from the university marketplace?');">
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
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

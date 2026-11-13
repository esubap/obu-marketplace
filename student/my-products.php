<?php
/**
 * OBU Student Marketplace - My Products (Module 4)
 */
require_once __DIR__ . '/../config/app.php';
require_student();

$pageTitle = t('nav_my_products');
$extraCss = ['dashboard.css'];
$user = current_user();
$userId = (int)$user['id'];

$statusFilter = clean_input($_GET['status'] ?? '');
$myProducts = [];

if (db()) {
    try {
        $where = ["p.user_id = ?"];
        $params = [$userId];

        if (!empty($statusFilter)) {
            $where[] = "p.status = ?";
            $params[] = $statusFilter;
        }

        $whereClause = implode(" AND ", $where);

        $stmt = db()->prepare("
            SELECT p.*, c.name_en AS cat_en, c.name_am AS cat_am, c.name_om AS cat_om,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) AS primary_image,
                   (SELECT COUNT(*) FROM purchase_requests WHERE product_id = p.id) AS total_requests
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE {$whereClause}
            ORDER BY p.id DESC
        ");
        $stmt->execute($params);
        $myProducts = $stmt->fetchAll();

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
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
                    <div>
                        <h3 class="fw-bold mb-1"><?= t('nav_my_products') ?></h3>
                        <p class="text-secondary mb-0">Manage and track your marketplace product listings</p>
                    </div>
                    <a href="<?= base_url('student/add-product.php') ?>" class="btn btn-obu">
                        <i class="bi bi-plus-circle-fill me-1"></i> <?= t('nav_add_product') ?>
                    </a>
                </div>

                <!-- Status Filter Pills -->
                <div class="d-flex gap-2 mb-4 flex-wrap">
                    <a href="<?= base_url('student/my-products.php') ?>" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill">
                        All Items (<?= count($myProducts) ?>)
                    </a>
                    <a href="<?= base_url('student/my-products.php?status=active') ?>" class="btn btn-sm <?= $statusFilter === 'active' ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill">
                        Active
                    </a>
                    <a href="<?= base_url('student/my-products.php?status=sold') ?>" class="btn btn-sm <?= $statusFilter === 'sold' ? 'btn-secondary' : 'btn-outline-secondary' ?> rounded-pill">
                        Sold
                    </a>
                    <a href="<?= base_url('student/my-products.php?status=hidden') ?>" class="btn btn-sm <?= $statusFilter === 'hidden' ? 'btn-dark' : 'btn-outline-secondary' ?> rounded-pill">
                        Hidden
                    </a>
                </div>

                <?php if (empty($myProducts)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-box-seam fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="fw-bold">No product listings found</h5>
                        <p class="text-muted">You haven't posted any items under this filter yet.</p>
                        <a href="<?= base_url('student/add-product.php') ?>" class="btn btn-obu mt-2">
                            <i class="bi bi-plus-circle me-1"></i> <?= t('nav_add_product') ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Condition</th>
                                    <th>Status</th>
                                    <th>Requests</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myProducts as $prod): ?>
                                    <?php 
                                        $thumb = !empty($prod['primary_image']) 
                                            ? (str_starts_with($prod['primary_image'], 'assets/') ? asset_url(substr($prod['primary_image'], 7)) : upload_url('products/' . $prod['primary_image']))
                                            : asset_url('images/placeholders/default-product.svg');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= $thumb ?>" alt="Thumb" class="table-thumb">
                                                <div>
                                                    <a href="<?= base_url('product-details.php?id=' . $prod['id']) ?>" class="fw-bold text-decoration-none d-block text-truncate" style="max-width: 180px;">
                                                        <?= e($prod['title']) ?>
                                                    </a>
                                                    <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($prod['location']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= e(localized(['name_en' => $prod['cat_en'], 'name_am' => $prod['cat_am'], 'name_om' => $prod['cat_om']], 'name')) ?></td>
                                        <td class="fw-bold text-primary"><?= format_currency($prod['price']) ?></td>
                                        <td><?= render_condition_badge($prod['condition']) ?></td>
                                        <td><?= render_status_badge($prod['status']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= $prod['total_requests'] ?> requests
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <!-- Edit Button -->
                                                <a href="<?= base_url('student/edit-product.php?id=' . $prod['id']) ?>" class="btn btn-sm btn-outline-primary" title="Edit Listing">
                                                    <i class="bi bi-pencil"></i>
                                                </a>

                                                <!-- Mark Sold / Available Toggle -->
                                                <?php if ($prod['status'] === 'active'): ?>
                                                    <a href="<?= base_url('actions/product-action.php?action=mark_sold&id=' . $prod['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-secondary" title="<?= t('mark_as_sold') ?>" onclick="return confirm('Mark this product as sold?');">
                                                        <i class="bi bi-check2-circle text-success"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= base_url('actions/product-action.php?action=mark_active&id=' . $prod['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-info" title="<?= t('mark_as_available') ?>">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <!-- Prominent Direct Delete Button -->
                                                <a href="<?= base_url('student/delete-product.php?id=' . $prod['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-danger" title="<?= t('delete_product') ?>" onclick="return confirm('<?= t('confirm_delete_product') ?>');">
                                                    <i class="bi bi-trash-fill"></i>
                                                </a>

                                                <!-- View Details -->
                                                <a href="<?= base_url('product-details.php?id=' . $prod['id']) ?>" class="btn btn-sm btn-light border" title="<?= t('view_details') ?>">
                                                    <i class="bi bi-eye"></i>
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
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * OBU Student Marketplace - Category Management (Admin)
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$pageTitle = t('manage_categories');
$extraCss = ['dashboard.css'];
$currentUser = current_user();

// Handle Add / Edit Category POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_csrf();
    $act = clean_input($_POST['action']);

    if ($act === 'create') {
        $nameEn = clean_input($_POST['name_en'] ?? '');
        $nameAm = clean_input($_POST['name_am'] ?? '');
        $nameOm = clean_input($_POST['name_om'] ?? '');
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', clean_input($_POST['slug'] ?? $nameEn)));
        $icon = clean_input($_POST['icon'] ?? 'bi-tag');
        $desc = clean_input($_POST['description'] ?? '');

        if (!empty($nameEn) && !empty($nameAm) && !empty($nameOm)) {
            try {
                $stmt = db()->prepare("
                    INSERT INTO categories (name_en, name_am, name_om, slug, icon, description, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'active')
                ");
                $stmt->execute([$nameEn, $nameAm, $nameOm, $slug, $icon, $desc]);
                log_activity('ADMIN_ADD_CAT', "Created category: $nameEn", (int)$currentUser['id']);
                set_flash('success', "Category '{$nameEn}' created successfully with multilingual names.");
            } catch (Exception $e) {
                set_flash('danger', 'Error adding category: ' . $e->getMessage());
            }
        } else {
            set_flash('danger', 'English, Amharic, and Afaan Oromo names are required for all categories.');
        }
    }

    if ($act === 'toggle_status') {
        $catId = (int)($_POST['category_id'] ?? 0);
        $newStatus = clean_input($_POST['new_status'] ?? 'active');
        if ($catId > 0) {
            $stmt = db()->prepare("UPDATE categories SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $catId]);
            set_flash('success', 'Category status updated.');
        }
    }

    header('Location: ' . base_url('admin/categories.php'));
    exit;
}

$categoriesList = [];
if (db()) {
    try {
        $stmt = db()->query("
            SELECT c.*, COUNT(p.id) AS product_count
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            GROUP BY c.id
            ORDER BY c.id ASC
        ");
        $categoriesList = $stmt->fetchAll();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-tags-fill text-primary me-2"></i> <?= t('manage_categories') ?></h3>
                <p class="text-secondary mb-0">Multilingual taxonomy configuration for English, Amharic, and Afaan Oromo</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-obu btn-sm" data-bs-toggle="modal" data-bs-target="#newCategoryModal">
                    <i class="bi bi-plus-circle me-1"></i> Add New Category
                </button>
                <a href="<?= base_url('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
                    &larr; Admin Overview
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>English Name</th>
                        <th>Amharic (አማርኛ)</th>
                        <th>Afaan Oromoo</th>
                        <th>Slug</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categoriesList as $cat): ?>
                        <tr>
                            <td>
                                <div class="stat-card-icon stat-icon-blue" style="width: 38px; height: 38px; font-size: 1.1rem;">
                                    <i class="bi <?= e($cat['icon'] ?? 'bi-tag') ?>"></i>
                                </div>
                            </td>
                            <td><strong><?= e($cat['name_en']) ?></strong></td>
                            <td><?= e($cat['name_am']) ?></td>
                            <td><?= e($cat['name_om']) ?></td>
                            <td><code><?= e($cat['slug']) ?></code></td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= $cat['product_count'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $cat['status'] === 'active' ? 'success' : 'secondary' ?> rounded-pill">
                                    <?= ucfirst($cat['status']) ?>
                                </span>
                            </td>
                            <td>
                                <form action="<?= base_url('admin/categories.php') ?>" method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $cat['status'] === 'active' ? 'inactive' : 'active' ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary py-1 px-2">
                                        <?= $cat['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="newCategoryModal" tabindex="-1" aria-labelledby="newCatLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="newCatLabel">
                    <i class="bi bi-plus-circle text-primary me-2"></i> Add Multilingual Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('admin/categories.php') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">English Name *</label>
                        <input type="text" name="name_en" class="form-control" placeholder="e.g. Laboratory Equipment" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Amharic Name (አማርኛ) *</label>
                        <input type="text" name="name_am" class="form-control" placeholder="ለምሳሌ፡ የላብራቶሪ መሣሪያዎች" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Afaan Oromo Name (Afaan Oromoo) *</label>
                        <input type="text" name="name_om" class="form-control" placeholder="Fkn: Meeshaalee Laabiraatorii" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">URL Slug</label>
                            <input type="text" name="slug" class="form-control" placeholder="lab-equipment">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Bootstrap Icon</label>
                            <input type="text" name="icon" class="form-control" value="bi-box-seam" placeholder="bi-tag">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Academic description..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-obu">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

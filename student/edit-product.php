<?php
/**
 * OBU Student Marketplace - Edit Product (Module 4)
 */
require_once __DIR__ . '/../config/app.php';
require_student();

$pageTitle = t('edit_product_title');
$extraCss = ['dashboard.css'];
$user = current_user();
$userId = (int)$user['id'];
$categories = get_all_categories();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
$images = [];

if (db() && $productId > 0) {
    try {
        $stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if ($product) {
            // Strict Authorization Check: Only owner or admin can edit
            if ((int)$product['user_id'] !== $userId && $user['role'] !== ROLE_ADMIN) {
                set_flash('danger', t('error_unauthorized'));
                header('Location: ' . base_url('student/my-products.php'));
                exit;
            }

            $stmtImg = db()->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
            $stmtImg->execute([$productId]);
            $images = $stmtImg->fetchAll();
        }
    } catch (Exception $e) {}
}

if (!$product) {
    set_flash('danger', t('error_404_desc'));
    header('Location: ' . base_url('student/my-products.php'));
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main Form Area -->
        <div class="col-lg-9">
            <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-surface">
                <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                    <div>
                        <h3 class="fw-bold mb-1"><?= t('edit_product_title') ?></h3>
                        <p class="text-secondary mb-0">Update listing details, pricing, and photos</p>
                    </div>
                    <a href="<?= base_url('student/my-products.php') ?>" class="btn btn-sm btn-outline-secondary">
                        &larr; <?= t('nav_my_products') ?>
                    </a>
                </div>

                <form action="<?= base_url('actions/product-action.php') ?>" method="POST" enctype="multipart/form-data" class="needs-validation">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                    <div class="row g-4">
                        <!-- Product Title -->
                        <div class="col-12">
                            <label class="form-label fw-bold"><?= t('product_title') ?> *</label>
                            <input type="text" name="title" class="form-control" value="<?= e($product['title']) ?>" required maxlength="200">
                        </div>

                        <!-- Category -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('product_category') ?> *</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= (int)$product['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                                        <?= e(localized($cat, 'name')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price in ETB -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('product_price') ?> *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-surface-alt">ETB</span>
                                <input type="number" step="0.5" min="0" name="price" class="form-control" value="<?= e($product['price']) ?>" required>
                            </div>
                        </div>

                        <!-- Condition -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('product_condition') ?> *</label>
                            <select name="condition" class="form-select" required>
                                <?php foreach (PRODUCT_CONDITIONS as $cKey => $cLabels): ?>
                                    <option value="<?= $cKey ?>" <?= $product['condition'] === $cKey ? 'selected' : '' ?>>
                                        <?= e($cLabels[current_lang()] ?? $cKey) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('availability_status') ?> *</label>
                            <select name="status" class="form-select" required>
                                <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>><?= t('status_available') ?></option>
                                <option value="sold" <?= $product['status'] === 'sold' ? 'selected' : '' ?>><?= t('status_sold') ?></option>
                                <option value="hidden" <?= $product['status'] === 'hidden' ? 'selected' : '' ?>><?= t('status_hidden') ?></option>
                            </select>
                        </div>

                        <!-- Campus Meeting Location -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('product_location') ?> *</label>
                            <input type="text" name="location" class="form-control" value="<?= e($product['location']) ?>" required maxlength="150">
                        </div>

                        <!-- Contact Preference -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Contact Preference</label>
                            <select name="contact_preference" class="form-select">
                                <option value="internal_message" <?= $product['contact_preference'] === 'internal_message' ? 'selected' : '' ?>>Internal Messages Only</option>
                                <option value="both" <?= $product['contact_preference'] === 'both' ? 'selected' : '' ?>>Both Messages & Phone</option>
                            </select>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label fw-bold"><?= t('product_description') ?> *</label>
                            <textarea name="description" class="form-control" rows="5" required><?= e($product['description']) ?></textarea>
                        </div>

                        <!-- Existing Images -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Current Photos</label>
                            <div class="row g-2">
                                <?php foreach ($images as $img): ?>
                                    <?php 
                                        $iSrc = str_starts_with($img['image_path'], 'assets/') 
                                            ? asset_url(substr($img['image_path'], 7)) 
                                            : upload_url('products/' . $img['image_path']);
                                    ?>
                                    <div class="col-3">
                                        <div class="card p-1 border shadow-sm position-relative">
                                            <img src="<?= $iSrc ?>" class="rounded w-100" style="height: 100px; object-fit: cover;" alt="Image">
                                            <?php if ($img['is_primary']): ?>
                                                <span class="badge bg-primary position-absolute top-0 start-0 m-2">Primary</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Upload additional photos -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Add Additional Photos (Optional)</label>
                            <input type="file" name="images[]" id="productImageInput" class="form-control" accept="image/jpeg,image/png,image/webp" multiple>
                            <div class="form-text small"><?= t('product_images_help') ?></div>
                            <div class="row g-2 mt-2" id="imagePreviewContainer"></div>
                        </div>

                        <!-- Submit Buttons & Direct Delete -->
                        <div class="col-12 mt-4 pt-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-obu px-5 py-2.5">
                                    <i class="bi bi-save me-1"></i> <?= t('update_product') ?>
                                </button>
                                <a href="<?= base_url('student/my-products.php') ?>" class="btn btn-outline-secondary px-4 py-2.5">
                                    Cancel
                                </a>
                            </div>
                            <a href="<?= base_url('student/delete-product.php?id=' . $product['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-outline-danger px-4 py-2.5 fw-semibold" onclick="return confirm('<?= t('confirm_delete_product') ?>');">
                                <i class="bi bi-trash-fill me-1"></i> <?= t('delete_product') ?>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

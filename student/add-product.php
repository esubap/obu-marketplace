<?php
/**
 * OBU Student Marketplace - Publish New Product (Module 4)
 */
require_once __DIR__ . '/../config/app.php';
require_student();

$pageTitle = t('add_product_title');
$extraCss = ['dashboard.css'];
$categories = get_all_categories();

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
                        <h3 class="fw-bold mb-1"><?= t('add_product_title') ?></h3>
                        <p class="text-secondary mb-0">List an item for sale to peers at <?= e(t('university_name')) ?></p>
                    </div>
                    <a href="<?= base_url('student/my-products.php') ?>" class="btn btn-sm btn-outline-secondary">
                        &larr; <?= t('nav_my_products') ?>
                    </a>
                </div>

                <form action="<?= base_url('actions/product-action.php') ?>" method="POST" enctype="multipart/form-data" class="needs-validation">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create">

                    <div class="row g-4">
                        <!-- Product Title -->
                        <div class="col-12">
                            <label class="form-label fw-bold"><?= t('product_title') ?> *</label>
                            <input type="text" name="title" class="form-control" placeholder="<?= t('product_title_placeholder') ?>" required maxlength="200">
                        </div>

                        <!-- Category -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('product_category') ?> *</label>
                            <select name="category_id" class="form-select" required>
                                <option value=""><?= t('product_category') ?>...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= e(localized($cat, 'name')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price in ETB -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('product_price') ?> *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-surface-alt">ETB</span>
                                <input type="number" step="0.5" min="0" name="price" class="form-control" placeholder="<?= t('product_price_placeholder') ?>" required>
                            </div>
                        </div>

                        <!-- Condition -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('product_condition') ?> *</label>
                            <select name="condition" class="form-select" required>
                                <option value=""><?= t('product_condition') ?>...</option>
                                <?php foreach (PRODUCT_CONDITIONS as $cKey => $cLabels): ?>
                                    <option value="<?= $cKey ?>"><?= e($cLabels[current_lang()] ?? $cKey) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Campus Meeting Location -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('product_location') ?> *</label>
                            <input type="text" name="location" class="form-control" placeholder="<?= t('product_location_placeholder') ?>" required maxlength="150">
                        </div>

                        <!-- Contact Preference -->
                        <div class="col-12">
                            <label class="form-label fw-bold">Contact Preference</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="contact_preference" id="pref_internal" value="internal_message" checked>
                                    <label class="form-check-label" for="pref_internal">
                                        Internal Marketplace Messages (Recommended)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="contact_preference" id="pref_both" value="both">
                                    <label class="form-check-label" for="pref_both">
                                        Both Messages & Phone Number
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Detailed Description -->
                        <div class="col-12">
                            <label class="form-label fw-bold"><?= t('product_description') ?> *</label>
                            <textarea name="description" class="form-control" rows="5" placeholder="<?= t('product_description_placeholder') ?>" required></textarea>
                            <div class="form-text small">
                                Write in any language you prefer (English, Amharic, or Afaan Oromo). Accurate details help peers make quick purchase decisions.
                            </div>
                        </div>

                        <!-- Image Upload & Preview -->
                        <div class="col-12">
                            <label class="form-label fw-bold"><?= t('product_images') ?> *</label>
                            <input type="file" name="images[]" id="productImageInput" class="form-control" accept="image/jpeg,image/png,image/webp" multiple required>
                            <div class="form-text small"><?= t('product_images_help') ?></div>

                            <!-- Preview row populated by JavaScript -->
                            <div class="row g-2 mt-2" id="imagePreviewContainer"></div>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-obu px-5 py-2.5 fs-6">
                                <i class="bi bi-cloud-arrow-up-fill me-1"></i> <?= t('save_product') ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

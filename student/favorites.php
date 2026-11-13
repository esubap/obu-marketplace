<?php
/**
 * OBU Student Marketplace - My Favorites (Module 8)
 */
require_once __DIR__ . '/../config/app.php';
require_student();

$pageTitle = t('favorites_title');
$extraCss = ['dashboard.css'];
$user = current_user();
$userId = (int)$user['id'];

$favoriteItems = [];

if (db()) {
    try {
        $stmt = db()->prepare("
            SELECT p.*, f.id AS fav_id, f.created_at AS saved_at,
                   c.name_en AS cat_en, c.name_am AS cat_am, c.name_om AS cat_om,
                   u.full_name AS seller_name,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) AS primary_image
            FROM favorites f
            JOIN products p ON f.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            JOIN users u ON p.user_id = u.id
            WHERE f.user_id = ?
            ORDER BY f.id DESC
        ");
        $stmt->execute([$userId]);
        $favoriteItems = $stmt->fetchAll();
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
                <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                    <div>
                        <h3 class="fw-bold mb-1"><?= t('favorites_title') ?></h3>
                        <p class="text-secondary mb-0">Items you've bookmarked for future reference</p>
                    </div>
                    <span class="badge bg-primary rounded-pill px-3 py-2">
                        <?= count($favoriteItems) ?> Saved
                    </span>
                </div>

                <?php if (empty($favoriteItems)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-heart fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="fw-bold"><?= t('favorites_empty') ?></h5>
                        <p class="text-muted">Explore the marketplace and click the heart icon on any item you wish to follow.</p>
                        <a href="<?= base_url('products.php') ?>" class="btn btn-obu mt-2">
                            <i class="bi bi-grid-fill me-1"></i> <?= t('nav_browse') ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($favoriteItems as $item): ?>
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="obu-card">
                                    <div class="obu-card-img-wrap">
                                        <span class="obu-card-badge">
                                            <?= render_status_badge($item['status']) ?>
                                        </span>
                                        <a href="<?= base_url('actions/favorite-action.php?product_id=' . $item['id'] . '&csrf_token=' . csrf_token()) ?>" class="obu-card-fav-btn" title="<?= t('remove_from_favorites') ?>">
                                            <i class="bi bi-heart-fill text-danger fs-6"></i>
                                        </a>
                                        <a href="<?= base_url('product-details.php?id=' . $item['id']) ?>" class="w-100 h-100">
                                            <?php 
                                                $imgSrc = !empty($item['primary_image']) 
                                                    ? (str_starts_with($item['primary_image'], 'assets/') ? asset_url(substr($item['primary_image'], 7)) : upload_url('products/' . $item['primary_image']))
                                                    : asset_url('images/placeholders/default-product.svg');
                                            ?>
                                            <img src="<?= $imgSrc ?>" alt="<?= e($item['title']) ?>">
                                        </a>
                                    </div>
                                    <div class="obu-card-body">
                                        <div class="obu-card-category">
                                            <?= e(localized(['name_en' => $item['cat_en'], 'name_am' => $item['cat_am'], 'name_om' => $item['cat_om']], 'name')) ?>
                                        </div>
                                        <h5 class="obu-card-title">
                                            <a href="<?= base_url('product-details.php?id=' . $item['id']) ?>"><?= e($item['title']) ?></a>
                                        </h5>
                                        <div class="obu-card-price"><?= format_currency($item['price']) ?></div>
                                        <div class="obu-card-meta">
                                            <span class="text-truncate"><i class="bi bi-person me-1"></i><?= e($item['seller_name']) ?></span>
                                            <span class="ms-auto"><?= render_condition_badge($item['condition']) ?></span>
                                        </div>
                                        <div class="d-flex gap-2 mt-3">
                                            <a href="<?= base_url('product-details.php?id=' . $item['id']) ?>" class="btn btn-sm btn-obu flex-grow-1">
                                                <?= t('view_details') ?>
                                            </a>
                                            <a href="<?= base_url('actions/favorite-action.php?product_id=' . $item['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-danger" title="<?= t('remove_from_favorites') ?>">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

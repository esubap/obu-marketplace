<?php
/**
 * OBU Student Marketplace - Homepage (Module 1: Landing Page)
 */
require_once __DIR__ . '/config/app.php';

$pageTitle = t('nav_home');
$categories = get_all_categories();

// Fetch latest products
$latestProducts = [];
$featuredProducts = [];

if (db()) {
    try {
        // Latest 8 active products
        $stmt = db()->query("
            SELECT p.*, c.name_en AS cat_en, c.name_am AS cat_am, c.name_om AS cat_om,
                   u.full_name AS seller_name, u.department AS seller_dept,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS primary_image
            FROM products p
            JOIN categories c ON p.category_id = c.id
            JOIN users u ON p.user_id = u.id
            WHERE p.status = 'active'
            ORDER BY p.id DESC
            LIMIT 8
        ");
        $latestProducts = $stmt->fetchAll();

        // Featured products (highest views)
        $stmtFeat = db()->query("
            SELECT p.*, c.name_en AS cat_en, c.name_am AS cat_am, c.name_om AS cat_om,
                   u.full_name AS seller_name, u.department AS seller_dept,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS primary_image
            FROM products p
            JOIN categories c ON p.category_id = c.id
            JOIN users u ON p.user_id = u.id
            WHERE p.status = 'active'
            ORDER BY p.views_count DESC, p.id DESC
            LIMIT 4
        ");
        $featuredProducts = $stmtFeat->fetchAll();
    } catch (Exception $e) {
        // Fallback gracefully if database table not yet populated
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. HERO SECTION -->
<section class="hero-section">
    <div class="container position-relative">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill mb-3" style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(8px);">
                    <i class="bi bi-patch-check-fill text-warning"></i>
                    <span class="small fw-semibold"><?= e(t('university_name')) ?> Verified Marketplace</span>
                </div>
                <h1 class="hero-title"><?= e(t('hero_title')) ?></h1>
                <p class="hero-sub mx-auto"><?= e(t('hero_subtitle')) ?></p>

                <!-- Search Bar Form -->
                <form action="<?= base_url('search.php') ?>" method="GET" class="hero-search-box d-flex align-items-center gap-2 mb-4">
                    <i class="bi bi-search fs-5 text-muted ps-3"></i>
                    <input type="text" name="q" class="hero-search-input" placeholder="<?= e(t('search_placeholder')) ?>" required>
                    <select name="category" class="form-select border-0 d-none d-md-block" style="max-width: 200px; background: transparent;">
                        <option value=""><?= t('all_categories') ?></option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= e(localized($cat, 'name')) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-obu px-4 py-2.5">
                        <?= t('search_button') ?>
                    </button>
                </form>

                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="<?= base_url('products.php') ?>" class="btn btn-outline-light px-4 py-2 rounded-pill fw-semibold">
                        <i class="bi bi-grid-fill me-1"></i> <?= t('browse_all_products') ?>
                    </a>
                    <a href="<?= is_logged_in() ? base_url('student/add-product.php') : base_url('login.php') ?>" class="btn btn-warning px-4 py-2 rounded-pill fw-bold text-dark">
                        <i class="bi bi-plus-circle-fill me-1"></i> <?= t('post_free_ad') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. POPULAR CATEGORIES -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="fw-bold mb-1"><?= t('popular_categories') ?></h2>
                <p class="text-muted mb-0"><?= t('popular_categories_sub') ?></p>
            </div>
            <a href="<?= base_url('products.php') ?>" class="btn btn-sm btn-obu-outline">
                <?= t('view_all') ?> &raquo;
            </a>
        </div>

        <div class="row g-3">
            <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
                <div class="col-6 col-md-3 col-lg-3">
                    <a href="<?= base_url('category.php?id=' . $cat['id']) ?>" class="category-card h-100">
                        <div class="category-icon">
                            <i class="bi <?= e($cat['icon'] ?? 'bi-tag') ?>"></i>
                        </div>
                        <h6 class="fw-bold mb-1" style="font-size: 0.95rem;"><?= e(localized($cat, 'name')) ?></h6>
                        <span class="small text-muted d-block text-truncate"><?= e($cat['description'] ?? '') ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 3. FEATURED PRODUCTS (IF ANY) -->
<?php if (!empty($featuredProducts)): ?>
<section class="py-5" style="background-color: var(--bg-surface-alt);">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="badge bg-warning text-dark mb-1 fw-bold">TRENDING</span>
                <h2 class="fw-bold mb-1"><?= t('featured_products') ?></h2>
                <p class="text-muted mb-0">Most viewed items by students this week</p>
            </div>
            <a href="<?= base_url('products.php?sort=views') ?>" class="btn btn-sm btn-obu-outline">
                <?= t('view_all') ?> &raquo;
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredProducts as $item): ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="obu-card">
                        <div class="obu-card-img-wrap">
                            <span class="obu-card-badge">
                                <?= render_condition_badge($item['condition']) ?>
                            </span>
                            <?php if (is_logged_in()): ?>
                                <button type="button" class="obu-card-fav-btn favorite-btn-action" data-product-id="<?= $item['id'] ?>" title="<?= t('add_to_favorites') ?>">
                                    <i class="bi bi-heart fs-6"></i>
                                </button>
                            <?php endif; ?>
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
                                <a href="<?= base_url('product-details.php?id=' . $item['id']) ?>">
                                    <?= e($item['title']) ?>
                                </a>
                            </h5>
                            <div class="obu-card-price"><?= format_currency($item['price']) ?></div>
                            <div class="obu-card-meta">
                                <span class="text-truncate"><i class="bi bi-geo-alt me-1"></i><?= e($item['location']) ?></span>
                                <span class="ms-auto"><i class="bi bi-eye me-1"></i><?= $item['views_count'] ?></span>
                            </div>
                            <a href="<?= base_url('product-details.php?id=' . $item['id']) ?>" class="btn btn-sm btn-obu w-100 mt-3">
                                <?= t('view_details') ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 4. LATEST PRODUCTS -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h2 class="fw-bold mb-1"><?= t('latest_products') ?></h2>
                <p class="text-muted mb-0"><?= t('latest_products_sub') ?></p>
            </div>
            <a href="<?= base_url('products.php') ?>" class="btn btn-sm btn-obu-outline">
                <?= t('view_all') ?> &raquo;
            </a>
        </div>

        <?php if (empty($latestProducts)): ?>
            <div class="text-center py-5 bg-surface rounded-4 border">
                <i class="bi bi-box-seam fs-1 text-muted mb-3 d-block"></i>
                <h5 class="fw-bold"><?= t('no_products_found') ?></h5>
                <p class="text-muted">Be the first student to publish a product listing on OBU Marketplace!</p>
                <a href="<?= is_logged_in() ? base_url('student/add-product.php') : base_url('login.php') ?>" class="btn btn-obu mt-2">
                    <i class="bi bi-plus-circle me-1"></i> <?= t('post_free_ad') ?>
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($latestProducts as $item): ?>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="obu-card">
                            <div class="obu-card-img-wrap">
                                <span class="obu-card-badge">
                                    <?= render_condition_badge($item['condition']) ?>
                                </span>
                                <?php if (is_logged_in()): ?>
                                    <button type="button" class="obu-card-fav-btn favorite-btn-action" data-product-id="<?= $item['id'] ?>" title="<?= t('add_to_favorites') ?>">
                                        <i class="bi bi-heart fs-6"></i>
                                    </button>
                                <?php endif; ?>
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
                                    <a href="<?= base_url('product-details.php?id=' . $item['id']) ?>">
                                        <?= e($item['title']) ?>
                                    </a>
                                </h5>
                                <div class="obu-card-price"><?= format_currency($item['price']) ?></div>
                                <div class="obu-card-meta">
                                    <span class="text-truncate" title="<?= e($item['seller_name']) ?>"><i class="bi bi-person me-1"></i><?= e($item['seller_name']) ?></span>
                                    <span class="ms-auto text-muted"><?= time_ago($item['created_at']) ?></span>
                                </div>
                                <a href="<?= base_url('product-details.php?id=' . $item['id']) ?>" class="btn btn-sm btn-obu w-100 mt-3">
                                    <?= t('view_details') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- 5. HOW IT WORKS -->
<section class="py-5" style="background-color: var(--bg-surface-alt);">
    <div class="container">
        <div class="text-center max-w-xl mx-auto mb-5">
            <span class="badge bg-primary text-white mb-2"><?= e(t('university_name')) ?> GUIDE</span>
            <h2 class="fw-bold"><?= t('how_works_title') ?></h2>
            <p class="text-muted">Simple, safe, and transparent 4-step campus trade cycle</p>
        </div>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="card h-100 border-0 p-4 rounded-4 shadow-sm text-center bg-surface">
                    <div class="stat-card-icon stat-icon-blue mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.8rem;">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h5 class="fw-bold mb-2"><?= t('how_works_step1_title') ?></h5>
                    <p class="small text-muted mb-0"><?= t('how_works_step1_desc') ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 p-4 rounded-4 shadow-sm text-center bg-surface">
                    <div class="stat-card-icon stat-icon-teal mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.8rem;">
                        <i class="bi bi-camera"></i>
                    </div>
                    <h5 class="fw-bold mb-2"><?= t('how_works_step2_title') ?></h5>
                    <p class="small text-muted mb-0"><?= t('how_works_step2_desc') ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 p-4 rounded-4 shadow-sm text-center bg-surface">
                    <div class="stat-card-icon stat-icon-amber mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.8rem;">
                        <i class="bi bi-chat-quote"></i>
                    </div>
                    <h5 class="fw-bold mb-2"><?= t('how_works_step3_title') ?></h5>
                    <p class="small text-muted mb-0"><?= t('how_works_step3_desc') ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 border-0 p-4 rounded-4 shadow-sm text-center bg-surface">
                    <div class="stat-card-icon stat-icon-purple mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.8rem;">
                        <i class="bi bi-hand-thumbs-up"></i>
                    </div>
                    <h5 class="fw-bold mb-2"><?= t('how_works_step4_title') ?></h5>
                    <p class="small text-muted mb-0"><?= t('how_works_step4_desc') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 6. BENEFITS OF USING MARKETPLACE -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-info text-dark mb-2 fw-bold">CAMPUS ADVANTAGES</span>
                <h2 class="fw-bold mb-4"><?= t('benefits_title') ?></h2>

                <div class="d-flex gap-3 mb-4">
                    <div class="stat-card-icon stat-icon-teal flex-shrink-0">
                        <i class="bi bi-piggy-bank"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1"><?= t('benefit_1_title') ?></h5>
                        <p class="text-muted mb-0"><?= t('benefit_1_desc') ?></p>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-4">
                    <div class="stat-card-icon stat-icon-blue flex-shrink-0">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1"><?= t('benefit_2_title') ?></h5>
                        <p class="text-muted mb-0"><?= t('benefit_2_desc') ?></p>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-4">
                    <div class="stat-card-icon stat-icon-amber flex-shrink-0">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1"><?= t('benefit_3_title') ?></h5>
                        <p class="text-muted mb-0"><?= t('benefit_3_desc') ?></p>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div class="stat-card-icon stat-icon-purple flex-shrink-0">
                        <i class="bi bi-translate"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1"><?= t('benefit_4_title') ?></h5>
                        <p class="text-muted mb-0"><?= t('benefit_4_desc') ?></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <!-- Safety Tips Card -->
                <div class="card border-0 p-4 p-md-5 rounded-4 shadow-sm" style="background: var(--bg-surface-alt); border-left: 6px solid var(--brand-secondary) !important;">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <i class="bi bi-exclamation-octagon-fill text-danger fs-1"></i>
                        <div>
                            <h4 class="fw-bold mb-0"><?= t('safety_tips_title') ?></h4>
                            <span class="small text-muted">Rules for safe in-person campus exchanges</span>
                        </div>
                    </div>

                    <ul class="list-unstyled mb-4 d-flex flex-direction-column gap-3">
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-1"></i>
                            <span><?= t('safety_tip_1') ?></span>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-1"></i>
                            <span><?= t('safety_tip_2') ?></span>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-1"></i>
                            <span><?= t('safety_tip_3') ?></span>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-1"></i>
                            <span><?= t('safety_tip_4') ?></span>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="bi bi-check-circle-fill text-success flex-shrink-0 mt-1"></i>
                            <span><?= t('safety_tip_5') ?></span>
                        </li>
                    </ul>

                    <a href="<?= base_url('safety-tips.php') ?>" class="btn btn-obu-outline w-100">
                        Read Full Safety Guidelines &raquo;
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

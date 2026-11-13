<?php
/**
 * OBU Student Marketplace - Product Catalog & Discovery (Module 5)
 */
require_once __DIR__ . '/config/app.php';

$pageTitle = t('nav_browse');
$categories = get_all_categories();

// Query Parameters
$search = clean_input($_GET['q'] ?? '');
$categoryFilter = !empty($_GET['category']) ? (int)$_GET['category'] : 0;
$conditionFilter = clean_input($_GET['condition'] ?? '');
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$sort = clean_input($_GET['sort'] ?? 'newest');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$products = [];
$totalProducts = 0;
$totalPages = 1;

if (db()) {
    try {
        // Build Dynamic Query
        $where = ["p.status = 'active'"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(p.title LIKE ? OR p.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        if ($categoryFilter > 0) {
            $where[] = "p.category_id = ?";
            $params[] = $categoryFilter;
        }

        if (!empty($conditionFilter) && array_key_exists($conditionFilter, PRODUCT_CONDITIONS)) {
            $where[] = "p.condition = ?";
            $params[] = $conditionFilter;
        }

        if ($minPrice !== null && $minPrice >= 0) {
            $where[] = "p.price >= ?";
            $params[] = $minPrice;
        }

        if ($maxPrice !== null && $maxPrice > 0) {
            $where[] = "p.price <= ?";
            $params[] = $maxPrice;
        }

        $whereClause = implode(" AND ", $where);

        // Count Total
        $countSql = "SELECT COUNT(*) FROM products p WHERE {$whereClause}";
        $countStmt = db()->prepare($countSql);
        $countStmt->execute($params);
        $totalProducts = (int)$countStmt->fetchColumn();
        $totalPages = max(1, ceil($totalProducts / $perPage));

        // Sorting
        $orderBy = "p.id DESC";
        switch ($sort) {
            case 'oldest':
                $orderBy = "p.id ASC";
                break;
            case 'price_low':
                $orderBy = "p.price ASC";
                break;
            case 'price_high':
                $orderBy = "p.price DESC";
                break;
            case 'views':
                $orderBy = "p.views_count DESC, p.id DESC";
                break;
            default:
                $orderBy = "p.id DESC";
                break;
        }

        // Fetch Products with Primary Image and Seller Info
        $sql = "
            SELECT p.*, c.name_en AS cat_en, c.name_am AS cat_am, c.name_om AS cat_om,
                   u.full_name AS seller_name, u.department AS seller_dept,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC, id ASC LIMIT 1) AS primary_image
            FROM products p
            JOIN categories c ON p.category_id = c.id
            JOIN users u ON p.user_id = u.id
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

    } catch (Exception $e) {
        $products = [];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="py-4" style="background-color: var(--bg-surface-alt); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="fw-bold mb-1"><?= t('nav_browse') ?></h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb small mb-0">
                        <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>"><?= t('nav_home') ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= t('products') ?></li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <span class="text-muted small">Showing <?= count($products) ?> of <?= $totalProducts ?> available listings</span>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row g-4">
        <!-- Filter Sidebar -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-surface sticky-top" style="top: 80px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-funnel me-1 text-primary"></i> <?= t('filter_results') ?></h5>
                    <a href="<?= base_url('products.php') ?>" class="small text-danger text-decoration-none"><?= t('reset_filters') ?></a>
                </div>

                <form action="<?= base_url('products.php') ?>" method="GET">
                    <!-- Keyword search -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Keyword</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="q" class="form-control" placeholder="Search title..." value="<?= e($search) ?>">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= t('category') ?></label>
                        <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value=""><?= t('all_categories') ?></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryFilter === (int)$cat['id'] ? 'selected' : '' ?>>
                                    <?= e(localized($cat, 'name')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Condition Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= t('condition') ?></label>
                        <select name="condition" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value=""><?= t('all_conditions') ?></option>
                            <?php foreach (PRODUCT_CONDITIONS as $cKey => $cLabels): ?>
                                <option value="<?= $cKey ?>" <?= $conditionFilter === $cKey ? 'selected' : '' ?>>
                                    <?= e($cLabels[current_lang()] ?? $cKey) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold"><?= t('price') ?> (ETB)</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min" value="<?= $minPrice !== null ? e($minPrice) : '' ?>">
                            </div>
                            <div class="col-6">
                                <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max" value="<?= $maxPrice !== null ? e($maxPrice) : '' ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Sort -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold"><?= t('sort_by') ?></label>
                        <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>><?= t('sort_newest') ?></option>
                            <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>><?= t('sort_price_low') ?></option>
                            <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>><?= t('sort_price_high') ?></option>
                            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>><?= t('sort_oldest') ?></option>
                            <option value="views" <?= $sort === 'views' ? 'selected' : '' ?>>Most Viewed</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-obu btn-sm w-100">
                        <i class="bi bi-check2"></i> Apply Filters
                    </button>
                </form>
            </div>
        </div>

        <!-- Products Grid Area -->
        <div class="col-lg-9">
            <?php if (empty($products)): ?>
                <div class="card border-0 rounded-4 shadow-sm p-5 text-center bg-surface">
                    <i class="bi bi-search fs-1 text-muted mb-3"></i>
                    <h4 class="fw-bold mb-2"><?= t('no_products_found') ?></h4>
                    <p class="text-muted mb-4">Try clearing some filters or searching with different keywords.</p>
                    <div>
                        <a href="<?= base_url('products.php') ?>" class="btn btn-obu-outline">
                            <i class="bi bi-arrow-clockwise me-1"></i> Clear All Filters
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $item): ?>
                        <div class="col-12 col-sm-6 col-md-4">
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
                                        <span class="text-truncate" title="<?= e($item['location']) ?>"><i class="bi bi-geo-alt me-1"></i><?= e($item['location']) ?></span>
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

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-5" aria-label="Product pagination">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= base_url('products.php?' . http_build_query(array_merge($_GET, ['page' => $page - 1]))) ?>" tabindex="-1">&laquo;</a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= base_url('products.php?' . http_build_query(array_merge($_GET, ['page' => $p]))) ?>">
                                        <?= $p ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= base_url('products.php?' . http_build_query(array_merge($_GET, ['page' => $page + 1]))) ?>">&raquo;</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

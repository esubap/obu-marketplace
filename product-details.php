<?php
/**
 * OBU Student Marketplace - Product Details (Module 5)
 */
require_once __DIR__ . '/config/app.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    set_flash('danger', t('error_404_desc'));
    header('Location: ' . base_url('products.php'));
    exit;
}

$product = null;
$productImages = [];
$relatedProducts = [];
$isFavorited = false;
$currentUser = current_user();

if (db()) {
    try {
        // Fetch product with category and seller details
        $stmt = db()->prepare("
            SELECT p.*, c.name_en AS cat_en, c.name_am AS cat_am, c.name_om AS cat_om, c.id AS cat_id,
                   u.id AS seller_id, u.full_name AS seller_name, u.email AS seller_email,
                   u.phone AS seller_phone, u.department AS seller_dept, u.year_of_study AS seller_year,
                   u.created_at AS seller_joined, u.profile_image AS seller_image
            FROM products p
            JOIN categories c ON p.category_id = c.id
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if ($product) {
            // Increment view count (if not the seller themselves)
            if (!$currentUser || $currentUser['id'] !== (int)$product['user_id']) {
                $stmtViews = db()->prepare("UPDATE products SET views_count = views_count + 1 WHERE id = ?");
                $stmtViews->execute([$productId]);
                $product['views_count']++;
            }

            // Fetch all product images
            $stmtImgs = db()->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
            $stmtImgs->execute([$productId]);
            $productImages = $stmtImgs->fetchAll();

            // Check if user has favorited this item
            if ($currentUser) {
                $stmtFav = db()->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
                $stmtFav->execute([$currentUser['id'], $productId]);
                $isFavorited = (bool)$stmtFav->fetch();
            }

            // Related Products
            $stmtRel = db()->prepare("
                SELECT p.*, 
                       (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) AS primary_image
                FROM products p
                WHERE p.category_id = ? AND p.id != ? AND p.status = 'active'
                ORDER BY p.id DESC LIMIT 4
            ");
            $stmtRel->execute([$product['category_id'], $productId]);
            $relatedProducts = $stmtRel->fetchAll();
        }
    } catch (Exception $e) {
        $product = null;
    }
}

if (!$product) {
    http_response_code(404);
    $pageTitle = t('error_404_title');
    require_once __DIR__ . '/includes/header.php';
    echo '
    <div class="container py-5 text-center">
        <i class="bi bi-question-circle fs-1 text-danger mb-3 d-block"></i>
        <h2 class="fw-bold">' . t('error_404_title') . '</h2>
        <p class="text-muted">' . t('error_404_desc') . '</p>
        <a href="' . base_url('products.php') . '" class="btn btn-obu">' . t('go_home') . '</a>
    </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$isOwner = $currentUser && ((int)$currentUser['id'] === (int)$product['user_id']);
$pageTitle = $product['title'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="py-3" style="background-color: var(--bg-surface-alt); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>"><?= t('nav_home') ?></a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('products.php') ?>"><?= t('products') ?></a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('category.php?id=' . $product['cat_id']) ?>"><?= e(localized(['name_en' => $product['cat_en'], 'name_am' => $product['cat_am'], 'name_om' => $product['cat_om']], 'name')) ?></a></li>
                <li class="breadcrumb-item active text-truncate" style="max-width: 250px;"><?= e($product['title']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Left: Image Gallery -->
        <div class="col-lg-6">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-surface">
                <?php 
                    $mainImage = !empty($productImages[0]['image_path']) 
                        ? (str_starts_with($productImages[0]['image_path'], 'assets/') ? asset_url(substr($productImages[0]['image_path'], 7)) : upload_url('products/' . $productImages[0]['image_path']))
                        : asset_url('images/placeholders/default-product.svg');
                ?>
                <div class="rounded-3 overflow-hidden text-center bg-surface-alt mb-3" style="height: 380px; display: flex; align-items: center; justify-content: center;">
                    <img id="mainProductImage" src="<?= $mainImage ?>" alt="<?= e($product['title']) ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                </div>

                <!-- Thumbnails row -->
                <?php if (count($productImages) > 1): ?>
                    <div class="row g-2">
                        <?php foreach ($productImages as $pImg): ?>
                            <?php 
                                $tSrc = str_starts_with($pImg['image_path'], 'assets/') 
                                    ? asset_url(substr($pImg['image_path'], 7)) 
                                    : upload_url('products/' . $pImg['image_path']);
                            ?>
                            <div class="col-3">
                                <div class="border rounded-2 p-1 cursor-pointer bg-surface-alt" onclick="document.getElementById('mainProductImage').src = '<?= $tSrc ?>'">
                                    <img src="<?= $tSrc ?>" class="w-100 rounded" style="height: 65px; object-fit: cover;" alt="Thumbnail">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Details & Buying Actions -->
        <div class="col-lg-6">
            <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-surface h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-secondary-subtle text-secondary fs-6 px-3 py-1.5 rounded-pill">
                        <i class="bi bi-tag-fill me-1"></i>
                        <?= e(localized(['name_en' => $product['cat_en'], 'name_am' => $product['cat_am'], 'name_om' => $product['cat_om']], 'name')) ?>
                    </span>
                    <div class="d-flex align-items-center gap-2">
                        <?= render_condition_badge($product['condition']) ?>
                        <?= render_status_badge($product['status']) ?>
                    </div>
                </div>

                <h2 class="fw-bold mb-2"><?= e($product['title']) ?></h2>

                <div class="d-flex align-items-baseline gap-3 my-3">
                    <span class="display-6 fw-bold text-primary"><?= format_currency($product['price']) ?></span>
                </div>

                <div class="d-flex flex-wrap gap-4 text-muted small py-3 my-2 border-top border-bottom">
                    <span><i class="bi bi-calendar-event me-1"></i> <?= date('M d, Y', strtotime($product['created_at'])) ?></span>
                    <span><i class="bi bi-eye me-1"></i> <?= $product['views_count'] ?> views</span>
                    <span><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= e($product['location']) ?></span>
                </div>

                <!-- Description (Original language as written by seller) -->
                <div class="my-3">
                    <h5 class="fw-bold mb-2"><?= t('description') ?></h5>
                    <div class="text-secondary" style="white-space: pre-line; line-height: 1.7;">
                        <?= nl2br(e($product['description'])) ?>
                    </div>
                </div>

                <!-- Meeting Location Notice -->
                <div class="p-3 rounded-3 mb-4 bg-surface-alt border">
                    <div class="d-flex gap-2">
                        <i class="bi bi-pin-map-fill text-primary fs-5"></i>
                        <div>
                            <strong>Campus Meeting Point:</strong>
                            <p class="small text-muted mb-0"><?= e($product['location']) ?> (<?= e($product['university']) ?>)</p>
                        </div>
                    </div>
                </div>

                <!-- Admin Moderation Action Bar (If Logged in as Admin) -->
                <?php if ($currentUser && $currentUser['role'] === ROLE_ADMIN): ?>
                    <div class="alert alert-danger p-3 mb-3 border-danger rounded-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <strong class="text-danger"><i class="bi bi-shield-fill-exclamation me-1"></i> Admin Moderation Controls</strong>
                                <div class="small text-muted">You have authority to immediately remove or hide inappropriate items.</div>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if ($product['status'] === 'active'): ?>
                                    <a href="<?= base_url('admin/products.php?action=hide&id=' . $product['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-warning fw-semibold">
                                        <i class="bi bi-eye-slash me-1"></i> Hide
                                    </a>
                                <?php else: ?>
                                    <a href="<?= base_url('admin/products.php?action=approve&id=' . $product['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-success fw-semibold">
                                        <i class="bi bi-check-lg me-1"></i> Approve
                                    </a>
                                <?php endif; ?>
                                <a href="<?= base_url('admin/products.php?action=delete&id=' . $product['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-danger fw-semibold" onclick="return confirm('Permanently remove this listing from the university marketplace?');">
                                    <i class="bi bi-trash-fill me-1"></i> Remove / Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Actions for Buyer vs Owner -->
                <div class="d-grid gap-2 mt-auto">
                    <?php if ($isOwner): ?>
                        <!-- Owner Controls -->
                        <div class="alert alert-info py-2 small mb-2 text-center">
                            <i class="bi bi-info-circle me-1"></i> You published this listing. You can edit, mark sold, or delete it.
                        </div>
                        <div class="row g-2">
                            <div class="col-4">
                                <a href="<?= base_url('student/edit-product.php?id=' . $product['id']) ?>" class="btn btn-warning w-100 fw-bold">
                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                </a>
                            </div>
                            <div class="col-4">
                                <?php if ($product['status'] === 'active'): ?>
                                    <a href="<?= base_url('actions/product-action.php?action=mark_sold&id=' . $product['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-secondary w-100 fw-bold" onclick="return confirm('Mark this product as sold?');">
                                        <i class="bi bi-check2-circle me-1"></i> Sold
                                    </a>
                                <?php else: ?>
                                    <a href="<?= base_url('actions/product-action.php?action=mark_active&id=' . $product['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-success w-100 fw-bold">
                                        <i class="bi bi-arrow-repeat me-1"></i> Available
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="col-4">
                                <a href="<?= base_url('student/delete-product.php?id=' . $product['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-outline-danger w-100 fw-bold" onclick="return confirm('<?= t('confirm_delete_product') ?>');">
                                    <i class="bi bi-trash-fill me-1"></i> Delete
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Buyer Controls -->
                        <?php if ($product['status'] === 'sold'): ?>
                            <div class="alert alert-secondary text-center fw-bold py-3">
                                <i class="bi bi-x-circle me-1"></i> This item has already been sold.
                            </div>
                        <?php else: ?>
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <?php if (is_logged_in()): ?>
                                        <button type="button" class="btn btn-obu w-100 py-2.5" data-bs-toggle="modal" data-bs-target="#purchaseRequestModal">
                                            <i class="bi bi-bag-check-fill me-1"></i> <?= t('send_purchase_request') ?>
                                        </button>
                                    <?php else: ?>
                                        <a href="<?= base_url('login.php') ?>" class="btn btn-obu w-100 py-2.5">
                                            <i class="bi bi-bag-check-fill me-1"></i> <?= t('send_purchase_request') ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12 col-md-6">
                                    <?php if (is_logged_in()): ?>
                                        <a href="<?= base_url('actions/message-action.php?action=start&product_id=' . $product['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-obu-outline w-100 py-2.5">
                                            <i class="bi bi-chat-dots-fill me-1"></i> <?= t('contact_seller') ?>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= base_url('login.php') ?>" class="btn btn-obu-outline w-100 py-2.5">
                                            <i class="bi bi-chat-dots-fill me-1"></i> <?= t('contact_seller') ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Secondary Actions: Favorite & Report -->
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <?php if (is_logged_in()): ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary favorite-btn-action" data-product-id="<?= $product['id'] ?>">
                                    <i class="bi <?= $isFavorited ? 'bi-heart-fill text-danger' : 'bi-heart' ?> me-1"></i>
                                    <span><?= $isFavorited ? t('remove_from_favorites') : t('add_to_favorites') ?></span>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reportProductModal">
                                    <i class="bi bi-flag-fill me-1"></i> <?= t('report_listing') ?>
                                </button>
                            <?php else: ?>
                                <a href="<?= base_url('login.php') ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-heart me-1"></i> <?= t('add_to_favorites') ?>
                                </a>
                                <a href="<?= base_url('login.php') ?>" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-flag me-1"></i> <?= t('report_listing') ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Seller Profile Card -->
                <div class="mt-4 pt-3 border-top">
                    <h6 class="small fw-bold text-muted text-uppercase mb-3"><?= t('seller_info') ?></h6>
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-avatar-circle" style="width: 46px; height: 46px; font-size: 1.1rem;">
                            <?= strtoupper(substr($product['seller_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold"><?= e($product['seller_name']) ?></h6>
                            <span class="small text-muted d-block"><?= e($product['seller_dept']) ?> &bull; <?= e($product['seller_year']) ?></span>
                            <span class="small text-muted" style="font-size: 0.75rem;"><i class="bi bi-calendar3 me-1"></i> Member since <?= date('M Y', strtotime($product['seller_joined'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="mt-5 pt-4">
            <h4 class="fw-bold mb-4">Related Listings in This Category</h4>
            <div class="row g-4">
                <?php foreach ($relatedProducts as $rel): ?>
                    <div class="col-6 col-md-3">
                        <div class="obu-card">
                            <div class="obu-card-img-wrap" style="height: 150px;">
                                <a href="<?= base_url('product-details.php?id=' . $rel['id']) ?>" class="w-100 h-100">
                                    <?php 
                                        $rImg = !empty($rel['primary_image']) 
                                            ? (str_starts_with($rel['primary_image'], 'assets/') ? asset_url(substr($rel['primary_image'], 7)) : upload_url('products/' . $rel['primary_image']))
                                            : asset_url('images/placeholders/default-product.svg');
                                    ?>
                                    <img src="<?= $rImg ?>" alt="<?= e($rel['title']) ?>">
                                </a>
                            </div>
                            <div class="obu-card-body p-3">
                                <h6 class="obu-card-title mb-1" style="font-size: 0.92rem;">
                                    <a href="<?= base_url('product-details.php?id=' . $rel['id']) ?>"><?= e($rel['title']) ?></a>
                                </h6>
                                <div class="obu-card-price" style="font-size: 1.05rem;"><?= format_currency($rel['price']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Purchase Request Modal -->
<?php if (is_logged_in() && !$isOwner): ?>
<div class="modal fade" id="purchaseRequestModal" tabindex="-1" aria-labelledby="purchaseRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="purchaseRequestModalLabel">
                    <i class="bi bi-bag-check text-primary me-2"></i> <?= t('send_request_modal_title') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('actions/purchase-action.php') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_request">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                <div class="modal-body p-4">
                    <div class="d-flex gap-3 align-items-center p-3 rounded-3 bg-surface-alt mb-3">
                        <img src="<?= $mainImage ?>" alt="Product" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                        <div>
                            <strong class="d-block text-truncate" style="max-width: 320px;"><?= e($product['title']) ?></strong>
                            <span class="text-primary fw-bold"><?= format_currency($product['price']) ?></span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small"><?= t('request_message_label') ?></label>
                        <textarea name="message" class="form-control" rows="3" placeholder="<?= t('request_message_placeholder') ?>"></textarea>
                    </div>

                    <div class="alert alert-warning py-2 small mb-0">
                        <i class="bi bi-shield-exclamation me-1"></i>
                        No online payments are collected. You will meet the seller in person on campus to inspect the item before paying cash.
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-obu"><?= t('confirm_send_request') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Report Product Modal -->
<div class="modal fade" id="reportProductModal" tabindex="-1" aria-labelledby="reportProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-danger" id="reportProductModalLabel">
                    <i class="bi bi-flag-fill me-2"></i> <?= t('report_modal_title') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= base_url('actions/report-action.php') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="reported_user_id" value="<?= $product['seller_id'] ?>">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small"><?= t('report_reason') ?> *</label>
                        <select name="reason" class="form-select" required>
                            <option value=""><?= t('report_reason') ?>...</option>
                            <option value="Prohibited Item"><?= t('reason_prohibited') ?></option>
                            <option value="Suspected Scam"><?= t('reason_scam') ?></option>
                            <option value="Counterfeit / Misleading"><?= t('reason_counterfeit') ?></option>
                            <option value="Inappropriate Content"><?= t('reason_inappropriate') ?></option>
                            <option value="Wrong Category / Spam"><?= t('reason_wrong_category') ?></option>
                            <option value="Other"><?= t('reason_other') ?></option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small"><?= t('report_details') ?> *</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="<?= t('report_details_placeholder') ?>" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><?= t('submit_report') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * OBU Student Marketplace - Student Dashboard (Module 3)
 */
require_once __DIR__ . '/../config/app.php';
require_student();

$pageTitle = t('dashboard_overview');
$extraCss = ['dashboard.css'];
$user = current_user();
$userId = (int)$user['id'];

// Dashboard Statistics
$stats = [
    'total_products'    => 0,
    'active_listings'   => 0,
    'pending_requests'  => 0,
    'completed_sales'   => 0,
    'outgoing_requests' => 0,
    'saved_favorites'   => 0,
    'unread_messages'   => 0,
    'unread_notifs'     => 0
];
$myRecentProducts = [];
$recentRequests = [];

if (db()) {
    try {
        // Total products (as seller)
        $stmt = db()->prepare("SELECT COUNT(*) FROM products WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['total_products'] = (int)$stmt->fetchColumn();

        // Active listings
        $stmt = db()->prepare("SELECT COUNT(*) FROM products WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$userId]);
        $stats['active_listings'] = (int)$stmt->fetchColumn();

        // Pending purchase requests (incoming offers to seller)
        $stmt = db()->prepare("SELECT COUNT(*) FROM purchase_requests WHERE seller_id = ? AND status = 'pending'");
        $stmt->execute([$userId]);
        $stats['pending_requests'] = (int)$stmt->fetchColumn();

        // Completed transactions (sold by seller or bought by buyer)
        $stmt = db()->prepare("SELECT COUNT(*) FROM purchase_requests WHERE (seller_id = ? OR buyer_id = ?) AND status = 'completed'");
        $stmt->execute([$userId, $userId]);
        $stats['completed_sales'] = (int)$stmt->fetchColumn();

        // Outgoing purchase requests (offers sent as buyer)
        $stmt = db()->prepare("SELECT COUNT(*) FROM purchase_requests WHERE buyer_id = ?");
        $stmt->execute([$userId]);
        $stats['outgoing_requests'] = (int)$stmt->fetchColumn();

        // Saved favorites (items saved as buyer)
        $stmt = db()->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['saved_favorites'] = (int)$stmt->fetchColumn();

        // Unread messages
        $stats['unread_messages'] = get_unread_message_count($userId);

        // Unread notifications
        $stats['unread_notifs'] = get_unread_notification_count($userId);

        // Recent listings by user
        $stmt = db()->prepare("
            SELECT p.*, c.name_en AS cat_en, c.name_am AS cat_am, c.name_om AS cat_om,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) AS primary_image
            FROM products p
            JOIN categories c ON p.category_id = c.id
            WHERE p.user_id = ?
            ORDER BY p.id DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $myRecentProducts = $stmt->fetchAll();

        // Recent purchase requests received
        $stmt = db()->prepare("
            SELECT pr.*, p.title AS product_title, p.price AS product_price, u.full_name AS buyer_name, u.phone AS buyer_phone
            FROM purchase_requests pr
            JOIN products p ON pr.product_id = p.id
            JOIN users u ON pr.buyer_id = u.id
            WHERE pr.seller_id = ?
            ORDER BY pr.id DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $recentRequests = $stmt->fetchAll();

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

        <!-- Main Dashboard Content -->
        <div class="col-lg-9">
            <!-- 1. Prominent 3-Language Switcher (Instant 1-Click Switch: English, Amharic, Afaan Oromoo) -->
            <div class="card border-0 rounded-4 shadow-sm p-3 mb-4 bg-surface" style="border-top: 4px solid var(--brand-primary) !important;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi bi-translate fs-5"></i>
                        </div>
                        <div>
                            <span class="fw-bold d-block lh-1 text-primary"><?= t('language') ?> / ቋንቋ / Afaan</span>
                            <small class="text-muted">Choose your language with 1 click</small>
                        </div>
                    </div>
                    <div class="btn-group shadow-sm" role="group" aria-label="Dashboard Language Switcher">
                        <a href="<?= get_lang_url('en') ?>" class="btn btn-sm <?= current_lang() === 'en' ? 'btn-primary active fw-bold' : 'btn-outline-secondary' ?> px-3 py-1.5">
                            🇬🇧 English <?= current_lang() === 'en' ? '<i class="bi bi-check2"></i>' : '' ?>
                        </a>
                        <a href="<?= get_lang_url('am') ?>" class="btn btn-sm <?= current_lang() === 'am' ? 'btn-primary active fw-bold' : 'btn-outline-secondary' ?> px-3 py-1.5">
                            🇪🇹 አማርኛ <?= current_lang() === 'am' ? '<i class="bi bi-check2"></i>' : '' ?>
                        </a>
                        <a href="<?= get_lang_url('om') ?>" class="btn btn-sm <?= current_lang() === 'om' ? 'btn-primary active fw-bold' : 'btn-outline-secondary' ?> px-3 py-1.5">
                            🌳 Afaan Oromoo <?= current_lang() === 'om' ? '<i class="bi bi-check2"></i>' : '' ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- 2. Welcome Header Banner with Unified Buy & Sell Role Badge -->
            <div class="card border-0 rounded-4 shadow-sm p-4 mb-4 bg-surface" style="border-left: 6px solid var(--brand-primary) !important;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold">
                                <i class="bi bi-patch-check-fill me-1"></i> VERIFIED STUDENT ACCOUNT
                            </span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold">
                                <i class="bi bi-arrow-left-right me-1"></i> ALL-IN-ONE BUY & SELL
                            </span>
                        </div>
                        <h3 class="fw-bold mb-1"><?= str_replace(':name', e($user['full_name']), t('welcome_message')) ?></h3>
                        <p class="text-secondary mb-0">
                            <?= e($user['department']) ?> &bull; <?= e($user['year_of_study']) ?> &bull; Student ID: <strong><?= e($user['student_id']) ?></strong>
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= base_url('student/add-product.php') ?>" class="btn btn-obu">
                            <i class="bi bi-plus-circle-fill me-1"></i> <?= t('nav_add_product') ?>
                        </a>
                        <a href="<?= base_url('products.php') ?>" class="btn btn-obu-outline">
                            <i class="bi bi-shop me-1"></i> <?= t('nav_browse') ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- 3. Dual Hubs: SELLER HUB & BUYER HUB -->
            <div class="row g-4 mb-4">
                <!-- SELLING HUB -->
                <div class="col-lg-6">
                    <div class="card border-0 rounded-4 shadow-sm h-100 bg-surface p-4" style="border-top: 4px solid #0D9488 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background-color: rgba(13, 148, 136, 0.12); color: #0D9488;">
                                    <i class="bi bi-shop fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0">Selling Hub (እቃ መሸጫ / Gurgurtaa)</h5>
                                    <small class="text-muted">Upload and manage items you want to sell</small>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <a href="<?= base_url('student/my-products.php') ?>" class="text-decoration-none">
                                    <div class="p-3 rounded-3 bg-light text-center border">
                                        <div class="fs-4 fw-bold text-dark"><?= $stats['total_products'] ?></div>
                                        <div class="small text-muted"><?= t('stat_total_products') ?></div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= base_url('student/my-products.php') ?>" class="text-decoration-none">
                                    <div class="p-3 rounded-3 bg-light text-center border">
                                        <div class="fs-4 fw-bold text-success"><?= $stats['active_listings'] ?></div>
                                        <div class="small text-muted"><?= t('stat_active_listings') ?></div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= base_url('student/purchase-requests.php?tab=incoming') ?>" class="text-decoration-none">
                                    <div class="p-3 rounded-3 bg-light text-center border">
                                        <div class="fs-4 fw-bold text-warning"><?= $stats['pending_requests'] ?></div>
                                        <div class="small text-muted">Incoming Buyer Offers</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= base_url('student/purchase-requests.php?tab=incoming') ?>" class="text-decoration-none">
                                    <div class="p-3 rounded-3 bg-light text-center border">
                                        <div class="fs-4 fw-bold text-primary"><?= $stats['completed_sales'] ?></div>
                                        <div class="small text-muted"><?= t('stat_completed_transactions') ?></div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-auto">
                            <a href="<?= base_url('student/add-product.php') ?>" class="btn btn-sm btn-primary w-100 py-2 fw-semibold">
                                <i class="bi bi-plus-circle me-1"></i> Post Item to Sell
                            </a>
                            <a href="<?= base_url('student/my-products.php') ?>" class="btn btn-sm btn-outline-secondary w-100 py-2 fw-semibold">
                                <i class="bi bi-box-seam me-1"></i> My Listings
                            </a>
                        </div>
                    </div>
                </div>

                <!-- BUYING HUB -->
                <div class="col-lg-6">
                    <div class="card border-0 rounded-4 shadow-sm h-100 bg-surface p-4" style="border-top: 4px solid #0284C7 !important;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                    <i class="bi bi-bag-heart fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0">Buying Hub (እቃ መግዣ / Bittaa)</h5>
                                    <small class="text-muted">Browse items, sent offers & saved favorites</small>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <a href="<?= base_url('student/favorites.php') ?>" class="text-decoration-none">
                                    <div class="p-3 rounded-3 bg-light text-center border">
                                        <div class="fs-4 fw-bold text-danger"><?= $stats['saved_favorites'] ?></div>
                                        <div class="small text-muted"><i class="bi bi-heart-fill text-danger me-1"></i> Saved Favorites</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= base_url('student/purchase-requests.php?tab=outgoing') ?>" class="text-decoration-none">
                                    <div class="p-3 rounded-3 bg-light text-center border">
                                        <div class="fs-4 fw-bold text-info"><?= $stats['outgoing_requests'] ?></div>
                                        <div class="small text-muted"><i class="bi bi-send me-1"></i> Sent Purchase Offers</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= base_url('student/messages.php') ?>" class="text-decoration-none">
                                    <div class="p-3 rounded-3 bg-light text-center border">
                                        <div class="fs-4 fw-bold text-primary"><?= $stats['unread_messages'] ?></div>
                                        <div class="small text-muted"><i class="bi bi-chat-dots me-1"></i> Unread Chats</div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= base_url('student/notifications.php') ?>" class="text-decoration-none">
                                    <div class="p-3 rounded-3 bg-light text-center border">
                                        <div class="fs-4 fw-bold text-warning"><?= $stats['unread_notifs'] ?></div>
                                        <div class="small text-muted"><i class="bi bi-bell me-1"></i> Alerts</div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-auto">
                            <a href="<?= base_url('products.php') ?>" class="btn btn-sm btn-outline-primary w-100 py-2 fw-semibold">
                                <i class="bi bi-search me-1"></i> Browse Marketplace
                            </a>
                            <a href="<?= base_url('student/purchase-requests.php?tab=outgoing') ?>" class="btn btn-sm btn-outline-secondary w-100 py-2 fw-semibold">
                                <i class="bi bi-bag-check me-1"></i> My Sent Offers
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Incoming Purchase Requests -->
            <div class="card border-0 rounded-4 shadow-sm p-4 mb-4 bg-surface">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-bag-check me-2 text-primary"></i> <?= t('recent_requests') ?></h5>
                    <a href="<?= base_url('student/purchase-requests.php') ?>" class="btn btn-sm btn-obu-outline">
                        <?= t('view_all') ?> &raquo;
                    </a>
                </div>

                <?php if (empty($recentRequests)): ?>
                    <p class="text-muted small mb-0 py-2">No incoming purchase requests at this moment.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Buyer</th>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRequests as $req): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($req['buyer_name']) ?></strong>
                                        </td>
                                        <td class="text-truncate" style="max-width: 180px;">
                                            <a href="<?= base_url('product-details.php?id=' . $req['product_id']) ?>">
                                                <?= e($req['product_title']) ?>
                                            </a>
                                        </td>
                                        <td class="fw-bold text-primary"><?= format_currency($req['product_price']) ?></td>
                                        <td><?= render_request_status_badge($req['status']) ?></td>
                                        <td class="small text-muted"><?= time_ago($req['created_at']) ?></td>
                                        <td>
                                            <a href="<?= base_url('student/purchase-requests.php') ?>" class="btn btn-sm btn-outline-primary py-1 px-2">
                                                Review
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- My Recent Listings -->
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-primary"></i> <?= t('my_listings') ?></h5>
                    <a href="<?= base_url('student/my-products.php') ?>" class="btn btn-sm btn-obu-outline">
                        <?= t('view_all') ?> &raquo;
                    </a>
                </div>

                <?php if (empty($myRecentProducts)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-box-seam fs-2 text-muted mb-2 d-block"></i>
                        <p class="text-muted small mb-2">You haven't listed any items for sale yet.</p>
                        <a href="<?= base_url('student/add-product.php') ?>" class="btn btn-sm btn-obu">
                            <i class="bi bi-plus-circle me-1"></i> <?= t('nav_add_product') ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Condition</th>
                                    <th>Status</th>
                                    <th>Views</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myRecentProducts as $p): ?>
                                    <?php 
                                        $thumb = !empty($p['primary_image']) 
                                            ? (str_starts_with($p['primary_image'], 'assets/') ? asset_url(substr($p['primary_image'], 7)) : upload_url('products/' . $p['primary_image']))
                                            : asset_url('images/placeholders/default-product.svg');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?= $thumb ?>" alt="Thumb" class="table-thumb">
                                                <div>
                                                    <a href="<?= base_url('product-details.php?id=' . $p['id']) ?>" class="fw-bold d-block text-truncate" style="max-width: 170px;">
                                                        <?= e($p['title']) ?>
                                                    </a>
                                                    <span class="small text-muted"><?= time_ago($p['created_at']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= e(localized(['name_en' => $p['cat_en'], 'name_am' => $p['cat_am'], 'name_om' => $p['cat_om']], 'name')) ?></td>
                                        <td class="fw-bold text-primary"><?= format_currency($p['price']) ?></td>
                                        <td><?= render_condition_badge($p['condition']) ?></td>
                                        <td><?= render_status_badge($p['status']) ?></td>
                                        <td><i class="bi bi-eye text-muted me-1"></i><?= $p['views_count'] ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= base_url('student/edit-product.php?id=' . $p['id']) ?>" class="btn btn-outline-secondary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="<?= base_url('student/delete-product.php?id=' . $p['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('<?= t('confirm_delete_product') ?>');">
                                                    <i class="bi bi-trash"></i>
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

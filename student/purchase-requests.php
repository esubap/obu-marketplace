<?php
/**
 * OBU Student Marketplace - Purchase Requests Management (Module 7)
 */
require_once __DIR__ . '/../config/app.php';
require_student();

$pageTitle = t('purchase_requests_title');
$extraCss = ['dashboard.css'];
$user = current_user();
$userId = (int)$user['id'];

$activeTab = clean_input($_GET['tab'] ?? 'incoming');
$incomingRequests = [];
$outgoingRequests = [];

if (db()) {
    try {
        // Incoming requests (I am the seller)
        $stmtIn = db()->prepare("
            SELECT pr.*, p.title AS product_title, p.price AS product_price, p.status AS product_status,
                   u.full_name AS buyer_name, u.student_id AS buyer_student_id, u.department AS buyer_dept,
                   u.phone AS buyer_phone,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) AS product_image
            FROM purchase_requests pr
            JOIN products p ON pr.product_id = p.id
            JOIN users u ON pr.buyer_id = u.id
            WHERE pr.seller_id = ?
            ORDER BY pr.id DESC
        ");
        $stmtIn->execute([$userId]);
        $incomingRequests = $stmtIn->fetchAll();

        // Outgoing requests (I am the buyer)
        $stmtOut = db()->prepare("
            SELECT pr.*, p.title AS product_title, p.price AS product_price, p.status AS product_status,
                   u.full_name AS seller_name, u.department AS seller_dept, u.phone AS seller_phone,
                   (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY is_primary DESC LIMIT 1) AS product_image
            FROM purchase_requests pr
            JOIN products p ON pr.product_id = p.id
            JOIN users u ON pr.seller_id = u.id
            WHERE pr.buyer_id = ?
            ORDER BY pr.id DESC
        ");
        $stmtOut->execute([$userId]);
        $outgoingRequests = $stmtOut->fetchAll();

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
                        <h3 class="fw-bold mb-1"><?= t('purchase_requests_title') ?></h3>
                        <p class="text-secondary mb-0">Track incoming offers from student buyers and manage your purchases</p>
                    </div>
                </div>

                <!-- Tabs: Incoming vs Outgoing -->
                <ul class="nav nav-pills mb-4" id="requestTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $activeTab === 'incoming' ? 'active' : '' ?>" id="incoming-tab" data-bs-toggle="pill" data-bs-target="#incomingTab" type="button" role="tab">
                            <i class="bi bi-box-arrow-in-down-left me-1"></i>
                            <?= t('incoming_requests') ?>
                            <span class="badge bg-danger ms-2"><?= count($incomingRequests) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $activeTab === 'outgoing' ? 'active' : '' ?>" id="outgoing-tab" data-bs-toggle="pill" data-bs-target="#outgoingTab" type="button" role="tab">
                            <i class="bi bi-send me-1"></i>
                            <?= t('outgoing_requests') ?>
                            <span class="badge bg-secondary ms-2"><?= count($outgoingRequests) ?></span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="requestTabsContent">
                    <!-- INCOMING REQUESTS (From Buyers) -->
                    <div class="tab-pane fade <?= $activeTab === 'incoming' ? 'show active' : '' ?>" id="incomingTab" role="tabpanel">
                        <?php if (empty($incomingRequests)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                                <h5 class="fw-bold"><?= t('no_incoming_requests') ?></h5>
                                <p class="text-muted">When a student sends an offer to buy one of your items, it will appear here.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-custom align-middle">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Buyer</th>
                                            <th>Offer Note</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($incomingRequests as $req): ?>
                                            <?php 
                                                $img = !empty($req['product_image']) 
                                                    ? (str_starts_with($req['product_image'], 'assets/') ? asset_url(substr($req['product_image'], 7)) : upload_url('products/' . $req['product_image']))
                                                    : asset_url('images/placeholders/default-product.svg');
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="<?= $img ?>" alt="Thumb" class="table-thumb">
                                                        <div>
                                                            <a href="<?= base_url('product-details.php?id=' . $req['product_id']) ?>" class="fw-bold d-block text-truncate" style="max-width: 160px;">
                                                                <?= e($req['product_title']) ?>
                                                            </a>
                                                            <span class="text-primary fw-bold"><?= format_currency($req['product_price']) ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong class="d-block"><?= e($req['buyer_name']) ?></strong>
                                                    <span class="small text-muted"><?= e($req['buyer_dept']) ?></span>
                                                    <span class="badge bg-light text-dark border small d-block mt-1"><?= e($req['buyer_student_id']) ?></span>
                                                </td>
                                                <td>
                                                    <div class="small text-secondary" style="max-width: 220px;">
                                                        <?= !empty($req['message']) ? e($req['message']) : '<span class="text-muted fst-italic">No message provided.</span>' ?>
                                                    </div>
                                                    <span class="text-muted" style="font-size: 0.72rem;"><?= time_ago($req['created_at']) ?></span>
                                                </td>
                                                <td>
                                                    <?= render_request_status_badge($req['status']) ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column gap-1" style="min-width: 120px;">
                                                        <?php if ($req['status'] === 'pending'): ?>
                                                            <a href="<?= base_url('actions/purchase-action.php?action=accept&id=' . $req['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-success py-1">
                                                                <i class="bi bi-check2"></i> <?= t('accept_request') ?>
                                                            </a>
                                                            <a href="<?= base_url('actions/purchase-action.php?action=reject&id=' . $req['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-danger py-1" onclick="return confirm('Decline this offer?');">
                                                                <i class="bi bi-x"></i> <?= t('reject_request') ?>
                                                            </a>
                                                        <?php elseif ($req['status'] === 'accepted'): ?>
                                                            <a href="<?= base_url('actions/purchase-action.php?action=complete&id=' . $req['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-primary py-1" onclick="return confirm('Mark this item as sold to this buyer?');">
                                                                <i class="bi bi-check2-all"></i> Mark as Sold
                                                            </a>
                                                            <a href="<?= base_url('actions/message-action.php?action=start&product_id=' . $req['product_id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-secondary py-1">
                                                                <i class="bi bi-chat-dots"></i> Message Buyer
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- OUTGOING REQUESTS (My Offers Sent to Sellers) -->
                    <div class="tab-pane fade <?= $activeTab === 'outgoing' ? 'show active' : '' ?>" id="outgoingTab" role="tabpanel">
                        <?php if (empty($outgoingRequests)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-send fs-1 text-muted mb-3 d-block"></i>
                                <h5 class="fw-bold"><?= t('no_outgoing_requests') ?></h5>
                                <p class="text-muted">You haven't sent purchase requests for any items yet.</p>
                                <a href="<?= base_url('products.php') ?>" class="btn btn-obu mt-2">
                                    <i class="bi bi-shop me-1"></i> Browse Marketplace
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-custom align-middle">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Seller</th>
                                            <th>My Message</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($outgoingRequests as $req): ?>
                                            <?php 
                                                $img = !empty($req['product_image']) 
                                                    ? (str_starts_with($req['product_image'], 'assets/') ? asset_url(substr($req['product_image'], 7)) : upload_url('products/' . $req['product_image']))
                                                    : asset_url('images/placeholders/default-product.svg');
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="<?= $img ?>" alt="Thumb" class="table-thumb">
                                                        <div>
                                                            <a href="<?= base_url('product-details.php?id=' . $req['product_id']) ?>" class="fw-bold d-block text-truncate" style="max-width: 170px;">
                                                                <?= e($req['product_title']) ?>
                                                            </a>
                                                            <span class="text-primary fw-bold"><?= format_currency($req['product_price']) ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong><?= e($req['seller_name']) ?></strong>
                                                    <span class="small text-muted d-block"><?= e($req['seller_dept']) ?></span>
                                                </td>
                                                <td>
                                                    <div class="small text-secondary" style="max-width: 220px;">
                                                        <?= !empty($req['message']) ? e($req['message']) : '<span class="text-muted fst-italic">No message attached.</span>' ?>
                                                    </div>
                                                    <span class="text-muted" style="font-size: 0.72rem;"><?= time_ago($req['created_at']) ?></span>
                                                </td>
                                                <td>
                                                    <?= render_request_status_badge($req['status']) ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column gap-1">
                                                        <a href="<?= base_url('actions/message-action.php?action=start&product_id=' . $req['product_id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-primary py-1">
                                                            <i class="bi bi-chat-dots"></i> Chat Seller
                                                        </a>
                                                        <?php if (in_array($req['status'], ['pending', 'accepted'])): ?>
                                                            <a href="<?= base_url('actions/purchase-action.php?action=cancel&id=' . $req['id'] . '&csrf_token=' . csrf_token()) ?>" class="btn btn-sm btn-outline-danger py-1" onclick="return confirm('Cancel this request?');">
                                                                <i class="bi bi-x-circle"></i> Cancel
                                                            </a>
                                                        <?php endif; ?>
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
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

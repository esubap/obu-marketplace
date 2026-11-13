<?php
/**
 * OBU Student Marketplace - Moderator Action Handler
 */
require_once __DIR__ . '/../config/app.php';
require_moderator();

$action = clean_input($_REQUEST['action'] ?? '');
$currentUser = current_user();
$modId = (int)$currentUser['id'];

if (!db()) {
    set_flash('danger', t('error_database'));
    header('Location: ' . base_url('moderator/dashboard.php'));
    exit;
}

// -------------------------------------------------------------
// ACTION: HIDE SUSPICIOUS PRODUCT
// -------------------------------------------------------------
if ($action === 'hide_product') {
    require_csrf();
    $productId = (int)($_POST['product_id'] ?? ($_GET['id'] ?? 0));
    $reason = clean_input($_POST['reason'] ?? 'Flagged as suspicious pending administrator review');

    if ($productId > 0) {
        $stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $prod = $stmt->fetch();

        if ($prod) {
            $stmtUp = db()->prepare("UPDATE products SET status = 'hidden' WHERE id = ?");
            $stmtUp->execute([$productId]);

            // Notify owner
            create_notification(
                (int)$prod['user_id'],
                'Listing Temporarily Hidden',
                "Your listing '{$prod['title']}' has been hidden by moderators for review: {$reason}",
                'moderation',
                $productId,
                'student/my-products.php'
            );

            log_activity('MOD_HIDE_PRODUCT', "Moderator {$currentUser['full_name']} hid product #$productId", $modId);
            set_flash('warning', "Product #$productId has been hidden from the public marketplace.");
        }
    }
}

// -------------------------------------------------------------
// ACTION: RESOLVE REPORT
// -------------------------------------------------------------
if ($action === 'resolve_report') {
    require_csrf();
    $reportId = (int)($_POST['report_id'] ?? 0);
    $notes = clean_input($_POST['admin_notes'] ?? '');

    if ($reportId > 0) {
        $stmt = db()->prepare("
            UPDATE reports 
            SET status = 'resolved', reviewed_by = ?, admin_notes = ? 
            WHERE id = ?
        ");
        $stmt->execute([$modId, $notes, $reportId]);

        log_activity('MOD_RESOLVE_REPORT', "Moderator {$currentUser['full_name']} resolved report #$reportId", $modId);
        set_flash('success', "Report #$reportId marked as resolved.");
    }
}

// -------------------------------------------------------------
// ACTION: REJECT REPORT (Invalid report)
// -------------------------------------------------------------
if ($action === 'reject_report') {
    require_csrf();
    $reportId = (int)($_POST['report_id'] ?? 0);
    $notes = clean_input($_POST['admin_notes'] ?? 'Dismissed as invalid or insufficient evidence');

    if ($reportId > 0) {
        $stmt = db()->prepare("
            UPDATE reports 
            SET status = 'rejected', reviewed_by = ?, admin_notes = ? 
            WHERE id = ?
        ");
        $stmt->execute([$modId, $notes, $reportId]);

        log_activity('MOD_DISMISS_REPORT', "Moderator {$currentUser['full_name']} dismissed report #$reportId", $modId);
        set_flash('info', "Report #$reportId dismissed as invalid.");
    }
}

$back = $_SERVER['HTTP_REFERER'] ?? base_url('moderator/reports.php');
header('Location: ' . $back);
exit;

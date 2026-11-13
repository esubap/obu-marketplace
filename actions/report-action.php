<?php
/**
 * OBU Student Marketplace - User & Product Report Action Handler (Module 11)
 */
require_once __DIR__ . '/../config/app.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('products.php'));
    exit;
}

require_csrf();

$currentUser = current_user();
$reporterId = (int)$currentUser['id'];
$productId = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
$reportedUserId = !empty($_POST['reported_user_id']) ? (int)$_POST['reported_user_id'] : null;
$reason = clean_input($_POST['reason'] ?? '');
$description = clean_input($_POST['description'] ?? '');

if (empty($reason) || empty($description)) {
    set_flash('danger', 'Please provide a reason and detailed description for your report.');
    $back = $_SERVER['HTTP_REFERER'] ?? base_url('products.php');
    header('Location: ' . $back);
    exit;
}

if (!db()) {
    set_flash('danger', t('error_database'));
    header('Location: ' . base_url('products.php'));
    exit;
}

try {
    $stmt = db()->prepare("
        INSERT INTO reports (reporter_id, reported_user_id, product_id, reason, description, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $reporterId,
        $reportedUserId,
        $productId,
        $reason,
        $description
    ]);

    $reportId = (int)db()->lastInsertId();

    // Log the action
    log_activity('SUBMIT_REPORT', "Report #$reportId submitted by {$currentUser['full_name']} (Reason: $reason)", $reporterId);

    set_flash('success', t('report_submitted_success'));

} catch (Exception $e) {
    set_flash('danger', 'Failed to submit report: ' . $e->getMessage());
}

$back = $_SERVER['HTTP_REFERER'] ?? base_url('products.php');
header('Location: ' . $back);
exit;

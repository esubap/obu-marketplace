<?php
/**
 * OBU Student Marketplace - Notifications Action Handler (Module 9)
 */
require_once __DIR__ . '/../config/app.php';
require_login();

$action = clean_input($_REQUEST['action'] ?? '');
$userId = (int)current_user_id();

if (!db()) {
    header('Location: ' . base_url('student/notifications.php'));
    exit;
}

if ($action === 'mark_read') {
    $id = (int)($_GET['id'] ?? 0);
    $csrf = $_GET['csrf_token'] ?? '';
    if (verify_csrf_token($csrf) && $id > 0) {
        $stmt = db()->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    }
}

if ($action === 'mark_all_read') {
    $csrf = $_GET['csrf_token'] ?? '';
    if (verify_csrf_token($csrf)) {
        $stmt = db()->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        set_flash('success', 'All notifications marked as read.');
    }
}

$back = $_SERVER['HTTP_REFERER'] ?? base_url('student/notifications.php');
header('Location: ' . $back);
exit;

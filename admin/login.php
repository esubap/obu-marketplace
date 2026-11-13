<?php
/**
 * OBU Student Marketplace - Administrator Gateway
 */
require_once __DIR__ . '/../config/app.php';

if (is_logged_in()) {
    if (current_user_role() === ROLE_ADMIN) {
        header('Location: ' . base_url('admin/dashboard.php'));
        exit;
    }
    set_flash('danger', 'Administrator privileges required.');
    header('Location: ' . base_url('student/dashboard.php'));
    exit;
}

header('Location: ' . base_url('login.php'));
exit;

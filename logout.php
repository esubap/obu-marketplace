<?php
/**
 * OBU Student Marketplace - Secure Logout
 */
require_once __DIR__ . '/config/app.php';

logout_user();

set_flash('info', t('msg_logout_success'));
header('Location: ' . base_url('login.php'));
exit;

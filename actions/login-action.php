<?php
/**
 * OBU Student Marketplace - Login Action
 */
require_once __DIR__ . '/../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('login.php'));
    exit;
}

require_csrf();

$identifier = clean_input($_POST['identifier'] ?? '');
$password = $_POST['password'] ?? '';
$remember = !empty($_POST['remember']);
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

$_SESSION['login_identifier'] = $identifier;

if (empty($identifier) || empty($password)) {
    set_flash('danger', 'Please enter your email or Student ID and password.');
    header('Location: ' . base_url('login.php'));
    exit;
}

// 1. Rate Limiting Check
if (is_rate_limited($identifier, $ip)) {
    set_flash('danger', t('error_rate_limit'));
    header('Location: ' . base_url('login.php'));
    exit;
}

if (!db()) {
    $errDetail = Database::getError();
    $errMsg = t('error_database');
    if ($errDetail) {
        $errMsg .= " Details: " . $errDetail;
    }
    set_flash('danger', $errMsg);
    header('Location: ' . base_url('login.php'));
    exit;
}

try {
    // Find user by either email OR student_id
    $stmt = db()->prepare("
        SELECT * FROM users 
        WHERE email = ? OR student_id = ?
        LIMIT 1
    ");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        // Record failed attempt for rate limiting
        record_login_attempt($identifier, $ip);
        set_flash('danger', t('error_invalid_credentials'));
        header('Location: ' . base_url('login.php'));
        exit;
    }

    // Check account status
    if ($user['status'] === STATUS_SUSPENDED) {
        set_flash('danger', t('error_account_suspended'));
        header('Location: ' . base_url('login.php'));
        exit;
    }

    // Authenticate user & start session
    login_user($user, $remember);
    unset($_SESSION['login_identifier']);
    set_flash('success', t('msg_login_success'));

    // Determine redirection
    $redirect = $_SESSION['redirect_after_login'] ?? '';
    unset($_SESSION['redirect_after_login']);

    if (!empty($redirect) && !str_contains($redirect, 'login.php') && !str_contains($redirect, 'register.php')) {
        header('Location: ' . $redirect);
        exit;
    }

    // Role-based destination
    if ($user['role'] === ROLE_ADMIN) {
        header('Location: ' . base_url('admin/dashboard.php'));
    } elseif ($user['role'] === ROLE_MODERATOR) {
        header('Location: ' . base_url('moderator/dashboard.php'));
    } else {
        header('Location: ' . base_url('student/dashboard.php'));
    }
    exit;

} catch (Exception $e) {
    set_flash('danger', 'Login processing error. Please try again.');
    header('Location: ' . base_url('login.php'));
    exit;
}

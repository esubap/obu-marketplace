<?php
/**
 * OBU Student Marketplace - Cross-Site Request Forgery (CSRF) Protection
 */

/**
 * Generate or get existing CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output HTML hidden CSRF input field
 */
function csrf_field(): string {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate incoming CSRF token from POST or header
 */
function verify_csrf_token(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    }
    
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Enforce CSRF check or abort with friendly message
 */
function require_csrf(): void {
    if (!verify_csrf_token()) {
        http_response_code(403);
        set_flash('danger', t('error_csrf'));
        $back = $_SERVER['HTTP_REFERER'] ?? base_url();
        header("Location: $back");
        exit;
    }
}

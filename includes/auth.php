<?php
/**
 * OBU Student Marketplace - Authentication & Role-Based Access Control (RBAC)
 */

/**
 * Check if a user is currently logged in
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

/**
 * Get current user array from session or fresh from DB
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    
    // Periodically sync user status from DB to catch suspensions immediately
    if (db() && (!isset($_SESSION['user_last_sync']) || time() - $_SESSION['user_last_sync'] > 60)) {
        try {
            $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([(int)$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if ($user) {
                if ($user['status'] === STATUS_SUSPENDED) {
                    logout_user();
                    set_flash('danger', t('error_account_suspended'));
                    header('Location: ' . base_url('login.php'));
                    exit;
                }
                $_SESSION['user'] = $user;
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_last_sync'] = time();
            } else {
                logout_user();
                return null;
            }
        } catch (Exception $e) {}
    }
    
    return $_SESSION['user'] ?? null;
}

/**
 * Get current user ID
 */
function current_user_id(): ?int {
    return !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Get current user role
 */
function current_user_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Authenticate and log in user
 */
function login_user(array $user, bool $remember = false): void {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user'] = $user;
    $_SESSION['user_last_sync'] = time();

    // Set user language & theme if stored
    if (!empty($user['preferred_language'])) {
        $_SESSION['lang'] = $user['preferred_language'];
        setcookie('obu_lang', $user['preferred_language'], time() + (86400 * 365), '/', '', false, true);
    }
    if (!empty($user['theme_preference'])) {
        $_SESSION['theme'] = $user['theme_preference'];
        setcookie('obu_theme', $user['theme_preference'], time() + (86400 * 365), '/', '', false, true);
    }

    // Clear failed login attempts on success
    clear_login_attempts($user['email'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

    log_activity('LOGIN', "User logged in ({$user['email']}, Role: {$user['role']})", (int)$user['id']);
}

/**
 * Securely log out current user
 */
function logout_user(): void {
    if (is_logged_in()) {
        log_activity('LOGOUT', 'User logged out', current_user_id());
    }

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Enforce authentication: Redirect to login if guest
 */
function require_login(string $redirectUrl = ''): void {
    if (!is_logged_in()) {
        $redirect = !empty($redirectUrl) ? $redirectUrl : ($_SERVER['REQUEST_URI'] ?? base_url());
        $_SESSION['redirect_after_login'] = $redirect;
        set_flash('warning', 'Please log in to continue.');
        header('Location: ' . base_url('login.php'));
        exit;
    }

    // Check account status
    $user = current_user();
    if ($user && $user['status'] === STATUS_SUSPENDED) {
        logout_user();
        set_flash('danger', t('error_account_suspended'));
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

/**
 * Enforce specific role requirement (RBAC)
 */
function require_role($roles): void {
    require_login();
    
    $allowed = is_array($roles) ? $roles : [$roles];
    $currentRole = current_user_role();
    
    if (!in_array($currentRole, $allowed)) {
        http_response_code(403);
        set_flash('danger', t('error_unauthorized'));
        
        // Redirect according to their actual role
        if ($currentRole === ROLE_ADMIN) {
            header('Location: ' . base_url('admin/dashboard.php'));
        } elseif ($currentRole === ROLE_MODERATOR) {
            header('Location: ' . base_url('moderator/dashboard.php'));
        } else {
            header('Location: ' . base_url('student/dashboard.php'));
        }
        exit;
    }
}

/**
 * Require administrator role
 */
function require_admin(): void {
    require_role(ROLE_ADMIN);
}

/**
 * Require moderator or administrator role
 */
function require_moderator(): void {
    require_role([ROLE_MODERATOR, ROLE_ADMIN]);
}

/**
 * Require student role (or allow admin/moderator viewing student space)
 */
function require_student(): void {
    require_role([ROLE_STUDENT, ROLE_ADMIN, ROLE_MODERATOR]);
}

/**
 * Rate limiting check for login attempts
 */
function is_rate_limited(string $email, string $ip): bool {
    if (!db()) return false;
    try {
        $stmt = db()->prepare("
            SELECT COUNT(*) FROM login_attempts 
            WHERE (email = ? OR ip_address = ?) 
              AND attempted_at > DATE_SUB(NOW(), INTERVAL " . LOGIN_LOCKOUT_MINUTES . " MINUTE)
        ");
        $stmt->execute([$email, $ip]);
        return (int)$stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Record a failed login attempt
 */
function record_login_attempt(string $email, string $ip): void {
    if (!db()) return;
    try {
        $stmt = db()->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
        $stmt->execute([$email, $ip]);
    } catch (Exception $e) {}
}

/**
 * Clear login attempts after successful authentication
 */
function clear_login_attempts(string $email, string $ip): void {
    if (!db()) return;
    try {
        $stmt = db()->prepare("DELETE FROM login_attempts WHERE email = ? OR ip_address = ?");
        $stmt->execute([$email, $ip]);
    } catch (Exception $e) {}
}

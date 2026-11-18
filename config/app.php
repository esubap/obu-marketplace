<?php
/**
 * OBU Student Marketplace - Application Bootstrapper
 * Oda Bultum University
 */

// Output buffering to prevent "headers already sent" issues
if (!ob_get_level()) {
    ob_start();
}

// Error handling configuration
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Safe for production redirects

// Set Timezone to Ethiopia (Addis Ababa / GMT+3)
date_default_timezone_set('Africa/Addis_Ababa');

// Load Core Configurations
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';

// Secure Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
        || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME_SECONDS,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

/**
 * Get the application base URL dynamically
 */
function base_url(string $path = ''): string {
    static $baseUrl = null;
    
    if ($baseUrl === null) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');

        $protocol = $isHttps ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Find path relative to document root
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $appRoot = str_replace('\\', '/', BASE_DIR);
        
        $subDir = '';
        if ($docRoot && str_starts_with($appRoot, $docRoot)) {
            $subDir = substr($appRoot, strlen($docRoot));
        } else {
            // Default subfolder convention if running under standard XAMPP or custom folder
            $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            if (strpos($scriptName, '/obu-marketplace') !== false) {
                $subDir = '/obu-marketplace';
            } elseif (strpos($scriptName, '/school management/obu-marketplace') !== false) {
                $subDir = '/school%20management/obu-marketplace';
            }
        }
        
        $baseUrl = rtrim($protocol . $host . $subDir, '/');
    }
    
    $path = ltrim($path, '/');
    return empty($path) ? $baseUrl : $baseUrl . '/' . $path;
}

/**
 * Generate asset URL
 */
function asset_url(string $path = ''): string {
    return base_url('assets/' . ltrim($path, '/'));
}

/**
 * Generate uploads URL (supports local file paths, serverless Base64 Data URLs, and external URLs)
 */
function upload_url(string $path = ''): string {
    $trimmed = ltrim($path, '/');
    if (empty($trimmed)) {
        return base_url('uploads');
    }
    // Handle Base64 Data URLs directly (preserves images on serverless environments like Vercel)
    if (str_starts_with($trimmed, 'data:image')) {
        return $trimmed;
    }
    if (str_contains($trimmed, 'data:image')) {
        $pos = strpos($trimmed, 'data:image');
        return substr($trimmed, $pos);
    }
    // Handle absolute URLs
    if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
        return $trimmed;
    }
    return base_url('uploads/' . $trimmed);
}

/**
 * Universal helper to get product image URL
 */
function get_product_image_url(?string $imagePath): string {
    if (empty($imagePath)) {
        return asset_url('images/placeholders/default-product.svg');
    }
    if (str_starts_with($imagePath, 'assets/')) {
        return asset_url(substr($imagePath, 7));
    }
    return upload_url('products/' . $imagePath);
}

/**
 * Universal helper to get profile avatar URL
 */
function get_profile_image_url(?string $avatarPath): string {
    if (empty($avatarPath)) {
        return asset_url('images/placeholders/default-avatar.svg');
    }
    if (str_starts_with($avatarPath, 'assets/')) {
        return asset_url(substr($avatarPath, 7));
    }
    return upload_url('profiles/' . $avatarPath);
}

// Load Core Application Services
require_once BASE_DIR . '/includes/csrf.php';
require_once BASE_DIR . '/includes/language.php';
require_once BASE_DIR . '/includes/functions.php';
require_once BASE_DIR . '/includes/auth.php';
require_once BASE_DIR . '/includes/validation.php';

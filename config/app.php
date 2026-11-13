<?php
/**
 * OBU Student Marketplace - Application Bootstrapper
 * Oda Bultum University
 */

// Error handling configuration
error_reporting(E_ALL);
ini_set('display_errors', '1'); // In development, display errors; in production, set to 0

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
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
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
 * Generate uploads URL
 */
function upload_url(string $path = ''): string {
    return base_url('uploads/' . ltrim($path, '/'));
}

// Load Core Application Services
require_once BASE_DIR . '/includes/csrf.php';
require_once BASE_DIR . '/includes/language.php';
require_once BASE_DIR . '/includes/functions.php';
require_once BASE_DIR . '/includes/auth.php';
require_once BASE_DIR . '/includes/validation.php';

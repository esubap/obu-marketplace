<?php
/**
 * OBU Student Marketplace - Vercel Serverless Entrypoint & Front Controller
 * Oda Bultum University
 *
 * This router dispatches requests on Vercel's serverless runtime to the proper PHP script,
 * while maintaining 100% full compatibility with local XAMPP Apache execution.
 */

// 1. Parse requested path
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = trim($requestUri, '/');

// Base directory is the project root (one directory up from /api)
$baseDir = dirname(__DIR__);

// 2. Route root request to index.php
if ($path === '' || $path === 'index.php') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $baseDir . '/index.php';
    chdir($baseDir);
    require $baseDir . '/index.php';
    exit;
}

// Security: Prevent directory traversal
$cleanPath = str_replace(['../', '..\\'], '', $path);
$targetFile = $baseDir . '/' . $cleanPath;

// 3. Exact PHP file match (e.g. /login.php, /student/dashboard.php, /actions/login-action.php)
if (is_file($targetFile) && pathinfo($targetFile, PATHINFO_EXTENSION) === 'php') {
    $_SERVER['SCRIPT_NAME'] = '/' . $cleanPath;
    $_SERVER['PHP_SELF'] = '/' . $cleanPath;
    $_SERVER['SCRIPT_FILENAME'] = $targetFile;
    chdir(dirname($targetFile));
    require $targetFile;
    exit;
}

// 4. Extensionless PHP match (e.g. /login -> /login.php, /student/dashboard -> /student/dashboard.php)
if (is_file($targetFile . '.php')) {
    $_SERVER['SCRIPT_NAME'] = '/' . $cleanPath . '.php';
    $_SERVER['PHP_SELF'] = '/' . $cleanPath . '.php';
    $_SERVER['SCRIPT_FILENAME'] = $targetFile . '.php';
    chdir(dirname($targetFile));
    require $targetFile . '.php';
    exit;
}

// 5. Directory with index.php match
if (is_dir($targetFile) && is_file($targetFile . '/index.php')) {
    $_SERVER['SCRIPT_NAME'] = '/' . $cleanPath . '/index.php';
    $_SERVER['PHP_SELF'] = '/' . $cleanPath . '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $targetFile . '/index.php';
    chdir($targetFile);
    require $targetFile . '/index.php';
    exit;
}

// 6. Direct static file delivery fallback (if not caught by Vercel static router)
if (is_file($targetFile)) {
    $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $mimes = [
        'css'  => 'text/css; charset=UTF-8',
        'js'   => 'application/javascript; charset=UTF-8',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'ico'  => 'image/x-icon',
        'json' => 'application/json',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf'
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
        header('Content-Length: ' . filesize($targetFile));
        readfile($targetFile);
        exit;
    }
}

// 7. 404 Fallback
http_response_code(404);
if (is_file($baseDir . '/404.php')) {
    chdir($baseDir);
    require $baseDir . '/404.php';
} else {
    echo "<h1>404 Not Found</h1><p>The requested page was not found.</p>";
}
exit;

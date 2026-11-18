<?php
/**
 * OBU Student Marketplace - Core Helper Functions
 */

/**
 * Set a flash message to display on next page view
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash_messages'][] = [
        'type'    => $type, // success, danger, warning, info
        'message' => $message
    ];
}

/**
 * Check if there are pending flash messages
 */
function has_flash(): bool {
    return !empty($_SESSION['flash_messages']);
}

/**
 * Retrieve and clear flash messages
 */
function get_flash(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Format monetary amount with currency (e.g. 1,250.00 ETB)
 */
function format_currency($amount): string {
    $num = is_numeric($amount) ? floatval($amount) : 0;
    return number_format($num, 2, '.', ',') . ' ' . t('currency', DEFAULT_CURRENCY);
}

/**
 * Relative human-readable time (e.g. 2 hours ago, yesterday)
 */
function time_ago(string $datetime): string {
    $timestamp = strtotime($datetime);
    if (!$timestamp) return $datetime;
    
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = max(1, floor($diff / 60));
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hrs = floor($diff / 3600);
        return $hrs . ' hour' . ($hrs > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 172800) {
        return 'Yesterday';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' days ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

/**
 * Log administrative or system activity
 */
function log_activity(string $action, string $description, ?int $userId = null): void {
    if (!db()) return;
    try {
        if ($userId === null && !empty($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = db()->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $description, $ip]);
    } catch (Exception $e) {
        // Silently fail to not interrupt user flow
    }
}

/**
 * Create a persistent notification for a user
 */
function create_notification(int $userId, string $title, string $message, string $type, ?int $relatedId = null, ?string $linkUrl = null): bool {
    if (!db()) return false;
    try {
        $stmt = db()->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_id, link_url) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $title, $message, $type, $relatedId, $linkUrl]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get setting value from database or fallback to default
 */
function get_setting(string $key, string $default = ''): string {
    static $settingsCache = null;
    
    if ($settingsCache === null && db()) {
        try {
            $stmt = db()->query("SELECT setting_key, setting_value FROM settings");
            $settingsCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $settingsCache = [];
        }
    }
    
    return $settingsCache[$key] ?? $default;
}

/**
 * Fetch all active product categories
 */
function get_all_categories(): array {
    static $categories = null;
    if ($categories === null && db()) {
        try {
            $stmt = db()->query("SELECT * FROM categories WHERE status = 'active' ORDER BY id ASC");
            $categories = $stmt->fetchAll();
        } catch (Exception $e) {
            $categories = [];
        }
    }
    return $categories ?? [];
}

/**
 * Get count of unread messages for logged in user
 */
function get_unread_message_count(int $userId): int {
    if (!db()) return 0;
    try {
        $stmt = db()->prepare("
            SELECT COUNT(m.id) 
            FROM messages m
            JOIN conversations c ON m.conversation_id = c.id
            WHERE m.sender_id != ? AND m.is_read = 0 AND (c.buyer_id = ? OR c.seller_id = ?)
        ");
        $stmt->execute([$userId, $userId, $userId]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get count of unread notifications for logged in user
 */
function get_unread_notification_count(int $userId): int {
    if (!db()) return 0;
    try {
        $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * HTML Badge for condition
 */
function render_condition_badge(string $condition): string {
    $badgeClasses = [
        'Brand New'    => 'bg-success text-white',
        'Like New'     => 'bg-primary text-white',
        'Good'         => 'bg-info text-dark',
        'Used'         => 'bg-secondary text-white',
        'Needs Repair' => 'bg-warning text-dark'
    ];
    $cls = $badgeClasses[$condition] ?? 'bg-secondary text-white';
    
    // Translated condition text
    $condKey = 'condition_' . strtolower(str_replace(' ', '_', $condition));
    $label = t($condKey, $condition);
    
    return '<span class="badge ' . $cls . ' rounded-pill px-2.5 py-1">' . e($label) . '</span>';
}

/**
 * HTML Badge for product status
 */
function render_status_badge(string $status): string {
    $classes = [
        'active'   => 'bg-success text-white',
        'sold'     => 'bg-secondary text-white',
        'pending'  => 'bg-warning text-dark',
        'rejected' => 'bg-danger text-white',
        'hidden'   => 'bg-dark text-white'
    ];
    $cls = $classes[$status] ?? 'bg-light text-dark';
    $label = t('status_' . $status, ucfirst($status));
    return '<span class="badge ' . $cls . ' rounded-pill px-2.5 py-1">' . e($label) . '</span>';
}

/**
 * Render Purchase Request Status Badge
 */
function render_request_status_badge(string $status): string {
    $classes = [
        'pending'   => 'bg-warning text-dark',
        'accepted'  => 'bg-success text-white',
        'rejected'  => 'bg-danger text-white',
        'cancelled' => 'bg-secondary text-white',
        'completed' => 'bg-primary text-white'
    ];
    $cls = $classes[$status] ?? 'bg-secondary text-white';
    $label = t('request_status_' . $status, ucfirst($status));
    return '<span class="badge ' . $cls . ' rounded-pill px-2 py-1">' . e($label) . '</span>';
}

/**
 * Truncate text cleanly with ellipsis
 */
function str_excerpt(string $text, int $limit = 100): string {
    $clean = strip_tags($text);
    if (mb_strlen($clean) <= $limit) {
        return $clean;
    }
    return mb_substr($clean, 0, $limit) . '...';
}

/**
 * Universally store an uploaded image across both traditional disk (XAMPP)
 * and read-only serverless filesystems (Vercel / AWS Lambda).
 *
 * @param array $file $_FILES item array (with name, tmp_name, error, size, etc.)
 * @param string $targetFolder 'products' or 'profiles'
 * @param string $prefix File name prefix (e.g. 'prod' or 'avatar')
 * @return string|null Relative filename (if saved to disk) OR Base64 Data URL (if serverless), or null on failure.
 */
function store_uploaded_image(array $file, string $targetFolder = 'products', string $prefix = 'prod'): ?string {
    if (empty($file) || empty($file['tmp_name']) || !file_exists($file['tmp_name'])) {
        return null;
    }

    // Ensure validation helper is available
    if (function_exists('validate_image_upload')) {
        $validation = validate_image_upload($file);
        if (!$validation['valid']) {
            return null;
        }
        $ext = $validation['ext'] ?? 'jpg';
        $mime = $validation['mime'] ?? 'image/jpeg';
    } else {
        $ext = strtolower(pathinfo($file['name'] ?? 'image.jpg', PATHINFO_EXTENSION)) ?: 'jpg';
        $mime = 'image/jpeg';
    }

    $uploadDir = ($targetFolder === 'profiles') ? PROFILES_UPLOAD_DIR : PRODUCTS_UPLOAD_DIR;

    // 1. Try traditional local disk storage (when folder is writable, e.g. local XAMPP)
    $diskWritable = false;
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }
    if (is_dir($uploadDir) && is_writable($uploadDir)) {
        $diskWritable = true;
    }

    if ($diskWritable) {
        $safeName = $prefix . '_' . bin2hex(random_bytes(10)) . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
        if (@move_uploaded_file($file['tmp_name'], $destPath)) {
            return $safeName;
        }
    }

    // 2. Serverless fallback (Vercel / AWS Lambda read-only disk):
    // Read the temporary uploaded file and encode as an optimized Base64 Data URL.
    // This allows the image to persist in MySQL / TiDB Cloud and render worldwide
    // without requiring external S3 or third-party paid buckets!
    $raw = @file_get_contents($file['tmp_name']);
    if ($raw === false || strlen($raw) === 0) {
        return null;
    }

    // Attempt smart GD resizing & compression to keep database records compact (< 100KB)
    if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
        $srcImg = @imagecreatefromstring($raw);
        if ($srcImg !== false) {
            $origW = imagesx($srcImg);
            $origH = imagesy($srcImg);
            $maxDim = 900; // Optimal resolution for product cards and mobile screens

            if ($origW > $maxDim || $origH > $maxDim) {
                if ($origW >= $origH) {
                    $newW = $maxDim;
                    $newH = (int)round(($origH / $origW) * $maxDim);
                } else {
                    $newH = $maxDim;
                    $newW = (int)round(($origW / $origH) * $maxDim);
                }

                $dstImg = imagecreatetruecolor($newW, $newH);
                if ($mime === 'image/png' || $mime === 'image/webp') {
                    imagealphablending($dstImg, false);
                    imagesavealpha($dstImg, true);
                }

                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
                imagedestroy($srcImg);
                $srcImg = $dstImg;
            }

            ob_start();
            if ($mime === 'image/png') {
                imagepng($srcImg, null, 7);
            } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
                imagewebp($srcImg, null, 80);
            } else {
                imagejpeg($srcImg, null, 80);
                $mime = 'image/jpeg';
            }
            $compressed = ob_get_clean();
            imagedestroy($srcImg);

            if (!empty($compressed)) {
                $raw = $compressed;
            }
        }
    }

    return 'data:' . $mime . ';base64,' . base64_encode($raw);
}

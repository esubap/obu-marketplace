<?php
/**
 * OBU Student Marketplace - System Constants
 * Oda Bultum University
 */

// Application Details
define('APP_NAME', 'OBU Student Marketplace');
define('APP_SHORT_NAME', 'OBU Marketplace');
define('APP_VERSION', '1.0.0');
define('UNIVERSITY_NAME', 'Oda Bultum University');
define('DEFAULT_CURRENCY', 'ETB');
define('DEFAULT_CURRENCY_SYMBOL', 'ETB');

// Base Paths
define('BASE_DIR', dirname(__DIR__));
define('UPLOADS_DIR', BASE_DIR . DIRECTORY_SEPARATOR . 'uploads');
define('PRODUCTS_UPLOAD_DIR', UPLOADS_DIR . DIRECTORY_SEPARATOR . 'products');
define('PROFILES_UPLOAD_DIR', UPLOADS_DIR . DIRECTORY_SEPARATOR . 'profiles');

// User Roles
define('ROLE_STUDENT', 'student');
define('ROLE_MODERATOR', 'moderator');
define('ROLE_ADMIN', 'admin');

// User Status
define('STATUS_ACTIVE', 'active');
define('STATUS_PENDING', 'pending');
define('STATUS_SUSPENDED', 'suspended');

// Product Conditions
define('PRODUCT_CONDITIONS', [
    'Brand New'    => ['en' => 'Brand New',    'am' => 'አዲስ',         'om' => 'Haaraa'],
    'Like New'     => ['en' => 'Like New',     'am' => 'እንደ አዲስ',    'om' => 'Akka Haaraa'],
    'Good'         => ['en' => 'Good',         'am' => 'ጥሩ ሁኔታ ላይ',  'om' => 'Haala Gaarii'],
    'Used'         => ['en' => 'Used',         'am' => 'ጥቅም ላይ የዋለ', 'om' => 'Kan Hojjetame'],
    'Needs Repair' => ['en' => 'Needs Repair', 'am' => 'ጥገና የሚያስፈልገው', 'om' => 'Suphaa Barbaada']
]);

// Product Status
define('PRODUCT_STATUS_PENDING', 'pending');
define('PRODUCT_STATUS_ACTIVE', 'active');
define('PRODUCT_STATUS_REJECTED', 'rejected');
define('PRODUCT_STATUS_SOLD', 'sold');
define('PRODUCT_STATUS_HIDDEN', 'hidden');

// Purchase Request Statuses
define('REQ_PENDING', 'pending');
define('REQ_ACCEPTED', 'accepted');
define('REQ_REJECTED', 'rejected');
define('REQ_CANCELLED', 'cancelled');
define('REQ_COMPLETED', 'completed');

// Supported Languages
define('SUPPORTED_LANGUAGES', [
    'en' => ['name' => 'English', 'native' => 'English', 'flag' => '🇬🇧', 'dir' => 'ltr'],
    'am' => ['name' => 'Amharic', 'native' => 'አማርኛ',  'flag' => '🇪🇹', 'dir' => 'ltr'],
    'om' => ['name' => 'Afaan Oromoo', 'native' => 'Afaan Oromoo', 'flag' => '🌳', 'dir' => 'ltr']
]);
define('DEFAULT_LANGUAGE', 'en');

// Themes (Light, Bright, Dark)
define('SUPPORTED_THEMES', ['light', 'bright', 'dark']);
define('DEFAULT_THEME', 'bright'); // User asked for bright & light mode option; default to bright!

// Upload Restrictions
define('MAX_FILE_SIZE_BYTES', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_IMAGE_MIMES', ['image/jpeg', 'image/png', 'image/webp']);

// Security Settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('PASSWORD_RESET_EXPIRY_HOURS', 2);
define('SESSION_LIFETIME_SECONDS', 86400); // 24 hours

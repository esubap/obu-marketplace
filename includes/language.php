<?php
/**
 * OBU Student Marketplace - Multilingual & Theme Management
 * Languages: English (en), Amharic (am), Afaan Oromo (om)
 * Themes: Light, Bright, Dark
 */

// Initialize or switch language
function init_language(): string {
    $supported = array_keys(SUPPORTED_LANGUAGES);
    
    // Check URL parameter (?lang=am)
    if (!empty($_GET['lang']) && in_array($_GET['lang'], $supported)) {
        $lang = $_GET['lang'];
        $_SESSION['lang'] = $lang;
        setcookie('obu_lang', $lang, time() + (86400 * 365), '/', '', false, true);

        // If user logged in, persist to database
        if (!empty($_SESSION['user_id']) && db()) {
            try {
                $stmt = db()->prepare("UPDATE users SET preferred_language = ? WHERE id = ?");
                $stmt->execute([$lang, $_SESSION['user_id']]);
            } catch (Exception $e) {
                // Silently continue
            }
        }
        return $lang;
    }

    // Check Session
    if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], $supported)) {
        return $_SESSION['lang'];
    }

    // Check Cookie
    if (!empty($_COOKIE['obu_lang']) && in_array($_COOKIE['obu_lang'], $supported)) {
        $_SESSION['lang'] = $_COOKIE['obu_lang'];
        return $_COOKIE['obu_lang'];
    }

    // Check User preference from session user data
    if (!empty($_SESSION['user']['preferred_language']) && in_array($_SESSION['user']['preferred_language'], $supported)) {
        $_SESSION['lang'] = $_SESSION['user']['preferred_language'];
        return $_SESSION['lang'];
    }

    // Default language
    $_SESSION['lang'] = DEFAULT_LANGUAGE;
    return DEFAULT_LANGUAGE;
}

// Global translation array storage
$GLOBALS['OBU_TRANSLATIONS'] = [];

function load_translations(string $lang): array {
    $file = BASE_DIR . "/languages/{$lang}.php";
    if (file_exists($file)) {
        return require $file;
    }
    // Fallback to English
    $fallbackFile = BASE_DIR . "/languages/en.php";
    if (file_exists($fallbackFile)) {
        return require $fallbackFile;
    }
    return [];
}

// Current active language
$currentLang = init_language();
$GLOBALS['OBU_TRANSLATIONS'] = load_translations($currentLang);

/**
 * Translate a key into the active language
 */
function t(string $key, ?string $default = null): string {
    global $OBU_TRANSLATIONS;
    if (isset($OBU_TRANSLATIONS[$key])) {
        return $OBU_TRANSLATIONS[$key];
    }
    return $default ?? $key;
}

/**
 * Get current active language code
 */
function current_lang(): string {
    return $_SESSION['lang'] ?? DEFAULT_LANGUAGE;
}

/**
 * Generate URL with language parameter preserved
 */
function get_lang_url(string $code): string {
    $params = $_GET;
    $params['lang'] = $code;
    return '?' . http_build_query($params);
}

/**
 * Generate URL with theme parameter preserved
 */
function get_theme_url(string $theme): string {
    $params = $_GET;
    $params['theme'] = $theme;
    return '?' . http_build_query($params);
}

/**
 * Get localized field (e.g., category name_en, name_am, name_om)
 */
function localized(array $item, string $fieldPrefix = 'name'): string {
    $lang = current_lang();
    $field = "{$fieldPrefix}_{$lang}";
    if (!empty($item[$field])) {
        return $item[$field];
    }
    // Fallback to english
    $fallback = "{$fieldPrefix}_en";
    return $item[$fallback] ?? ($item['name'] ?? '');
}

/**
 * Initialize or switch theme (light, bright, dark)
 */
function init_theme(): string {
    $supported = SUPPORTED_THEMES;
    
    // Check GET parameter (?theme=dark|bright|light)
    if (!empty($_GET['theme']) && in_array($_GET['theme'], $supported)) {
        $theme = $_GET['theme'];
        $_SESSION['theme'] = $theme;
        setcookie('obu_theme', $theme, time() + (86400 * 365), '/', '', false, true);

        if (!empty($_SESSION['user_id']) && db()) {
            try {
                $stmt = db()->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
                $stmt->execute([$theme, $_SESSION['user_id']]);
            } catch (Exception $e) {}
        }
        return $theme;
    }

    // Check Session
    if (!empty($_SESSION['theme']) && in_array($_SESSION['theme'], $supported)) {
        return $_SESSION['theme'];
    }

    // Check Cookie
    if (!empty($_COOKIE['obu_theme']) && in_array($_COOKIE['obu_theme'], $supported)) {
        $_SESSION['theme'] = $_COOKIE['obu_theme'];
        return $_COOKIE['obu_theme'];
    }

    // Check User profile
    if (!empty($_SESSION['user']['theme_preference']) && in_array($_SESSION['user']['theme_preference'], $supported)) {
        $_SESSION['theme'] = $_SESSION['user']['theme_preference'];
        return $_SESSION['theme'];
    }

    $_SESSION['theme'] = DEFAULT_THEME;
    return DEFAULT_THEME;
}

/**
 * Get current active theme
 */
function current_theme(): string {
    return init_theme();
}

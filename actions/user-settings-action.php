<?php
/**
 * OBU Student Marketplace - User Profile & Settings Actions
 */
require_once __DIR__ . '/../config/app.php';
require_login();

$action = clean_input($_REQUEST['action'] ?? '');
$currentUser = current_user();
$userId = (int)$currentUser['id'];

if (!db()) {
    set_flash('danger', t('error_database'));
    header('Location: ' . base_url('student/settings.php'));
    exit;
}

// -------------------------------------------------------------
// 1. UPDATE PROFILE & AVATAR
// -------------------------------------------------------------
if ($action === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $fullName = clean_input($_POST['full_name'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $department = clean_input($_POST['department'] ?? '');
    $yearOfStudy = clean_input($_POST['year_of_study'] ?? '');

    if (empty($fullName) || empty($phone) || empty($department) || empty($yearOfStudy)) {
        set_flash('danger', 'Please fill out all required profile fields.');
        header('Location: ' . base_url('student/edit-profile.php'));
        exit;
    }

    if (!validate_phone($phone)) {
        set_flash('danger', 'Please enter a valid phone number.');
        header('Location: ' . base_url('student/edit-profile.php'));
        exit;
    }

    $profileImageName = $currentUser['profile_image'];

    // Handle avatar upload if provided
    if (!empty($_FILES['profile_image']) && !empty($_FILES['profile_image']['name'])) {
        $validation = validate_image_upload($_FILES['profile_image']);
        if (!$validation['valid']) {
            set_flash('danger', $validation['error']);
            header('Location: ' . base_url('student/edit-profile.php'));
            exit;
        }

        $ext = $validation['ext'];
        $newAvatarName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $dest = PROFILES_UPLOAD_DIR . DIRECTORY_SEPARATOR . $newAvatarName;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dest)) {
            // Delete old avatar if present
            if (!empty($profileImageName) && file_exists(PROFILES_UPLOAD_DIR . DIRECTORY_SEPARATOR . $profileImageName)) {
                @unlink(PROFILES_UPLOAD_DIR . DIRECTORY_SEPARATOR . $profileImageName);
            }
            $profileImageName = $newAvatarName;
        }
    }

    try {
        $stmt = db()->prepare("
            UPDATE users 
            SET full_name = ?, phone = ?, department = ?, year_of_study = ?, profile_image = ?
            WHERE id = ?
        ");
        $stmt->execute([$fullName, $phone, $department, $yearOfStudy, $profileImageName, $userId]);

        // Refresh session
        $_SESSION['user']['full_name'] = $fullName;
        $_SESSION['user']['phone'] = $phone;
        $_SESSION['user']['department'] = $department;
        $_SESSION['user']['year_of_study'] = $yearOfStudy;
        $_SESSION['user']['profile_image'] = $profileImageName;

        log_activity('PROFILE_UPDATE', 'Updated student profile information', $userId);
        set_flash('success', t('msg_profile_updated'));
        header('Location: ' . base_url('student/profile.php'));
        exit;

    } catch (Exception $e) {
        set_flash('danger', 'Error updating profile: ' . $e->getMessage());
        header('Location: ' . base_url('student/edit-profile.php'));
        exit;
    }
}

// -------------------------------------------------------------
// 2. CHANGE PASSWORD
// -------------------------------------------------------------
if ($action === 'change_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $currentPwd = $_POST['current_password'] ?? '';
    $newPwd = $_POST['new_password'] ?? '';
    $confirmPwd = $_POST['confirm_new_password'] ?? '';

    if (empty($currentPwd) || empty($newPwd) || empty($confirmPwd)) {
        set_flash('danger', 'All password fields are required.');
        header('Location: ' . base_url('student/settings.php'));
        exit;
    }

    if (!password_verify($currentPwd, $currentUser['password'])) {
        set_flash('danger', 'Current password is incorrect.');
        header('Location: ' . base_url('student/settings.php'));
        exit;
    }

    if (strlen($newPwd) < 8) {
        set_flash('danger', t('error_password_length'));
        header('Location: ' . base_url('student/settings.php'));
        exit;
    }

    if ($newPwd !== $confirmPwd) {
        set_flash('danger', t('error_password_mismatch'));
        header('Location: ' . base_url('student/settings.php'));
        exit;
    }

    try {
        $newHash = password_hash($newPwd, PASSWORD_BCRYPT);
        $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newHash, $userId]);
        $_SESSION['user']['password'] = $newHash;

        log_activity('PASSWORD_CHANGE', 'User changed account password', $userId);
        set_flash('success', t('msg_password_changed'));
        header('Location: ' . base_url('student/settings.php'));
        exit;

    } catch (Exception $e) {
        set_flash('danger', 'Failed to update password.');
        header('Location: ' . base_url('student/settings.php'));
        exit;
    }
}

// -------------------------------------------------------------
// 3. SET THEME VIA AJAX OR FORM (Light, Bright, Dark)
// -------------------------------------------------------------
if ($action === 'set_theme') {
    $theme = clean_input($_REQUEST['theme'] ?? 'bright');
    if (in_array($theme, SUPPORTED_THEMES)) {
        $_SESSION['theme'] = $theme;
        setcookie('obu_theme', $theme, time() + (86400 * 365), '/', '', false, true);
        try {
            $stmt = db()->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
            $stmt->execute([$theme, $userId]);
        } catch (Exception $e) {}
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => true, 'theme' => $theme]);
        exit;
    }

    set_flash('success', 'Visual theme updated to ' . ucfirst($theme) . ' Mode.');
    $back = $_SERVER['HTTP_REFERER'] ?? base_url('student/settings.php');
    header('Location: ' . $back);
    exit;
}

// -------------------------------------------------------------
// 4. SET LANGUAGE VIA FORM
// -------------------------------------------------------------
if ($action === 'set_language' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $lang = clean_input($_POST['preferred_language'] ?? 'en');
    if (array_key_exists($lang, SUPPORTED_LANGUAGES)) {
        $_SESSION['lang'] = $lang;
        setcookie('obu_lang', $lang, time() + (86400 * 365), '/', '', false, true);
        try {
            $stmt = db()->prepare("UPDATE users SET preferred_language = ? WHERE id = ?");
            $stmt->execute([$lang, $userId]);
        } catch (Exception $e) {}
        set_flash('success', 'Interface language updated.');
    }
    header('Location: ' . base_url('student/settings.php'));
    exit;
}

header('Location: ' . base_url('student/settings.php'));
exit;

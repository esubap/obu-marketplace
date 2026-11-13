<?php
/**
 * OBU Student Marketplace - Registration Action
 */
require_once __DIR__ . '/../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('register.php'));
    exit;
}

require_csrf();

$fullName = clean_input($_POST['full_name'] ?? '');
$studentId = strtoupper(clean_input($_POST['student_id'] ?? ''));
$university = clean_input($_POST['university'] ?? 'Oda Bultum University');
$department = clean_input($_POST['department'] ?? '');
$yearOfStudy = clean_input($_POST['year_of_study'] ?? '');
$phone = clean_input($_POST['phone'] ?? '');
$email = strtolower(clean_input($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Retain form input on failure
$_SESSION['register_form_data'] = [
    'full_name'     => $fullName,
    'student_id'    => $studentId,
    'department'    => $department,
    'year_of_study' => $yearOfStudy,
    'phone'         => $phone,
    'email'         => $email
];

// Validation
if (empty($fullName) || empty($studentId) || empty($department) || empty($yearOfStudy) || empty($phone) || empty($email) || empty($password)) {
    set_flash('danger', 'Please complete all required fields.');
    header('Location: ' . base_url('register.php'));
    exit;
}

if (!validate_email($email)) {
    set_flash('danger', 'Please enter a valid email address.');
    header('Location: ' . base_url('register.php'));
    exit;
}

if (!validate_student_id($studentId)) {
    set_flash('danger', 'Student ID contains invalid characters. Use letters, numbers, hyphens, and slashes.');
    header('Location: ' . base_url('register.php'));
    exit;
}

if (!validate_phone($phone)) {
    set_flash('danger', 'Please enter a valid Ethiopian mobile phone number (e.g., 0912345678 or +251912345678).');
    header('Location: ' . base_url('register.php'));
    exit;
}

if (strlen($password) < 8) {
    set_flash('danger', t('error_password_length'));
    header('Location: ' . base_url('register.php'));
    exit;
}

if ($password !== $confirmPassword) {
    set_flash('danger', t('error_password_mismatch'));
    header('Location: ' . base_url('register.php'));
    exit;
}

if (!db()) {
    set_flash('danger', t('error_database'));
    header('Location: ' . base_url('register.php'));
    exit;
}

try {
    // Check duplicate email
    $stmt = db()->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        set_flash('danger', t('error_duplicate_email'));
        header('Location: ' . base_url('register.php'));
        exit;
    }

    // Check duplicate student ID
    $stmt = db()->prepare("SELECT id FROM users WHERE student_id = ?");
    $stmt->execute([$studentId]);
    if ($stmt->fetch()) {
        set_flash('danger', t('error_duplicate_student_id'));
        header('Location: ' . base_url('register.php'));
        exit;
    }

    // Hash password with strong bcrypt
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // CRITICAL SECURITY ENFORCEMENT:
    // Strictly assign role = 'student' and status = 'active'. Never accept role from user request!
    $role = ROLE_STUDENT;
    $status = STATUS_ACTIVE;
    $lang = current_lang();
    $theme = current_theme();

    $stmt = db()->prepare("
        INSERT INTO users (full_name, student_id, university, department, year_of_study, email, phone, password, role, status, preferred_language, theme_preference)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $fullName,
        $studentId,
        $university,
        $department,
        $yearOfStudy,
        $email,
        $phone,
        $passwordHash,
        $role,
        $status,
        $lang,
        $theme
    ]);

    $newUserId = (int)db()->lastInsertId();

    // Send Welcome Notification
    create_notification(
        $newUserId,
        'Welcome to OBU Marketplace!',
        'Your student account has been created successfully. You can now browse listings, save favorites, or publish your own textbooks and gear.',
        'welcome',
        $newUserId,
        'student/dashboard.php'
    );

    log_activity('REGISTER', "New student registered: $fullName ($studentId, $email)", $newUserId);

    unset($_SESSION['register_form_data']);
    set_flash('success', t('msg_register_success'));
    header('Location: ' . base_url('login.php'));
    exit;

} catch (Exception $e) {
    set_flash('danger', 'Registration error: ' . $e->getMessage());
    header('Location: ' . base_url('register.php'));
    exit;
}

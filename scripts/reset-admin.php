<?php
/**
 * OBU Student Marketplace - CLI Admin Password Reset Script
 * Usage: php scripts/reset-admin.php [new_password]
 */
require_once __DIR__ . '/../config/app.php';

if (php_sapi_name() !== 'cli') {
    die("This script can only be executed from the command line.\n");
}

$newPassword = $argv[1] ?? 'Password123!';

if (strlen($newPassword) < 6) {
    die("Error: Password must be at least 6 characters.\n");
}

if (!db()) {
    die("Error: Cannot connect to database. Make sure MySQL is running in XAMPP.\n");
}

$hash = password_hash($newPassword, PASSWORD_BCRYPT);
$stmt = db()->prepare("UPDATE users SET password = ?, status = 'active' WHERE role = 'admin'");
$stmt->execute([$hash]);
$rows = $stmt->rowCount();

if ($rows > 0) {
    echo "SUCCESS: Updated password for {$rows} administrator account(s) to '{$newPassword}'.\n";
    echo "You can now log in at http://localhost/obu-marketplace/login.php with admin@obu.edu.et\n";
} else {
    // If no admin user existed, insert one
    $stmt = db()->prepare("
        INSERT INTO users (full_name, student_id, university, department, year_of_study, email, phone, password, role, status, preferred_language, theme_preference)
        VALUES ('OBU Administrator', 'ADMIN-001', 'Oda Bultum University', 'ICT Directorate', 'Staff', 'admin@obu.edu.et', '+251 91 123 4567', ?, 'admin', 'active', 'en', 'bright')
    ");
    $stmt->execute([$hash]);
    echo "SUCCESS: Created new administrator account 'admin@obu.edu.et' with password '{$newPassword}'.\n";
}

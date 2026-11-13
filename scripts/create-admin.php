<?php
/**
 * OBU Student Marketplace - Secure Administrator CLI Setup Script
 * Usage: php scripts/create-admin.php
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line interface (CLI).\n");
}

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

echo "=========================================================\n";
echo "   OBU STUDENT MARKETPLACE - ADMIN INITIALIZATION\n";
echo "   Oda Bultum University\n";
echo "=========================================================\n\n";

if (!Database::isConnected()) {
    echo "ERROR: Unable to connect to MySQL database.\n";
    echo "Details: " . Database::getError() . "\n";
    echo "Please ensure MySQL server is running in XAMPP and database 'university_marketplace' exists.\n";
    exit(1);
}

// Prompt for administrator details or use defaults
echo "Enter Administrator Full Name [OBU Admin]: ";
$name = trim(fgets(STDIN)) ?: "OBU Administrator";

echo "Enter Administrator Email [admin@obu.edu.et]: ";
$email = strtolower(trim(fgets(STDIN))) ?: "admin@obu.edu.et";

echo "Enter Admin Staff/Student ID [ADMIN-001]: ";
$studentId = strtoupper(trim(fgets(STDIN))) ?: "ADMIN-001";

echo "Enter Admin Password [Password123!]: ";
$pwd = trim(fgets(STDIN)) ?: "Password123!";

if (strlen($pwd) < 8) {
    echo "ERROR: Password must be at least 8 characters.\n";
    exit(1);
}

$hash = password_hash($pwd, PASSWORD_BCRYPT);

try {
    $db = Database::getConnection();

    // Check if user already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update to admin
        $stmtUp = $db->prepare("
            UPDATE users 
            SET full_name = ?, student_id = ?, password = ?, role = 'admin', status = 'active'
            WHERE id = ?
        ");
        $stmtUp->execute([$name, $studentId, $hash, $existing['id']]);
        echo "\nSUCCESS: Existing account for '{$email}' has been updated to Administrator!\n";
    } else {
        // Insert new admin
        $stmtIns = $db->prepare("
            INSERT INTO users (full_name, student_id, university, department, year_of_study, email, phone, password, role, status, preferred_language, theme_preference)
            VALUES (?, ?, 'Oda Bultum University', 'ICT Directorate', 'Staff', ?, '+251 91 123 4567', ?, 'admin', 'active', 'en', 'bright')
        ");
        $stmtIns->execute([$name, $studentId, $email, $hash]);
        echo "\nSUCCESS: Administrator account '{$email}' created successfully!\n";
    }

    echo "Login Email: $email\n";
    echo "Password:    $pwd\n";
    echo "Role:        Administrator\n";
    echo "=========================================================\n";
    exit(0);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

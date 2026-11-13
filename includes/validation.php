<?php
/**
 * OBU Student Marketplace - Input Validation and Sanitization
 */

/**
 * Escape output for HTML (XSS prevention)
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Clean and sanitize a string input
 */
function clean_input(?string $data): string {
    if ($data === null) return '';
    return trim(strip_tags($data));
}

/**
 * Validate email address
 */
function validate_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate Ethiopian phone number formats (e.g. 0912345678, 0712345678, +251912345678, +251712345678)
 */
function validate_phone(string $phone): bool {
    $clean = preg_replace('/[\s\-]/', '', $phone);
    return preg_match('/^(\+251[79]\d{8}|0[79]\d{8})$/', $clean) === 1;
}

/**
 * Validate student ID format (allow standard formats e.g. OBU/0452/14 or letters/numbers/slashes/hyphens)
 */
function validate_student_id(string $id): bool {
    $clean = trim($id);
    return strlen($clean) >= 3 && strlen($clean) <= 40 && preg_match('/^[a-zA-Z0-9\/\-_]+$/', $clean) === 1;
}

/**
 * Validate password strength (at least 8 chars, letters and numbers)
 */
function validate_password(string $password): bool {
    if (strlen($password) < 8) {
        return false;
    }
    $hasLetter = preg_match('/[a-zA-Z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    return ($hasLetter && $hasNumber);
}

/**
 * Validate price
 */
function validate_price($price): bool {
    return is_numeric($price) && floatval($price) >= 0 && floatval($price) <= 10000000;
}

/**
 * Validate image upload securely
 * Checks: upload error, file size, extension, real MIME type with finfo / getimagesize
 */
function validate_image_upload(array $file): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['valid' => false, 'error' => 'Invalid file parameter.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['valid' => false, 'error' => 'Uploaded file exceeds the maximum allowed size (5MB).'];
            case UPLOAD_ERR_NO_FILE:
                return ['valid' => false, 'error' => 'No file uploaded.'];
            default:
                return ['valid' => false, 'error' => 'File upload error code: ' . $file['error']];
        }
    }

    if ($file['size'] > MAX_FILE_SIZE_BYTES) {
        return ['valid' => false, 'error' => 'Image file is too large. Max limit is 5MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXTENSIONS)) {
        return ['valid' => false, 'error' => 'Invalid file extension. Only JPG, PNG, and WebP are allowed.'];
    }

    // Verify real MIME type using finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_MIMES)) {
        return ['valid' => false, 'error' => 'Invalid file format. Actual MIME type not allowed: ' . $mime];
    }

    // Verify real image dimensions
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['valid' => false, 'error' => 'File is not a valid recognized image.'];
    }

    return ['valid' => true, 'ext' => $ext, 'mime' => $mime];
}

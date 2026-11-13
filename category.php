<?php
/**
 * OBU Student Marketplace - Category View
 */
require_once __DIR__ . '/config/app.php';

$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category = null;

if (db() && $categoryId > 0) {
    try {
        $stmt = db()->prepare("SELECT * FROM categories WHERE id = ? AND status = 'active'");
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch();
    } catch (Exception $e) {}
}

if (!$category) {
    header('Location: ' . base_url('products.php'));
    exit;
}

$catName = localized($category, 'name');
$pageTitle = $catName;

// Forward to products.php with category filter
header("Location: " . base_url("products.php?category=" . $categoryId));
exit;

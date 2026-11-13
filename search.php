<?php
/**
 * OBU Student Marketplace - Search Handler
 */
require_once __DIR__ . '/config/app.php';

$query = clean_input($_GET['q'] ?? '');
$category = !empty($_GET['category']) ? (int)$_GET['category'] : '';

$params = [];
if (!empty($query)) $params['q'] = $query;
if (!empty($category)) $params['category'] = $category;

header('Location: ' . base_url('products.php?' . http_build_query($params)));
exit;

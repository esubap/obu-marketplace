<?php
/**
 * OBU Student Marketplace - Product CRUD Action Handler (Module 4)
 */
require_once __DIR__ . '/../config/app.php';
require_login();

$action = clean_input($_REQUEST['action'] ?? '');
$currentUser = current_user();
$userId = (int)$currentUser['id'];

if (!db()) {
    set_flash('danger', t('error_database'));
    header('Location: ' . base_url('student/my-products.php'));
    exit;
}

// -------------------------------------------------------------
// ACTION: CREATE PRODUCT
// -------------------------------------------------------------
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $title = clean_input($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $price = $_POST['price'] ?? 0;
    $condition = clean_input($_POST['condition'] ?? '');
    $location = clean_input($_POST['location'] ?? '');
    $contactPref = clean_input($_POST['contact_preference'] ?? 'internal_message');
    $description = clean_input($_POST['description'] ?? '');

    // Validation
    if (empty($title) || $categoryId <= 0 || !validate_price($price) || empty($condition) || empty($location) || empty($description)) {
        set_flash('danger', 'Please complete all required listing fields with valid values.');
        header('Location: ' . base_url('student/add-product.php'));
        exit;
    }

    if (!array_key_exists($condition, PRODUCT_CONDITIONS)) {
        set_flash('danger', 'Invalid item condition selected.');
        header('Location: ' . base_url('student/add-product.php'));
        exit;
    }

    // Process Image Uploads
    $uploadedImages = [];
    if (!empty($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $fileCount = count($_FILES['images']['name']);
        if ($fileCount > 4) {
            set_flash('danger', 'You can upload a maximum of 4 images per listing.');
            header('Location: ' . base_url('student/add-product.php'));
            exit;
        }

        for ($i = 0; $i < $fileCount; $i++) {
            $file = [
                'name'     => $_FILES['images']['name'][$i],
                'type'     => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error'    => $_FILES['images']['error'][$i],
                'size'     => $_FILES['images']['size'][$i]
            ];

            if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;

            $validation = validate_image_upload($file);
            if (!$validation['valid']) {
                set_flash('danger', $validation['error']);
                header('Location: ' . base_url('student/add-product.php'));
                exit;
            }

            // Generate safe randomized filename
            $ext = $validation['ext'];
            $newFileName = 'prod_' . bin2hex(random_bytes(10)) . '_' . time() . '.' . $ext;
            $destination = PRODUCTS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $uploadedImages[] = $newFileName;
            }
        }
    }

    if (empty($uploadedImages)) {
        set_flash('danger', 'Please upload at least one valid image of your item.');
        header('Location: ' . base_url('student/add-product.php'));
        exit;
    }

    try {
        db()->beginTransaction();

        $stmt = db()->prepare("
            INSERT INTO products (user_id, category_id, title, description, price, `condition`, university, location, contact_preference, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Oda Bultum University', ?, ?, 'active')
        ");
        $stmt->execute([
            $userId,
            $categoryId,
            $title,
            $description,
            floatval($price),
            $condition,
            $location,
            $contactPref
        ]);

        $newProductId = (int)db()->lastInsertId();

        // Save image records
        $stmtImg = db()->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)");
        foreach ($uploadedImages as $idx => $imgName) {
            $isPrimary = ($idx === 0) ? 1 : 0;
            $stmtImg->execute([$newProductId, $imgName, $isPrimary]);
        }

        db()->commit();

        log_activity('PRODUCT_CREATE', "Published product: $title (ID: $newProductId)", $userId);
        set_flash('success', t('msg_product_created'));
        header('Location: ' . base_url('student/my-products.php'));
        exit;

    } catch (Exception $e) {
        if (db()->inTransaction()) db()->rollBack();
        set_flash('danger', 'Failed to publish listing: ' . $e->getMessage());
        header('Location: ' . base_url('student/add-product.php'));
        exit;
    }
}

// -------------------------------------------------------------
// ACTION: UPDATE PRODUCT
// -------------------------------------------------------------
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $productId = (int)($_POST['product_id'] ?? 0);
    $title = clean_input($_POST['title'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $price = $_POST['price'] ?? 0;
    $condition = clean_input($_POST['condition'] ?? '');
    $location = clean_input($_POST['location'] ?? '');
    $contactPref = clean_input($_POST['contact_preference'] ?? 'internal_message');
    $description = clean_input($_POST['description'] ?? '');
    $status = clean_input($_POST['status'] ?? 'active');

    // Strict ownership verification
    $stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $prod = $stmt->fetch();

    if (!$prod || ((int)$prod['user_id'] !== $userId && $currentUser['role'] !== ROLE_ADMIN)) {
        set_flash('danger', t('error_unauthorized'));
        header('Location: ' . base_url('student/my-products.php'));
        exit;
    }

    try {
        $stmtUp = db()->prepare("
            UPDATE products 
            SET title = ?, category_id = ?, price = ?, `condition` = ?, location = ?, contact_preference = ?, description = ?, status = ?
            WHERE id = ?
        ");
        $stmtUp->execute([
            $title,
            $categoryId,
            floatval($price),
            $condition,
            $location,
            $contactPref,
            $description,
            $status,
            $productId
        ]);

        // If extra images uploaded
        if (!empty($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $fileCount = count($_FILES['images']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                $file = [
                    'name'     => $_FILES['images']['name'][$i],
                    'type'     => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error'    => $_FILES['images']['error'][$i],
                    'size'     => $_FILES['images']['size'][$i]
                ];
                if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;

                $validation = validate_image_upload($file);
                if ($validation['valid']) {
                    $ext = $validation['ext'];
                    $newFileName = 'prod_' . bin2hex(random_bytes(10)) . '_' . time() . '.' . $ext;
                    $dest = PRODUCTS_UPLOAD_DIR . DIRECTORY_SEPARATOR . $newFileName;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $stmtImg = db()->prepare("INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, 0)");
                        $stmtImg->execute([$productId, $newFileName]);
                    }
                }
            }
        }

        log_activity('PRODUCT_UPDATE', "Updated product: $title (ID: $productId)", $userId);
        set_flash('success', t('msg_product_updated'));
        header('Location: ' . base_url('student/my-products.php'));
        exit;

    } catch (Exception $e) {
        set_flash('danger', 'Error updating product: ' . $e->getMessage());
        header('Location: ' . base_url('student/edit-product.php?id=' . $productId));
        exit;
    }
}

// -------------------------------------------------------------
// ACTION: MARK SOLD
// -------------------------------------------------------------
if ($action === 'mark_sold') {
    require_csrf();
    $productId = (int)($_GET['id'] ?? 0);

    $stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $prod = $stmt->fetch();

    if ($prod && ((int)$prod['user_id'] === $userId || $currentUser['role'] === ROLE_ADMIN)) {
        $stmtUp = db()->prepare("UPDATE products SET status = 'sold' WHERE id = ?");
        $stmtUp->execute([$productId]);
        log_activity('PRODUCT_SOLD', "Marked product as sold (ID: $productId)", $userId);
        set_flash('success', t('msg_marked_sold'));
    } else {
        set_flash('danger', t('error_unauthorized'));
    }

    $back = $_SERVER['HTTP_REFERER'] ?? base_url('student/my-products.php');
    header('Location: ' . $back);
    exit;
}

// -------------------------------------------------------------
// ACTION: MARK ACTIVE
// -------------------------------------------------------------
if ($action === 'mark_active') {
    require_csrf();
    $productId = (int)($_GET['id'] ?? 0);

    $stmt = db()->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $prod = $stmt->fetch();

    if ($prod && ((int)$prod['user_id'] === $userId || $currentUser['role'] === ROLE_ADMIN)) {
        $stmtUp = db()->prepare("UPDATE products SET status = 'active' WHERE id = ?");
        $stmtUp->execute([$productId]);
        set_flash('success', 'Product listing is now active.');
    } else {
        set_flash('danger', t('error_unauthorized'));
    }

    $back = $_SERVER['HTTP_REFERER'] ?? base_url('student/my-products.php');
    header('Location: ' . $back);
    exit;
}

// Default redirect
header('Location: ' . base_url('student/my-products.php'));
exit;

<?php
/**
 * OBU Student Marketplace - Custom 404 Error Page
 */
require_once __DIR__ . '/config/app.php';

http_response_code(404);
$pageTitle = t('error_404_title');

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5 text-center my-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card border-0 rounded-4 shadow-sm p-5 bg-surface">
                <div class="text-danger mb-3" style="font-size: 4rem;">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h1 class="display-5 fw-bold mb-2">404</h1>
                <h3 class="fw-bold mb-3"><?= t('error_404_title') ?></h3>
                <p class="text-secondary mb-4"><?= t('error_404_desc') ?></p>

                <div class="d-flex justify-content-center gap-3">
                    <a href="<?= base_url('index.php') ?>" class="btn btn-obu px-4 py-2">
                        <i class="bi bi-house-door me-1"></i> <?= t('go_home') ?>
                    </a>
                    <a href="<?= base_url('products.php') ?>" class="btn btn-obu-outline px-4 py-2">
                        <i class="bi bi-grid-fill me-1"></i> <?= t('nav_browse') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

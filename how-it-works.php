<?php
/**
 * OBU Student Marketplace - How It Works
 */
require_once __DIR__ . '/config/app.php';

$pageTitle = t('nav_how_it_works');
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5" style="background: var(--hero-gradient); color: #FFFFFF;">
    <div class="container text-center py-4">
        <h1 class="fw-bold mb-2"><?= t('how_works_title') ?></h1>
        <p class="lead opacity-90 mx-auto" style="max-width: 680px;"><?= e(t('sub_tagline')) ?></p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4 justify-content-center">
        <!-- Step 1 -->
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-surface">
                <div class="row align-items-center g-4">
                    <div class="col-md-2 text-center">
                        <div class="stat-card-icon stat-icon-blue mx-auto" style="width: 72px; height: 72px; font-size: 2rem;">
                            <i class="bi bi-person-badge"></i>
                        </div>
                    </div>
                    <div class="col-md-10">
                        <h4 class="fw-bold text-primary mb-2"><?= t('how_works_step1_title') ?></h4>
                        <p class="text-secondary mb-2"><?= t('how_works_step1_desc') ?></p>
                        <p class="small text-muted mb-0">
                            Required credentials: Full Name, Student ID (e.g. OBU/0452/14), Department, and valid contact phone number.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-surface">
                <div class="row align-items-center g-4">
                    <div class="col-md-2 text-center">
                        <div class="stat-card-icon stat-icon-teal mx-auto" style="width: 72px; height: 72px; font-size: 2rem;">
                            <i class="bi bi-camera"></i>
                        </div>
                    </div>
                    <div class="col-md-10">
                        <h4 class="fw-bold text-success mb-2"><?= t('how_works_step2_title') ?></h4>
                        <p class="text-secondary mb-2"><?= t('how_works_step2_desc') ?></p>
                        <p class="small text-muted mb-0">
                            Upload clear photos, select category (Books, Electronics, Dorm Gear, Clothes), set realistic student-friendly price in Ethiopian Birr (ETB), and specify campus meeting point.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-surface">
                <div class="row align-items-center g-4">
                    <div class="col-md-2 text-center">
                        <div class="stat-card-icon stat-icon-amber mx-auto" style="width: 72px; height: 72px; font-size: 2rem;">
                            <i class="bi bi-chat-quote"></i>
                        </div>
                    </div>
                    <div class="col-md-10">
                        <h4 class="fw-bold text-warning mb-2"><?= t('how_works_step3_title') ?></h4>
                        <p class="text-secondary mb-2"><?= t('how_works_step3_desc') ?></p>
                        <p class="small text-muted mb-0">
                            Buyers can send instant messages or submit official purchase requests. Sellers can accept or reject requests with real-time in-app notifications.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-surface">
                <div class="row align-items-center g-4">
                    <div class="col-md-2 text-center">
                        <div class="stat-card-icon stat-icon-purple mx-auto" style="width: 72px; height: 72px; font-size: 2rem;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                    </div>
                    <div class="col-md-10">
                        <h4 class="fw-bold text-purple mb-2"><?= t('how_works_step4_title') ?></h4>
                        <p class="text-secondary mb-2"><?= t('how_works_step4_desc') ?></p>
                        <p class="small text-muted mb-0">
                            Recommended meeting spots: Main Campus Library Entrance, ICT Center Lounge, Block Cafeteria. Always test items physically before handing cash.
                        </p>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <a href="<?= base_url('register.php') ?>" class="btn btn-obu btn-lg px-5">
                    <i class="bi bi-person-plus-fill me-2"></i> Join OBU Marketplace Today
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

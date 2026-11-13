<?php
/**
 * OBU Student Marketplace - About Us
 */
require_once __DIR__ . '/config/app.php';

$pageTitle = t('nav_about');
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5" style="background: var(--hero-gradient); color: #FFFFFF;">
    <div class="container text-center py-4">
        <h1 class="fw-bold mb-2"><?= t('nav_about') ?></h1>
        <p class="lead opacity-90 mx-auto" style="max-width: 680px;">Centralized Community Commerce Platform for Oda Bultum University</p>
    </div>
</div>

<div class="container py-5">
    <div class="row align-items-center g-5 mb-5">
        <div class="col-lg-6">
            <span class="badge bg-primary text-white mb-2">OUR MISSION</span>
            <h2 class="fw-bold mb-3">Empowering Students Through Affordable Campus Commerce</h2>
            <p class="text-secondary leading-relaxed">
                <strong>OBU Student Marketplace</strong> was designed and developed specifically to address the real-world challenges faced by students at Oda Bultum University in Chiro, West Hararghe, Ethiopia.
            </p>
            <p class="text-secondary leading-relaxed">
                Every semester, thousands of students need affordable textbooks, calculators, engineering drafting kits, lab coats, and laptops. Concurrently, graduating seniors have high-quality materials that sit unused in dorm rooms.
            </p>
            <p class="text-secondary leading-relaxed">
                By providing a centralized, verified, multilingual marketplace supporting English, Amharic, and Afaan Oromo, students can trade directly, affordably, and safely within our university community.
            </p>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface">
                <h4 class="fw-bold mb-3 text-primary"><i class="bi bi-award-fill me-2"></i> Key Platform Pillars</h4>
                <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                    <li class="d-flex gap-3">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <div>
                            <strong>100% Student Verified</strong>
                            <p class="small text-muted mb-0">Only registered students and staff with verified university credentials can publish and trade.</p>
                        </div>
                    </li>
                    <li class="d-flex gap-3">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <div>
                            <strong>Multi-Lingual Inclusion</strong>
                            <p class="small text-muted mb-0">Complete native translations in English, Amharic (አማርኛ), and Afaan Oromoo.</p>
                        </div>
                    </li>
                    <li class="d-flex gap-3">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <div>
                            <strong>Zero Broker Fees</strong>
                            <p class="small text-muted mb-0">Free peer-to-peer marketplace without any transaction commissions or hidden charges.</p>
                        </div>
                    </li>
                    <li class="d-flex gap-3">
                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                        <div>
                            <strong>Modern Responsive Design</strong>
                            <p class="small text-muted mb-0">Full support for Light, Bright, and Dark visual themes across all Android phones and laptops.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

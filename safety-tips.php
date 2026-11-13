<?php
/**
 * OBU Student Marketplace - Campus Safety Tips
 */
require_once __DIR__ . '/config/app.php';

$pageTitle = t('nav_safety_tips');
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5" style="background: var(--hero-gradient); color: #FFFFFF;">
    <div class="container text-center py-4">
        <h1 class="fw-bold mb-2"><?= t('safety_tips_title') ?></h1>
        <p class="lead opacity-90 mx-auto" style="max-width: 680px;">Guidelines for safe peer-to-peer exchanges across Oda Bultum University campuses</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-surface mb-4">
                <div class="alert alert-info d-flex align-items-center gap-3 mb-4">
                    <i class="bi bi-info-circle-fill fs-3"></i>
                    <div>
                        <strong>Important Security Reminder:</strong> OBU Student Marketplace facilitates community connections. Transactions are completed in person. Never pay advance deposits or mobile money transfers before inspecting goods.
                    </div>
                </div>

                <h4 class="fw-bold mb-3"><i class="bi bi-geo-alt-fill text-danger me-2"></i> Safe Meeting Spots on Campus</h4>
                <p class="text-secondary">Always arrange to meet within designated campus locations during daytime hours:</p>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-surface-alt border">
                            <h6 class="fw-bold mb-1"><i class="bi bi-book text-primary me-2"></i> Main Campus Library Plaza</h6>
                            <p class="small text-muted mb-0">High visibility, security guards nearby, well-lit public area.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-surface-alt border">
                            <h6 class="fw-bold mb-1"><i class="bi bi-laptop text-success me-2"></i> ICT Laboratory Lounge</h6>
                            <p class="small text-muted mb-0">Ideal for testing laptops, chargers, flash drives, and calculators.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-surface-alt border">
                            <h6 class="fw-bold mb-1"><i class="bi bi-cup-hot text-amber me-2"></i> Student Cafeteria Foyer</h6>
                            <p class="small text-muted mb-0">Convenient daytime meetup spot between lecture hours.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded-3 bg-surface-alt border">
                            <h6 class="fw-bold mb-1"><i class="bi bi-building text-info me-2"></i> Block Dorm Administration</h6>
                            <p class="small text-muted mb-0">For heavy dorm items like desks, study chairs, or mattresses.</p>
                        </div>
                    </div>
                </div>

                <h4 class="fw-bold mb-3"><i class="bi bi-shield-lock-fill text-primary me-2"></i> Top Safety Rules</h4>
                <div class="d-flex flex-column gap-3 mb-4">
                    <div class="d-flex gap-3 p-3 rounded-3 bg-surface-alt border">
                        <span class="badge bg-danger rounded-circle p-2 fs-6">1</span>
                        <div>
                            <strong>Never Send Money Before Inspecting</strong>
                            <p class="small text-muted mb-0">Do not transfer advance reservation fees via Telebirr, CBE Birr, or cash. Hand over payment only when the physical item is in your hands and matches description.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3 p-3 rounded-3 bg-surface-alt border">
                        <span class="badge bg-danger rounded-circle p-2 fs-6">2</span>
                        <div>
                            <strong>Test Electronics Thoroughly</strong>
                            <p class="small text-muted mb-0">Turn on laptops, plug in power banks, test smartphone SIM cards and cameras, and test calculators on campus power outlets before completing the deal.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3 p-3 rounded-3 bg-surface-alt border">
                        <span class="badge bg-danger rounded-circle p-2 fs-6">3</span>
                        <div>
                            <strong>Keep Communication on Platform</strong>
                            <p class="small text-muted mb-0">Using the internal marketplace messaging system protects you with a clear record of timestamps, agreed prices, and meetup terms.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3 p-3 rounded-3 bg-surface-alt border">
                        <span class="badge bg-danger rounded-circle p-2 fs-6">4</span>
                        <div>
                            <strong>Report Suspicious Listings Promptly</strong>
                            <p class="small text-muted mb-0">Use the "Report Listing" button on any suspicious item, stolen goods, or harassment. Campus moderators review reports swiftly.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * OBU Student Marketplace - Contact & Support
 */
require_once __DIR__ . '/config/app.php';

$pageTitle = 'Contact Campus Support';

$sentSuccess = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name = clean_input($_POST['name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $subject = clean_input($_POST['subject'] ?? '');
    $message = clean_input($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill out all required fields.';
    } elseif (!validate_email($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        log_activity('CONTACT_INQUIRY', "Inquiry from $name ($email): $subject", current_user_id());
        $sentSuccess = true;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5" style="background: var(--hero-gradient); color: #FFFFFF;">
    <div class="container text-center py-4">
        <h1 class="fw-bold mb-2">Campus Support & Inquiries</h1>
        <p class="lead opacity-90 mx-auto" style="max-width: 680px;">Have questions, feedback, or need moderation assistance? We are here to help.</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-5">
            <h3 class="fw-bold mb-3">Contact Information</h3>
            <p class="text-secondary mb-4">
                Reach out to the Oda Bultum University Student Affairs and ICT Directorate team.
            </p>

            <div class="d-flex flex-column gap-3 mb-4">
                <div class="d-flex gap-3 align-items-center p-3 rounded-3 bg-surface border">
                    <div class="stat-card-icon stat-icon-blue">
                        <i class="bi bi-geo-alt"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Campus Location</div>
                        <strong>Main Campus, Chiro, West Hararghe</strong>
                    </div>
                </div>

                <div class="d-flex gap-3 align-items-center p-3 rounded-3 bg-surface border">
                    <div class="stat-card-icon stat-icon-teal">
                        <i class="bi bi-envelope"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Support Email</div>
                        <strong><?= e(get_setting('contact_email', 'marketplace@obu.edu.et')) ?></strong>
                    </div>
                </div>

                <div class="d-flex gap-3 align-items-center p-3 rounded-3 bg-surface border">
                    <div class="stat-card-icon stat-icon-amber">
                        <i class="bi bi-telephone"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Support Desk Hotline</div>
                        <strong><?= e(get_setting('contact_phone', '+251 25 551 0352')) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-surface">
                <h4 class="fw-bold mb-3">Send Us a Message</h4>
                
                <?php if ($sentSuccess): ?>
                    <div class="alert alert-success d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <div>Thank you! Your message has been received. Our campus support team will follow up via email.</div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <div><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('contact.php') ?>" method="POST" class="needs-validation">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" value="<?= e($currentUser['full_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control" value="<?= e($currentUser['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Subject *</label>
                            <input type="text" name="subject" class="form-control" placeholder="Inquiry about..." required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message *</label>
                            <textarea name="message" class="form-control" rows="5" placeholder="How can we assist you?" required></textarea>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-obu px-4 py-2.5">
                                <i class="bi bi-send-fill me-2"></i> Submit Inquiry
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

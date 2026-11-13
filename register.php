<?php
/**
 * OBU Student Marketplace - Student Registration (Module 2)
 */
require_once __DIR__ . '/config/app.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . base_url('student/dashboard.php'));
    exit;
}

$pageTitle = t('auth_register_title');
$extraCss = ['auth.css'];

$formData = $_SESSION['register_form_data'] ?? [];
unset($_SESSION['register_form_data']);

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card auth-card-wide">
        <div class="auth-header">
            <div class="d-inline-flex align-items-center justify-content-center p-2 rounded-circle mb-2" style="background: rgba(255, 255, 255, 0.2);">
                <i class="bi bi-person-plus-fill fs-3"></i>
            </div>
            <h3><?= t('auth_register_title') ?></h3>
            <p><?= t('auth_register_sub') ?> — <?= e(t('university_name')) ?></p>
        </div>

        <div class="auth-body">
            <form id="registrationForm" action="<?= base_url('actions/register-action.php') ?>" method="POST" class="needs-validation" novalidate>
                <?= csrf_field() ?>

                <div class="row g-3">
                    <!-- Full Name -->
                    <div class="col-md-6">
                        <label for="reg_fullname" class="form-label"><?= t('auth_full_name') ?> *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-surface-alt"><i class="bi bi-person"></i></span>
                            <input type="text" name="full_name" id="reg_fullname" class="form-control" placeholder="Tolosa Bekele" value="<?= e($formData['full_name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Student ID -->
                    <div class="col-md-6">
                        <label for="reg_student_id" class="form-label"><?= t('auth_student_id') ?> *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-surface-alt"><i class="bi bi-card-text"></i></span>
                            <input type="text" name="student_id" id="reg_student_id" class="form-control" placeholder="<?= t('auth_student_id_placeholder') ?>" value="<?= e($formData['student_id'] ?? '') ?>" required>
                        </div>
                        <div class="form-text small">Official OBU student ID number.</div>
                    </div>

                    <!-- University (Pre-filled for OBU) -->
                    <div class="col-md-6">
                        <label for="reg_university" class="form-label"><?= t('auth_university') ?> *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-surface-alt"><i class="bi bi-building"></i></span>
                            <input type="text" name="university" id="reg_university" class="form-control" value="Oda Bultum University" readonly required>
                        </div>
                    </div>

                    <!-- Department -->
                    <div class="col-md-6">
                        <label for="reg_department" class="form-label"><?= t('auth_department') ?> *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-surface-alt"><i class="bi bi-mortarboard"></i></span>
                            <input type="text" name="department" id="reg_department" class="form-control" placeholder="<?= t('auth_department_placeholder') ?>" value="<?= e($formData['department'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Year of Study -->
                    <div class="col-md-6">
                        <label for="reg_year" class="form-label"><?= t('auth_year') ?> *</label>
                        <select name="year_of_study" id="reg_year" class="form-select" required>
                            <option value=""><?= t('auth_year') ?>...</option>
                            <option value="1st Year" <?= ($formData['year_of_study'] ?? '') === '1st Year' ? 'selected' : '' ?>><?= t('auth_year_1') ?></option>
                            <option value="2nd Year" <?= ($formData['year_of_study'] ?? '') === '2nd Year' ? 'selected' : '' ?>><?= t('auth_year_2') ?></option>
                            <option value="3rd Year" <?= ($formData['year_of_study'] ?? '') === '3rd Year' ? 'selected' : '' ?>><?= t('auth_year_3') ?></option>
                            <option value="4th Year" <?= ($formData['year_of_study'] ?? '') === '4th Year' ? 'selected' : '' ?>><?= t('auth_year_4') ?></option>
                            <option value="5th Year" <?= ($formData['year_of_study'] ?? '') === '5th Year' ? 'selected' : '' ?>><?= t('auth_year_5') ?></option>
                            <option value="Postgraduate" <?= ($formData['year_of_study'] ?? '') === 'Postgraduate' ? 'selected' : '' ?>><?= t('auth_year_pg') ?></option>
                            <option value="Staff" <?= ($formData['year_of_study'] ?? '') === 'Staff' ? 'selected' : '' ?>><?= t('auth_year_staff') ?></option>
                        </select>
                    </div>

                    <!-- Phone Number -->
                    <div class="col-md-6">
                        <label for="reg_phone" class="form-label"><?= t('auth_phone') ?> *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-surface-alt"><i class="bi bi-phone"></i></span>
                            <input type="tel" name="phone" id="reg_phone" class="form-control" placeholder="<?= t('auth_phone_placeholder') ?>" value="<?= e($formData['phone'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Email Address -->
                    <div class="col-12">
                        <label for="reg_email" class="form-label"><?= t('auth_email') ?> *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-surface-alt"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" id="reg_email" class="form-control" placeholder="student@obu.edu.et" value="<?= e($formData['email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="col-md-6">
                        <label for="reg_password" class="form-label"><?= t('auth_password') ?> *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-surface-alt"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="reg_password" class="form-control" minlength="8" required>
                        </div>
                        <div class="password-meter mt-2">
                            <div class="password-meter-bar" id="passwordMeterBar"></div>
                        </div>
                        <div class="form-text small"><?= t('auth_password_req') ?></div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="col-md-6">
                        <label for="reg_confirm_password" class="form-label"><?= t('auth_confirm_password') ?> *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-surface-alt"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" name="confirm_password" id="reg_confirm_password" class="form-control" minlength="8" required>
                        </div>
                    </div>
                </div>

                <!-- Terms agreement -->
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" id="termsCheck" required checked>
                    <label class="form-check-label small text-secondary" for="termsCheck">
                        I agree to abide by the Oda Bultum University Student Marketplace honor code and campus safety rules.
                    </label>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-obu w-100 py-2.5 fs-6">
                        <i class="bi bi-check2-circle me-1"></i> <?= t('auth_register_btn') ?>
                    </button>
                </div>
            </form>

            <div class="auth-footer-note">
                <?= t('auth_already_have') ?> 
                <a href="<?= base_url('login.php') ?>" class="fw-bold text-primary"><?= t('auth_login_link') ?></a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

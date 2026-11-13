<?php
/**
 * OBU Student Marketplace - Edit Profile (Module 3)
 */
require_once __DIR__ . '/../config/app.php';
require_student();

$pageTitle = t('edit_profile');
$extraCss = ['dashboard.css'];
$user = current_user();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main Form Area -->
        <div class="col-lg-9">
            <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-surface">
                <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                    <div>
                        <h3 class="fw-bold mb-1"><?= t('edit_profile') ?></h3>
                        <p class="text-secondary mb-0">Update your profile details and campus avatar</p>
                    </div>
                    <a href="<?= base_url('student/profile.php') ?>" class="btn btn-sm btn-outline-secondary">
                        &larr; <?= t('profile_title') ?>
                    </a>
                </div>

                <form action="<?= base_url('actions/user-settings-action.php') ?>" method="POST" enctype="multipart/form-data" class="needs-validation">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="row g-4">
                        <!-- Profile Image -->
                        <div class="col-12">
                            <label class="form-label fw-bold"><?= t('profile_picture') ?></label>
                            <div class="d-flex align-items-center gap-3">
                                <div class="user-avatar-circle" style="width: 72px; height: 72px; font-size: 1.8rem;">
                                    <?php if (!empty($user['profile_image'])): ?>
                                        <img src="<?= upload_url('profiles/' . $user['profile_image']) ?>" alt="Avatar">
                                    <?php else: ?>
                                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" name="profile_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                                    <div class="form-text small">Accepted formats: JPG, PNG, WebP (Max 5MB).</div>
                                </div>
                            </div>
                        </div>

                        <!-- Full Name -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('auth_full_name') ?> *</label>
                            <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                        </div>

                        <!-- Student ID (Readonly) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('auth_student_id') ?></label>
                            <input type="text" class="form-control" value="<?= e($user['student_id']) ?>" readonly disabled>
                            <div class="form-text small">Student ID cannot be changed. Contact ICT admin if incorrect.</div>
                        </div>

                        <!-- Department -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('auth_department') ?> *</label>
                            <input type="text" name="department" class="form-control" value="<?= e($user['department']) ?>" required>
                        </div>

                        <!-- Year of Study -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('auth_year') ?> *</label>
                            <select name="year_of_study" class="form-select" required>
                                <option value="1st Year" <?= $user['year_of_study'] === '1st Year' ? 'selected' : '' ?>><?= t('auth_year_1') ?></option>
                                <option value="2nd Year" <?= $user['year_of_study'] === '2nd Year' ? 'selected' : '' ?>><?= t('auth_year_2') ?></option>
                                <option value="3rd Year" <?= $user['year_of_study'] === '3rd Year' ? 'selected' : '' ?>><?= t('auth_year_3') ?></option>
                                <option value="4th Year" <?= $user['year_of_study'] === '4th Year' ? 'selected' : '' ?>><?= t('auth_year_4') ?></option>
                                <option value="5th Year" <?= $user['year_of_study'] === '5th Year' ? 'selected' : '' ?>><?= t('auth_year_5') ?></option>
                                <option value="Postgraduate" <?= $user['year_of_study'] === 'Postgraduate' ? 'selected' : '' ?>><?= t('auth_year_pg') ?></option>
                                <option value="Staff" <?= $user['year_of_study'] === 'Staff' ? 'selected' : '' ?>><?= t('auth_year_staff') ?></option>
                            </select>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('auth_phone') ?> *</label>
                            <input type="tel" name="phone" class="form-control" value="<?= e($user['phone']) ?>" required>
                        </div>

                        <!-- Email (Readonly) -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= t('auth_email') ?></label>
                            <input type="email" class="form-control" value="<?= e($user['email']) ?>" readonly disabled>
                        </div>

                        <div class="col-12 mt-4 pt-3 border-top d-flex gap-3">
                            <button type="submit" class="btn btn-obu px-5 py-2.5">
                                <i class="bi bi-check2-circle me-1"></i> <?= t('update_profile') ?>
                            </button>
                            <a href="<?= base_url('student/profile.php') ?>" class="btn btn-outline-secondary px-4 py-2.5">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

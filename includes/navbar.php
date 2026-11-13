<?php
/**
 * OBU Student Marketplace - Main Navigation Bar
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg obu-navbar">
    <div class="container">
        <!-- Logo & Brand -->
        <a class="navbar-brand obu-brand" href="<?= base_url('index.php') ?>">
            <img src="<?= asset_url('images/logo.svg') ?>" alt="OBU Logo" height="42">
        </a>

        <!-- Mobile Controls (Theme/Lang + Hamburger) -->
        <div class="d-flex align-items-center gap-2 d-lg-none ms-auto me-2">
            <!-- Mobile Language Dropdown -->
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle py-1 px-2" type="button" data-bs-toggle="dropdown">
                    <?= SUPPORTED_LANGUAGES[$activeLang]['flag'] ?? '🌐' ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <?php foreach (SUPPORTED_LANGUAGES as $code => $info): ?>
                        <li><a class="dropdown-item" href="<?= get_lang_url($code) ?>"><?= $info['flag'] ?> <?= $info['native'] ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Mobile Theme Toggle -->
            <button class="btn btn-sm btn-outline-secondary py-1 px-2" data-set-theme="<?= $activeTheme === 'dark' ? 'bright' : ($activeTheme === 'bright' ? 'light' : 'dark') ?>" title="<?= t('theme') ?>">
                <?php if ($activeTheme === 'dark'): ?>
                    <i class="bi bi-moon-stars-fill text-warning"></i>
                <?php elseif ($activeTheme === 'bright'): ?>
                    <i class="bi bi-lightning-charge-fill text-warning"></i>
                <?php else: ?>
                    <i class="bi bi-brightness-high"></i>
                <?php endif; ?>
            </button>
        </div>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-2 text-primary"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <!-- Center Navigation Links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="<?= base_url('index.php') ?>">
                        <i class="bi bi-house-door me-1"></i> <?= t('nav_home') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'products.php' ? 'active' : '' ?>" href="<?= base_url('products.php') ?>">
                        <i class="bi bi-grid-3x3-gap me-1"></i> <?= t('nav_browse') ?>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-tags me-1"></i> <?= t('nav_categories') ?>
                    </a>
                    <ul class="dropdown-menu shadow-sm">
                        <?php foreach (get_all_categories() as $cat): ?>
                            <li>
                                <a class="dropdown-item" href="<?= base_url('category.php?id=' . $cat['id']) ?>">
                                    <i class="bi <?= e($cat['icon'] ?? 'bi-tag') ?> me-2 text-primary"></i>
                                    <?= e(localized($cat, 'name')) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item fw-bold text-primary" href="<?= base_url('products.php') ?>"><?= t('browse_all_products') ?> &raquo;</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'how-it-works.php' ? 'active' : '' ?>" href="<?= base_url('how-it-works.php') ?>">
                        <?= t('nav_how_it_works') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'safety-tips.php' ? 'active' : '' ?>" href="<?= base_url('safety-tips.php') ?>">
                        <?= t('nav_safety_tips') ?>
                    </a>
                </li>
            </ul>

            <!-- Right-Hand Actions & User Navigation -->
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <?php if ($currentUser): ?>
                    <!-- Post New Ad / Sell Button -->
                    <a href="<?= base_url('student/add-product.php') ?>" class="btn btn-obu me-1">
                        <i class="bi bi-plus-circle-fill"></i> <?= t('nav_add_product') ?>
                    </a>

                    <!-- Messages with notification badge -->
                    <a href="<?= base_url('student/messages.php') ?>" class="btn btn-light position-relative border" title="<?= t('nav_messages') ?>">
                        <i class="bi bi-chat-dots fs-5 text-secondary"></i>
                        <?php if ($unreadMsgCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $unreadMsgCount ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- Notifications Bell with badge -->
                    <a href="<?= base_url('student/notifications.php') ?>" class="btn btn-light position-relative border" title="<?= t('nav_notifications') ?>">
                        <i class="bi bi-bell fs-5 text-secondary"></i>
                        <?php if ($unreadNotifCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">
                                <?= $unreadNotifCount ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-light border d-flex align-items-center gap-2 py-1 px-2 rounded-pill dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="rounded-circle <?= $currentUser['role'] === ROLE_ADMIN ? 'bg-danger text-white' : 'bg-primary text-white' ?> d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem; font-weight: 700;">
                                <?= $currentUser['role'] === ROLE_ADMIN ? 'A' : strtoupper(substr($currentUser['full_name'], 0, 1)) ?>
                            </span>
                            <span class="d-none d-xl-inline text-start lh-1">
                                <span class="d-block fw-bold small text-truncate" style="max-width: 110px;">
                                    <?= $currentUser['role'] === ROLE_ADMIN ? 'Admin' : e($currentUser['full_name']) ?>
                                </span>
                                <span class="d-block <?= $currentUser['role'] === ROLE_ADMIN ? 'text-danger fw-bold' : 'text-muted' ?>" style="font-size: 0.72rem;">
                                    <?= $currentUser['role'] === ROLE_ADMIN ? 'Admin' : 'Student' ?>
                                </span>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-bold"><?= $currentUser['role'] === ROLE_ADMIN ? 'Admin' : e($currentUser['full_name']) ?></div>
                                <div class="small text-muted"><?= e($currentUser['email']) ?></div>
                                <?php if ($currentUser['role'] === ROLE_ADMIN): ?>
                                    <div class="badge bg-danger text-white mt-1">ADMINISTRATOR</div>
                                <?php else: ?>
                                    <div class="badge bg-primary text-white mt-1">STUDENT &bull; <?= e($currentUser['student_id']) ?></div>
                                <?php endif; ?>
                            </li>
                            <?php if ($currentUser['role'] === ROLE_ADMIN): ?>
                                <li>
                                    <a class="dropdown-item text-danger fw-bold" href="<?= base_url('admin/dashboard.php') ?>">
                                        <i class="bi bi-speedometer2 me-2"></i> <?= t('admin_dashboard') ?>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php elseif ($currentUser['role'] === ROLE_MODERATOR): ?>
                                <li>
                                    <a class="dropdown-item text-warning-emphasis fw-bold" href="<?= base_url('moderator/dashboard.php') ?>">
                                        <i class="bi bi-shield-lock me-2"></i> <?= t('moderator_dashboard') ?>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="<?= base_url('student/dashboard.php') ?>">
                                    <i class="bi bi-speedometer me-2"></i> <?= t('nav_dashboard') ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= base_url('student/my-products.php') ?>">
                                    <i class="bi bi-box-seam me-2"></i> <?= t('nav_my_products') ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= base_url('student/purchase-requests.php') ?>">
                                    <i class="bi bi-bag-check me-2"></i> <?= t('nav_requests') ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= base_url('student/favorites.php') ?>">
                                    <i class="bi bi-heart me-2"></i> <?= t('nav_favorites') ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= base_url('student/profile.php') ?>">
                                    <i class="bi bi-person me-2"></i> <?= t('nav_profile') ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= base_url('student/settings.php') ?>">
                                    <i class="bi bi-gear me-2"></i> <?= t('nav_settings') ?>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= base_url('logout.php') ?>">
                                    <i class="bi bi-box-arrow-right me-2"></i> <?= t('nav_logout') ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= base_url('login.php') ?>" class="btn btn-obu-outline">
                        <i class="bi bi-box-arrow-in-right"></i> <?= t('nav_login') ?>
                    </a>
                    <a href="<?= base_url('register.php') ?>" class="btn btn-obu">
                        <i class="bi bi-person-plus"></i> <?= t('nav_register') ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

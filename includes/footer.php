<?php
/**
 * OBU Student Marketplace - Master Footer
 */
?>
</main>

<footer class="obu-footer">
    <div class="container">
        <div class="row g-4">
            <!-- Brand & Info -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="<?= asset_url('images/logo.svg') ?>" alt="OBU Logo" height="38" style="filter: brightness(0) invert(1);">
                </div>
                <p class="text-secondary mb-3" style="font-size: 0.92rem; line-height: 1.6;">
                    <?= e(t('sub_tagline')) ?>
                </p>
                <div class="d-flex align-items-center gap-3 text-secondary small">
                    <span><i class="bi bi-geo-alt-fill text-danger me-1"></i> Chiro, West Hararghe, Ethiopia</span>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5><?= t('nav_home') ?></h5>
                <ul class="list-unstyled">
                    <li><a href="<?= base_url('products.php') ?>"><?= t('nav_browse') ?></a></li>
                    <li><a href="<?= base_url('how-it-works.php') ?>"><?= t('nav_how_it_works') ?></a></li>
                    <li><a href="<?= base_url('safety-tips.php') ?>"><?= t('nav_safety_tips') ?></a></li>
                    <li><a href="<?= base_url('about.php') ?>"><?= t('nav_about') ?></a></li>
                    <li><a href="<?= base_url('contact.php') ?>">Contact Support</a></li>
                </ul>
            </div>

            <!-- Top Categories -->
            <div class="col-lg-3 col-md-6 col-6">
                <h5><?= t('nav_categories') ?></h5>
                <ul class="list-unstyled">
                    <?php 
                        $footerCats = array_slice(get_all_categories(), 0, 5);
                        foreach ($footerCats as $fCat):
                    ?>
                        <li>
                            <a href="<?= base_url('category.php?id=' . $fCat['id']) ?>">
                                <?= e(localized($fCat, 'name')) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Campus Support & Safety Notice -->
            <div class="col-lg-3 col-md-6">
                <h5>Campus Safety</h5>
                <div class="p-3 rounded mb-2" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="d-flex gap-2">
                        <i class="bi bi-shield-check text-success fs-4"></i>
                        <p class="small text-secondary mb-0">
                            Strict student-to-student verification. Always meet on campus and never transfer advance funds.
                        </p>
                    </div>
                </div>
                <div class="small text-secondary mt-2">
                    <div><i class="bi bi-envelope me-1"></i> <?= e(get_setting('contact_email', 'marketplace@obu.edu.et')) ?></div>
                    <div><i class="bi bi-telephone me-1"></i> <?= e(get_setting('contact_phone', '+251 25 551 0352')) ?></div>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="obu-footer-bottom text-center">
            <p class="mb-1">
                &copy; <?= date('Y') ?> <?= e(t('university_name')) ?> — <?= e(t('app_name')) ?>. <?= e(t('all_rights_reserved')) ?>
            </p>
            <p class="mb-0 text-muted" style="font-size: 0.78rem;">
                Built for students, by students. Developed for academic community exchange.
            </p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?= asset_url('js/main.js') ?>"></script>
<script src="<?= asset_url('js/validation.js') ?>"></script>
<script src="<?= asset_url('js/marketplace.js') ?>"></script>
<?php if (!empty($extraJs)): ?>
    <?php foreach ((array)$extraJs as $js): ?>
        <script src="<?= asset_url('js/' . $js) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>

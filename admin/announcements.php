<?php
/**
 * OBU Student Marketplace - Campus Announcements (Admin)
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$pageTitle = t('manage_announcements');
$extraCss = ['dashboard.css'];
$currentUser = current_user();

// Handle Create / Delete Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_csrf();
    $act = clean_input($_POST['action']);

    if ($act === 'create') {
        $titleEn = clean_input($_POST['title_en'] ?? '');
        $titleAm = clean_input($_POST['title_am'] ?? '');
        $titleOm = clean_input($_POST['title_om'] ?? '');
        $contentEn = clean_input($_POST['content_en'] ?? '');
        $contentAm = clean_input($_POST['content_am'] ?? '');
        $contentOm = clean_input($_POST['content_om'] ?? '');

        if (!empty($titleEn) && !empty($contentEn)) {
            try {
                $stmt = db()->prepare("
                    INSERT INTO announcements (title_en, title_am, title_om, content_en, content_am, content_om, created_by, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'published')
                ");
                $stmt->execute([
                    $titleEn,
                    $titleAm ?: $titleEn,
                    $titleOm ?: $titleEn,
                    $contentEn,
                    $contentAm ?: $contentEn,
                    $contentOm ?: $contentEn,
                    $currentUser['id']
                ]);

                log_activity('ADMIN_ANNOUNCEMENT', "Published announcement: $titleEn", (int)$currentUser['id']);
                set_flash('success', 'Announcement published in all languages.');
            } catch (Exception $e) {
                set_flash('danger', 'Error creating announcement: ' . $e->getMessage());
            }
        }
    }

    if ($act === 'delete') {
        $annId = (int)($_POST['announcement_id'] ?? 0);
        if ($annId > 0) {
            $stmt = db()->prepare("DELETE FROM announcements WHERE id = ?");
            $stmt->execute([$annId]);
            set_flash('info', 'Announcement deleted.');
        }
    }

    header('Location: ' . base_url('admin/announcements.php'));
    exit;
}

$announcements = [];
if (db()) {
    try {
        $stmt = db()->query("
            SELECT a.*, u.full_name AS author_name 
            FROM announcements a 
            JOIN users u ON a.created_by = u.id 
            ORDER BY a.id DESC
        ");
        $announcements = $stmt->fetchAll();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container dashboard-container">
    <div class="card border-0 rounded-4 shadow-sm p-4 bg-surface mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pb-3 mb-4 border-bottom">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-megaphone-fill text-primary me-2"></i> <?= t('manage_announcements') ?></h3>
                <p class="text-secondary mb-0">Broadcast notices and security bulletins to all university students</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-obu btn-sm" data-bs-toggle="modal" data-bs-target="#newAnnouncementModal">
                    <i class="bi bi-plus-circle me-1"></i> Post Announcement
                </button>
                <a href="<?= base_url('admin/dashboard.php') ?>" class="btn btn-outline-secondary btn-sm">
                    &larr; Admin Overview
                </a>
            </div>
        </div>

        <?php if (empty($announcements)): ?>
            <div class="text-center py-5">
                <i class="bi bi-megaphone fs-1 text-muted mb-2 d-block"></i>
                <h5 class="fw-bold">No announcements yet</h5>
                <p class="text-muted">Publish your first broadcast bulletin to the student community.</p>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($announcements as $ann): ?>
                    <div class="card p-3 rounded-3 bg-surface-alt border">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="fw-bold text-primary mb-1"><?= e($ann['title_en']) ?></h5>
                                <?php if (!empty($ann['title_am'])): ?>
                                    <h6 class="text-secondary mb-1"><?= e($ann['title_am']) ?></h6>
                                <?php endif; ?>
                                <?php if (!empty($ann['title_om'])): ?>
                                    <h6 class="text-muted mb-2 fst-italic"><?= e($ann['title_om']) ?></h6>
                                <?php endif; ?>
                            </div>
                            <form action="<?= base_url('admin/announcements.php') ?>" method="POST" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="announcement_id" value="<?= $ann['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this announcement?');">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>

                        <p class="small text-secondary mb-2"><?= nl2br(e($ann['content_en'])) ?></p>

                        <div class="small text-muted border-top pt-2 mt-2 d-flex justify-content-between">
                            <span>Posted by <?= e($ann['author_name']) ?></span>
                            <span><?= date('M j, Y, g:i A', strtotime($ann['created_at'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: New Announcement -->
<div class="modal fade" id="newAnnouncementModal" tabindex="-1" aria-labelledby="annLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="annLabel">
                    <i class="bi bi-megaphone text-primary me-2"></i> New Multilingual Announcement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('admin/announcements.php') ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">English Title *</label>
                            <input type="text" name="title_en" class="form-control" placeholder="Headline in English..." required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">English Body Content *</label>
                            <textarea name="content_en" class="form-control" rows="3" required></textarea>
                        </div>

                        <div class="col-12 border-top pt-3">
                            <label class="form-label small fw-bold">Amharic Title (አማርኛ)</label>
                            <input type="text" name="title_am" class="form-control" placeholder="የማስታወቂያው አርዕስት...">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Amharic Content (አማርኛ)</label>
                            <textarea name="content_am" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-12 border-top pt-3">
                            <label class="form-label small fw-bold">Afaan Oromo Title (Afaan Oromoo)</label>
                            <input type="text" name="title_om" class="form-control" placeholder="Mata duree beeksisaa...">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Afaan Oromo Content (Afaan Oromoo)</label>
                            <textarea name="content_om" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-obu">Publish Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

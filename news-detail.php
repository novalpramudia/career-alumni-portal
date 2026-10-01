<?php
require_once __DIR__ . '/config/auth.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$n = $slug !== '' ? db_one("SELECT title, content, image, created_at, status FROM news WHERE slug = ?", [$slug]) : null;
if (!$n || ($n['status'] !== 'published' && !(is_logged_in() && $_SESSION['user']['role'] === 'admin'))) {
    http_response_code(404);
    flash('warning', 'Berita tidak ditemukan.');
    redirect('news.php');
}
$imageUrl = $n['image'] && is_file(UPLOAD_PATH . '/news/' . basename($n['image'])) ? url('uploads/news/' . rawurlencode($n['image'])) : null;

$pageTitle = $n['title'];
include __DIR__ . '/components/head.php';
include __DIR__ . '/components/navbar.php';
?>
<article class="section">
  <div class="container" style="max-width: 760px;">
    <a href="<?= e(url('news.php')) ?>" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Semua berita</a>
    <?php if ($n['status'] === 'draft'): ?><span class="badge status-pending mb-2">Draft &ndash; hanya terlihat oleh admin</span><?php endif; ?>
    <h1 class="h3"><?= e($n['title']) ?></h1>
    <p class="text-secondary">Dipublikasikan <?= e(date_id($n['created_at'])) ?></p>
    <?php if ($imageUrl): ?><img src="<?= e($imageUrl) ?>" class="w-100 rounded mb-4" alt="<?= e($n['title']) ?>"><?php endif; ?>
    <div class="job-detail-text"><?= nl2br(e($n['content'])) ?></div>
  </div>
</article>
<?php include __DIR__ . '/components/footer.php'; include __DIR__ . '/components/scripts.php'; ?>

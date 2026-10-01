<?php
require_once __DIR__ . '/../config/auth.php';
require_role('alumni');

$rows = db_all("SELECT title, slug, content, created_at FROM news WHERE status = 'published' ORDER BY created_at DESC, id DESC LIMIT 12");

$pageTitle  = 'Berita';
$activeMenu = 'alumni/news.php';
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
?>
<?php if (!$rows): ?>
  <div class="panel"><div class="empty-state"><i class="bi bi-newspaper"></i>Belum ada berita.</div></div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($rows as $n): ?>
      <div class="col-md-6 col-xl-4">
        <a class="news-card d-block text-decoration-none text-reset h-100" href="<?= e(url('news-detail.php?slug=' . urlencode($n['slug']))) ?>" target="_blank">
          <time datetime="<?= e(substr($n['created_at'], 0, 10)) ?>"><?= e(date_id($n['created_at'])) ?></time>
          <h2 class="h6 mt-2"><?= e($n['title']) ?></h2>
          <p class="small text-secondary mb-0"><?= e(excerpt($n['content'], 120)) ?></p>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/../components/dash_end.php'; ?>

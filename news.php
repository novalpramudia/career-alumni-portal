<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/jobs.php'; // render_pagination()

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$total = (int) db_scalar("SELECT COUNT(*) FROM news WHERE status = 'published'");
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;
$rows = db_all("SELECT title, slug, content, image, created_at FROM news WHERE status = 'published'
               ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET $offset");

$pageTitle = 'Berita';
include __DIR__ . '/components/head.php';
include __DIR__ . '/components/navbar.php';
?>
<section class="section">
  <div class="container">
    <div class="section-head"><div><h1 class="h3 mb-0">Berita career center</h1><p>Kabar dan tips terbaru untuk alumni.</p></div></div>
    <?php if (!$rows): ?>
      <div class="empty-state"><i class="bi bi-newspaper"></i>Belum ada berita.</div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($rows as $n): ?>
          <div class="col-md-6 col-lg-4">
            <a class="news-card d-block text-decoration-none text-reset h-100" href="<?= e(url('news-detail.php?slug=' . urlencode($n['slug']))) ?>">
              <time datetime="<?= e(substr($n['created_at'], 0, 10)) ?>"><?= e(date_id($n['created_at'])) ?></time>
              <h2 class="h6 mt-2"><?= e($n['title']) ?></h2>
              <p class="small text-secondary mb-0"><?= e(excerpt($n['content'], 130)) ?></p>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
      <?php $qs=[]; ?>
      <?= render_pagination($page, $pages, [], 'news.php') ?>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/components/footer.php'; include __DIR__ . '/components/scripts.php'; ?>

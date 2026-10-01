<?php
/** Navbar publik (landing page). */
$navUser = current_user();
?>
<nav class="navbar navbar-expand-lg site-nav sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(url('index.php')) ?>">
      <span class="brand-mark">C</span><span><?= e(APP_NAME) ?></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Buka menu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="siteNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= e(url('index.php#lowongan')) ?>">Lowongan</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(url('index.php#perusahaan')) ?>">Perusahaan</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(url('index.php#career-center')) ?>">Career center</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= e(url('news.php')) ?>">Berita</a></li>
      </ul>
      <div class="d-flex gap-2 align-items-center">
        <?php if ($navUser): ?>
          <a class="btn btn-primary btn-sm" href="<?= e(url(dashboard_path($navUser['role']))) ?>">Dashboard</a>
          <form method="post" action="<?= e(url('auth/logout.php')) ?>" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-secondary btn-sm">Keluar</button>
          </form>
        <?php else: ?>
          <a class="btn btn-outline-primary btn-sm" href="<?= e(url('auth/login.php')) ?>">Masuk</a>
          <a class="btn btn-accent btn-sm" href="<?= e(url('auth/register.php')) ?>">Daftar</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<?php
/**
 * Layout dashboard: sidebar + topbar. Pasangannya: components/dash_end.php
 * Variabel sebelum include: $pageTitle (judul halaman), $activeMenu (path menu aktif).
 * Menu yang halamannya belum ada otomatis tampil nonaktif ("segera"), lalu aktif sendiri saat file dibuat.
 */
$sbUser = current_user();
$sbRole = $sbUser['role'];
$activeMenu = $activeMenu ?? '';

$allMenus = [
    'admin' => [
        ['Dashboard',      'admin/dashboard.php',          'speedometer2'],
        ['Alumni',         'admin/alumni/index.php',       'mortarboard'],
        ['Perusahaan',     'admin/companies/index.php',    'buildings'],
        ['Lowongan',       'admin/jobs/index.php',         'briefcase'],
        ['Lamaran',        'admin/applications/index.php', 'file-earmark-person'],
        ['Tracer study',   'admin/tracer/index.php',       'bar-chart-line'],
        ['Berita',         'admin/news/index.php',         'newspaper'],
    ],
    'alumni' => [
        ['Dashboard',      'alumni/dashboard.php',         'speedometer2'],
        ['Profil saya',    'alumni/profile.php',           'person-circle'],
        ['Lowongan',       'alumni/jobs.php',              'search'],
        ['Lamaran saya',   'alumni/applications.php',      'file-earmark-person'],
        ['Tracer study',   'alumni/tracer.php',            'clipboard2-check'],
        ['Berita',         'alumni/news.php',              'newspaper'],
    ],
    'company' => [
        ['Dashboard',         'company/dashboard.php',     'speedometer2'],
        ['Profil perusahaan', 'company/profile.php',       'building'],
        ['Lowongan saya',     'company/jobs.php',          'briefcase'],
        ['Buat lowongan',     'company/create-job.php',    'plus-circle'],
        ['Pelamar',           'company/applicants.php',    'people'],
    ],
];
$menuItems  = $allMenus[$sbRole] ?? [];
$roleLabels = ['admin' => 'Admin', 'alumni' => 'Alumni', 'company' => 'Perusahaan'];
?>
<div class="dash-shell">
  <div class="dash-side-wrap">
  <aside class="offcanvas-lg offcanvas-start dash-sidebar" tabindex="-1" id="sidebarMenu" aria-label="Menu utama">
    <div class="offcanvas-header d-lg-none">
      <span class="auth-brand"><span class="brand-mark">C</span> <?= e(APP_NAME) ?></span>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Tutup menu"></button>
    </div>
    <div class="offcanvas-body flex-column p-0">
      <a class="auth-brand dash-brand d-none d-lg-inline-flex" href="<?= e(url('index.php')) ?>"><span class="brand-mark">C</span> <?= e(APP_NAME) ?></a>
      <div class="dash-role"><?= e($roleLabels[$sbRole] ?? '') ?></div>
      <nav class="dash-nav">
        <?php foreach ($menuItems as [$label, $path, $icon]): ?>
          <?php if (is_file(__DIR__ . '/../' . $path)): ?>
            <a class="dash-link<?= $activeMenu === $path ? ' active' : '' ?>" href="<?= e(url($path)) ?>"<?= $activeMenu === $path ? ' aria-current="page"' : '' ?>>
              <i class="bi bi-<?= e($icon) ?>"></i> <?= e($label) ?>
            </a>
          <?php else: ?>
            <span class="dash-link disabled" aria-disabled="true" title="Dibuat pada tahap berikutnya">
              <i class="bi bi-<?= e($icon) ?>"></i> <?= e($label) ?> <small>segera</small>
            </span>
          <?php endif; ?>
        <?php endforeach; ?>
      </nav>
      <div class="dash-side-foot">
        <a class="dash-link" href="<?= e(url('index.php')) ?>"><i class="bi bi-house"></i> Ke beranda</a>
      </div>
    </div>
  </aside>
  </div>

  <div class="dash-main">
    <header class="dash-topbar">
      <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-label="Buka menu">
        <i class="bi bi-list"></i>
      </button>
      <h1 class="dash-title"><?= e($pageTitle ?? '') ?></h1>
      <div class="ms-auto d-flex align-items-center gap-3">
        <div class="d-none d-sm-flex align-items-center gap-2">
          <span class="avatar-box avatar-sm"><?= e(initials($sbUser['name'])) ?></span>
          <span class="small lh-sm"><strong class="d-block"><?= e($sbUser['name']) ?></strong><span class="text-secondary"><?= e($sbUser['email']) ?></span></span>
        </div>
        <form method="post" action="<?= e(url('auth/logout.php')) ?>" class="m-0">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-right"></i> Keluar</button>
        </form>
      </div>
    </header>
    <main class="dash-content">
      <?= render_flashes() ?>

<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/jobs.php';
require_role('alumni');

$filters = job_filters_from($_GET);
$page    = max(1, (int) ($_GET['page'] ?? 1));
$res     = search_jobs($filters, $page);

$categories = db_all('SELECT id, name FROM job_categories ORDER BY name');
$locations  = array_column(db_all("SELECT DISTINCT location FROM jobs WHERE status = 'open' AND deadline >= CURDATE() ORDER BY location"), 'location');

$pageTitle    = 'Lowongan pekerjaan';
$activeMenu   = 'alumni/jobs.php';
$extraScripts = ['assets/js/jobs.js'];
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
?>

<form id="jobFilter" class="filter-bar mb-3" method="get" action="<?= e(url('alumni/jobs.php')) ?>" data-api="<?= e(url('api/jobs.php')) ?>" role="search">
  <div class="row g-2">
    <div class="col-lg-4">
      <label for="q" class="form-label visually-hidden">Kata kunci</label>
      <input type="search" class="form-control" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Posisi, perusahaan, atau kata kunci" autocomplete="off">
    </div>
    <div class="col-6 col-lg-2">
      <label for="location" class="form-label visually-hidden">Lokasi</label>
      <select class="form-select" id="location" name="location">
        <option value="">Semua lokasi</option>
        <?php foreach ($locations as $loc): ?>
          <option value="<?= e($loc) ?>" <?= $filters['location'] === $loc ? 'selected' : '' ?>><?= e($loc) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-lg-3">
      <label for="category" class="form-label visually-hidden">Kategori</label>
      <select class="form-select" id="category" name="category">
        <option value="">Semua kategori</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int) $cat['id'] ?>" <?= $filters['category'] === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-lg-2">
      <label for="type" class="form-label visually-hidden">Tipe pekerjaan</label>
      <select class="form-select" id="type" name="type">
        <option value="">Semua tipe</option>
        <?php foreach (JOB_TYPES as $t): ?>
          <option value="<?= e($t) ?>" <?= $filters['type'] === $t ? 'selected' : '' ?>><?= e(job_type_label($t)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-lg-1 d-grid">
      <button type="submit" class="btn btn-primary">Cari</button>
    </div>
  </div>
</form>

<p id="jobCount" class="text-secondary small" aria-live="polite"><?= e($res['total']) ?> lowongan ditemukan</p>

<div id="jobResults"><?= render_job_grid($res['items']) ?></div>
<div id="jobPagination"><?= render_pagination($res['page'], $res['pages'], $filters, 'alumni/jobs.php') ?></div>

<?php include __DIR__ . '/../components/dash_end.php'; ?>

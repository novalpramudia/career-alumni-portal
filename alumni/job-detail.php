<?php
require_once __DIR__ . '/../config/auth.php';
require_role('alumni');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$job = $id ? db_one('SELECT j.*, c.id AS company_id, c.name AS company_name, c.logo AS company_logo, c.industry, k.name AS category_name
                     FROM jobs j
                     JOIN companies c ON c.id = j.company_id
                     JOIN job_categories k ON k.id = j.category_id
                     WHERE j.id = ?', [$id]) : null;
if (!$job) {
    flash('warning', 'Lowongan tidak ditemukan.');
    redirect('alumni/jobs.php');
}

$alumniId = (int) current_alumni_id();
$application = db_one('SELECT id, status, created_at FROM job_applications WHERE job_id = ? AND alumni_id = ?', [$job['id'], $alumniId]);

$today    = new DateTime('today');
$deadline = new DateTime($job['deadline']);
$isOpen   = $job['status'] === 'open' && $deadline >= $today;
$daysLeft = (int) $today->diff($deadline)->format('%r%a');
$applyPage = is_file(__DIR__ . '/apply.php'); // halaman lamaran dibuat pada Tahap 7

$pageTitle  = $job['title'];
$activeMenu = 'alumni/jobs.php';
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
?>

<a href="<?= e(url('alumni/jobs.php')) ?>" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Kembali ke daftar</a>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="panel"><div class="panel-body">
      <div class="d-flex gap-3 align-items-start mb-3">
        <?= avatar_html($job['company_name'], $job['company_logo']) ?>
        <div class="min-w-0">
          <h2 class="h4 mb-1"><?= e($job['title']) ?></h2>
          <a href="<?= e(url('company/profile-view.php?id=' . (int) $job['company_id'])) ?>" class="text-decoration-none"><?= e($job['company_name']) ?></a>
        </div>
        <div class="ms-auto"><?= status_badge($isOpen ? 'open' : ($job['status'] === 'closed' ? 'closed' : 'expired')) ?></div>
      </div>

      <div class="job-meta mb-4">
        <span><i class="bi bi-geo-alt"></i> <?= e($job['location']) ?></span>
        <span><i class="bi bi-briefcase"></i> <?= e(job_type_label($job['job_type'])) ?></span>
        <span><i class="bi bi-tag"></i> <?= e($job['category_name']) ?></span>
      </div>

      <h3 class="h6">Deskripsi pekerjaan</h3>
      <p class="job-detail-text mb-4"><?= nl2br(e($job['description'])) ?></p>

      <h3 class="h6">Persyaratan</h3>
      <p class="job-detail-text mb-0"><?= nl2br(e($job['requirements'])) ?></p>
    </div></div>
  </div>

  <div class="col-lg-4">
    <div class="panel"><div class="panel-body">
      <dl class="info-list mb-0">
        <dt>Gaji</dt><dd><?= e($job['salary'] ?: 'Tidak dicantumkan') ?></dd>
        <dt>Batas lamaran</dt>
        <dd><?= e(date_id($job['deadline'])) ?>
          <?php if ($isOpen): ?><span class="small text-secondary d-block"><?= $daysLeft === 0 ? 'Berakhir hari ini' : 'Sisa ' . e($daysLeft) . ' hari' ?></span><?php endif; ?>
        </dd>
        <dt>Dipublikasikan</dt><dd class="mb-0"><?= e(date_id($job['created_at'])) ?></dd>
      </dl>
    </div></div>

    <div class="panel mt-3"><div class="panel-body">
      <?php if ($application): ?>
        <p class="mb-2">Anda sudah melamar pada <?= e(date_id($application['created_at'])) ?>.</p>
        <p class="mb-3">Status: <?= status_badge($application['status']) ?></p>
        <a class="btn btn-outline-primary w-100" href="<?= e(url('alumni/applications.php')) ?>">Lihat lamaran saya</a>
      <?php elseif (!$isOpen): ?>
        <p class="mb-0 text-secondary">Lowongan ini sudah ditutup dan tidak menerima lamaran.</p>
      <?php elseif ($applyPage): ?>
        <a class="btn btn-accent w-100 py-2" href="<?= e(url('alumni/apply.php?job_id=' . (int) $job['id'])) ?>">Lamar sekarang</a>
      <?php else: ?>
        <button class="btn btn-accent w-100 py-2" disabled>Lamar sekarang</button>
        <p class="small text-secondary mt-2 mb-0">Fitur melamar dibuat pada tahap berikutnya.</p>
      <?php endif; ?>
    </div></div>
  </div>
</div>

<?php include __DIR__ . '/../components/dash_end.php'; ?>

<?php
require_once __DIR__ . '/../config/auth.php';
require_role('alumni');

$user   = current_user();
$alumni = db_one('SELECT * FROM alumni WHERE user_id = ?', [$user['id']]) ?? [];
$alumniId = (int) ($alumni['id'] ?? 0);

/* Kelengkapan profil */
$comp       = alumni_completion($alumni);
$missing    = $comp['missing'];
$completion = $comp['percent'];

/* Statistik lamaran */
$appStats = db_one("SELECT COUNT(*) AS total,
                           COALESCE(SUM(status = 'pending'), 0)  AS pending,
                           COALESCE(SUM(status = 'accepted'), 0) AS accepted
                    FROM job_applications WHERE alumni_id = ?", [$alumniId]) ?? ['total' => 0, 'pending' => 0, 'accepted' => 0];

$tracerDone = (int) db_scalar('SELECT COUNT(*) FROM tracer_studies WHERE alumni_id = ?', [$alumniId]) > 0;

$latestJobs = db_all("SELECT j.id, j.title, j.location, j.job_type, j.salary, j.deadline,
                             c.name AS company_name, c.logo AS company_logo, k.name AS category_name
                      FROM jobs j
                      JOIN companies c ON c.id = j.company_id
                      JOIN job_categories k ON k.id = j.category_id
                      WHERE j.status = 'open' AND j.deadline >= CURDATE()
                      ORDER BY j.created_at DESC, j.id DESC LIMIT 4");

$pageTitle  = 'Dashboard alumni';
$activeMenu = 'alumni/dashboard.php';
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
?>

<?php if (($alumni['verification_status'] ?? '') === 'pending'): ?>
  <div class="alert alert-info d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-info-circle"></i>
    <div>Data Anda sedang menunggu verifikasi admin kampus.</div>
  </div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-4"><?= stat_card('file-earmark-person', 'Total lamaran', (int) $appStats['total'], 'blue') ?></div>
  <div class="col-sm-6 col-xl-4"><?= stat_card('hourglass-split', 'Lamaran menunggu', (int) $appStats['pending'], 'gold') ?></div>
  <div class="col-sm-6 col-xl-4"><?= stat_card('check2-circle', 'Lamaran diterima', (int) $appStats['accepted'], 'green') ?></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-7">
    <div class="panel h-100">
      <div class="panel-head"><h2>Kelengkapan profil</h2><strong><?= e($completion) ?>%</strong></div>
      <div class="panel-body">
        <div class="progress mb-3" role="progressbar" aria-label="Kelengkapan profil" aria-valuenow="<?= e($completion) ?>" aria-valuemin="0" aria-valuemax="100">
          <div class="progress-bar" style="width: <?= e($completion) ?>%"></div>
        </div>
        <?php if ($missing): ?>
          <p class="mb-2 small text-secondary">Belum diisi: <?= e(implode(', ', $missing)) ?>.</p>
          <a class="btn btn-primary btn-sm" href="<?= e(url('alumni/profile.php')) ?>">Lengkapi profil</a>
        <?php else: ?>
          <p class="mb-0 small text-success"><i class="bi bi-check-circle"></i> Profil Anda sudah lengkap.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="panel h-100">
      <div class="panel-head"><h2>Tracer study</h2><?= $tracerDone ? '<span class="badge status-accepted">Sudah diisi</span>' : '<span class="badge status-pending">Belum diisi</span>' ?></div>
      <div class="panel-body">
        <p class="small text-secondary">Masukan Anda membantu kampus memperbaiki kurikulum dan layanan career center.</p>
        <a class="btn btn-outline-primary btn-sm" href="<?= e(url('alumni/tracer.php')) ?>"><?= $tracerDone ? 'Perbarui isian' : 'Isi sekarang' ?></a>
      </div>
    </div>
  </div>
</div>

<div class="section-head">
  <div><h2 class="h5 mb-0">Lowongan terbaru</h2></div>
  <a class="btn btn-outline-primary btn-sm" href="<?= e(url('alumni/jobs.php')) ?>">Lihat semua</a>
</div>
<?php if (!$latestJobs): ?>
  <div class="panel"><div class="empty-state"><i class="bi bi-briefcase"></i>Belum ada lowongan yang dibuka.</div></div>
<?php else: ?>
  <div class="row g-3">
    <?php foreach ($latestJobs as $job): ?>
      <div class="col-md-6 col-xl-3"><?= job_card($job) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../components/dash_end.php'; ?>

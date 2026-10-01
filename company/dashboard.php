<?php
require_once __DIR__ . '/../config/auth.php';
require_role('company');

$companyId = (int) current_company_id();
$company   = db_one('SELECT * FROM companies WHERE id = ?', [$companyId]) ?? [];
$comp      = company_completion($company);

$totalJobs   = (int) db_scalar('SELECT COUNT(*) FROM jobs WHERE company_id = ?', [$companyId]);
$activeJobs  = (int) db_scalar("SELECT COUNT(*) FROM jobs WHERE company_id = ? AND status = 'open' AND deadline >= CURDATE()", [$companyId]);
$totalApps   = (int) db_scalar('SELECT COUNT(*) FROM job_applications ap JOIN jobs j ON j.id = ap.job_id WHERE j.company_id = ?', [$companyId]);
$pendingApps = (int) db_scalar("SELECT COUNT(*) FROM job_applications ap JOIN jobs j ON j.id = ap.job_id WHERE j.company_id = ? AND ap.status = 'pending'", [$companyId]);

$recentApps = db_all('SELECT a.full_name, a.study_program, j.title, ap.status, ap.created_at
                      FROM job_applications ap
                      JOIN jobs j ON j.id = ap.job_id
                      JOIN alumni a ON a.id = ap.alumni_id
                      WHERE j.company_id = ?
                      ORDER BY ap.created_at DESC, ap.id DESC LIMIT 6', [$companyId]);

$pageTitle  = 'Dashboard perusahaan';
$activeMenu = 'company/dashboard.php';
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
?>

<?php if ($comp['percent'] < 100): ?>
  <div class="alert alert-info d-flex align-items-center gap-2 flex-wrap" role="alert">
    <i class="bi bi-info-circle"></i>
    <div class="me-auto">Profil perusahaan baru <?= e($comp['percent']) ?>% lengkap. Profil yang lengkap lebih dipercaya pelamar.</div>
    <a class="btn btn-sm btn-primary" href="<?= e(url('company/profile.php')) ?>">Lengkapi profil</a>
  </div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3"><?= stat_card('briefcase', 'Total lowongan', $totalJobs, 'blue') ?></div>
  <div class="col-6 col-xl-3"><?= stat_card('megaphone', 'Lowongan aktif', $activeJobs, 'green') ?></div>
  <div class="col-6 col-xl-3"><?= stat_card('people', 'Total pelamar', $totalApps, 'slate') ?></div>
  <div class="col-6 col-xl-3"><?= stat_card('hourglass-split', 'Perlu ditinjau', $pendingApps, 'gold') ?></div>
</div>

<div class="panel">
  <div class="panel-head">
    <h2>Lamaran terbaru</h2>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-primary btn-sm" href="<?= e(url('company/applicants.php')) ?>">Lihat semua pelamar</a>
      <a class="btn btn-primary btn-sm" href="<?= e(url('company/create-job.php')) ?>">Buat lowongan</a>
    </div>
  </div>
  <?php if (!$recentApps): ?>
    <div class="empty-state"><i class="bi bi-inbox"></i>Belum ada lamaran masuk. Lamaran akan muncul di sini setelah alumni melamar.</div>
  <?php else: ?>
    <div class="table-responsive"><table class="table table-clean">
      <thead><tr><th>Pelamar</th><th>Posisi</th><th>Tanggal</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($recentApps as $r): ?>
        <tr>
          <td><strong class="d-block"><?= e($r['full_name']) ?></strong><span class="small text-secondary"><?= e($r['study_program'] ?: '-') ?></span></td>
          <td><?= e($r['title']) ?></td>
          <td class="text-nowrap"><?= e(date_id($r['created_at'])) ?></td>
          <td><?= status_badge($r['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../components/dash_end.php'; ?>

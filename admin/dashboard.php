<?php
require_once __DIR__ . '/../config/auth.php';
require_role('admin');

$totals = [
    'alumni'    => (int) db_scalar('SELECT COUNT(*) FROM alumni'),
    'companies' => (int) db_scalar('SELECT COUNT(*) FROM companies'),
    'jobs'      => (int) db_scalar('SELECT COUNT(*) FROM jobs'),
    'apps'      => (int) db_scalar('SELECT COUNT(*) FROM job_applications'),
    'tracer'    => (int) db_scalar('SELECT COUNT(*) FROM tracer_studies'),
];
$pendingAlumni = (int) db_scalar("SELECT COUNT(*) FROM alumni WHERE verification_status = 'pending'");

$recentAlumni = db_all('SELECT full_name, study_program, graduation_year, verification_status FROM alumni ORDER BY id DESC LIMIT 5');
$recentJobs   = db_all('SELECT j.title, j.status, j.created_at, c.name AS company_name
                        FROM jobs j JOIN companies c ON c.id = j.company_id ORDER BY j.id DESC LIMIT 5');
$recentApps   = db_all('SELECT a.full_name, j.title, ap.status, ap.created_at
                        FROM job_applications ap
                        JOIN alumni a ON a.id = ap.alumni_id
                        JOIN jobs j ON j.id = ap.job_id
                        ORDER BY ap.id DESC LIMIT 5');

$pageTitle    = 'Dashboard admin';
$activeMenu   = 'admin/dashboard.php';
$extraScripts = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js', 'assets/js/admin-dashboard.js'];
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
?>

<?php if ($pendingAlumni > 0): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-exclamation-triangle"></i>
    <div><?= e($pendingAlumni) ?> alumni menunggu verifikasi.</div>
  </div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-4 col-xl"><?= stat_card('mortarboard', 'Total alumni', $totals['alumni'], 'blue') ?></div>
  <div class="col-6 col-lg-4 col-xl"><?= stat_card('buildings', 'Total perusahaan', $totals['companies'], 'gold') ?></div>
  <div class="col-6 col-lg-4 col-xl"><?= stat_card('briefcase', 'Total lowongan', $totals['jobs'], 'green') ?></div>
  <div class="col-6 col-lg-4 col-xl"><?= stat_card('file-earmark-person', 'Total lamaran', $totals['apps'], 'slate') ?></div>
  <div class="col-12 col-lg-4 col-xl"><?= stat_card('clipboard2-check', 'Tracer study terisi', $totals['tracer'], 'red') ?></div>
</div>

<div class="row g-3 mb-4" id="charts" data-api="<?= e(url('api/stats.php')) ?>">
  <div class="col-lg-5">
    <div class="panel h-100">
      <div class="panel-head"><h2>Status lamaran</h2></div>
      <div class="panel-body"><div class="chart-box" id="chartApplicationsBox"><canvas id="chartApplications" aria-label="Grafik status lamaran" role="img"></canvas></div></div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="panel h-100">
      <div class="panel-head"><h2>Alumni per tahun lulus</h2></div>
      <div class="panel-body"><div class="chart-box" id="chartYearBox"><canvas id="chartYear" aria-label="Grafik alumni per tahun lulus" role="img"></canvas></div></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-4">
    <div class="panel h-100">
      <div class="panel-head"><h2>Alumni terbaru</h2></div>
      <?php if (!$recentAlumni): ?>
        <div class="empty-state">Belum ada alumni.</div>
      <?php else: ?>
        <div class="table-responsive"><table class="table table-clean">
          <thead><tr><th>Nama</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($recentAlumni as $r): ?>
            <tr>
              <td><strong class="d-block"><?= e($r['full_name']) ?></strong><span class="small text-secondary"><?= e($r['study_program'] ?: '-') ?> <?= e($r['graduation_year']) ?></span></td>
              <td><?= status_badge($r['verification_status']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="panel h-100">
      <div class="panel-head"><h2>Lowongan terbaru</h2></div>
      <?php if (!$recentJobs): ?>
        <div class="empty-state">Belum ada lowongan.</div>
      <?php else: ?>
        <div class="table-responsive"><table class="table table-clean">
          <thead><tr><th>Posisi</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($recentJobs as $r): ?>
            <tr>
              <td><strong class="d-block"><?= e($r['title']) ?></strong><span class="small text-secondary"><?= e($r['company_name']) ?></span></td>
              <td><?= status_badge($r['status']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="panel h-100">
      <div class="panel-head"><h2>Lamaran terbaru</h2></div>
      <?php if (!$recentApps): ?>
        <div class="empty-state">Belum ada lamaran.</div>
      <?php else: ?>
        <div class="table-responsive"><table class="table table-clean">
          <thead><tr><th>Pelamar</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($recentApps as $r): ?>
            <tr>
              <td><strong class="d-block"><?= e($r['full_name']) ?></strong><span class="small text-secondary"><?= e($r['title']) ?> &middot; <?= e(date_id($r['created_at'])) ?></span></td>
              <td><?= status_badge($r['status']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../components/dash_end.php'; ?>

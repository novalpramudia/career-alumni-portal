<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/jobs.php';   // render_pagination()
require_once __DIR__ . '/../../config/tracer.php';
require_role('admin');

$years = tracer_years();
$year  = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: null;
if ($year !== null && !in_array($year, $years, true)) {
    $year = null;
}

$s = tracer_stats($year);

/* Tabel respons (dengan pagination) */
$page    = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$pages   = max(1, (int) ceil($s['total'] / $perPage));
$page    = min($page, $pages);
$offset  = ($page - 1) * $perPage;
$rows = db_all('SELECT t.*, a.full_name, a.study_program
                FROM tracer_studies t JOIN alumni a ON a.id = t.alumni_id
                ' . ($year ? 'WHERE t.graduation_year = ?' : '') . "
                ORDER BY t.updated_at DESC, t.id DESC LIMIT $perPage OFFSET $offset", $year ? [$year] : []);

$pageTitle    = 'Tracer study';
$activeMenu   = 'admin/tracer/index.php';
$extraScripts = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js', 'assets/js/admin-tracer.js', 'assets/js/tracer-feedback.js'];
include __DIR__ . '/../../components/head.php';
include __DIR__ . '/../../components/sidebar.php';
?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-end mb-3">
  <form method="get" class="d-flex gap-2 align-items-end">
    <div>
      <label for="year" class="form-label small mb-1">Tahun lulus</label>
      <select class="form-select" id="year" name="year" onchange="this.form.submit()">
        <option value="">Semua tahun</option>
        <?php foreach ($years as $y): ?>
          <option value="<?= (int) $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= (int) $y ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <noscript><button class="btn btn-primary" type="submit">Terapkan</button></noscript>
  </form>
  <a class="btn btn-outline-primary" href="<?= e(url('admin/tracer/export.php' . ($year ? '?year=' . $year : ''))) ?>"><i class="bi bi-download"></i> Unduh CSV</a>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3"><?= stat_card('clipboard2-check', 'Responden', $s['total'], 'blue') ?></div>
  <div class="col-6 col-xl-3"><?= stat_card('percent', 'Tingkat respons', $s['response_pct'] . '%', 'gold') ?></div>
  <div class="col-6 col-xl-3"><?= stat_card('briefcase', 'Bekerja atau wirausaha', $s['working_pct'] . '%', 'green') ?></div>
  <div class="col-6 col-xl-3"><?= stat_card('bullseye', 'Pekerjaan sesuai jurusan', $s['relevant_pct'] . '%', 'slate') ?></div>
</div>

<?php if ($s['total'] === 0): ?>
  <div class="panel"><div class="empty-state"><i class="bi bi-clipboard2-x"></i>Belum ada responden<?= $year ? ' untuk tahun lulus ' . e($year) : '' ?>.</div></div>
<?php else: ?>
  <div class="row g-3 mb-4" id="charts" data-api="<?= e(url('api/tracer-stats.php' . ($year ? '?year=' . $year : ''))) ?>">
    <div class="col-lg-6"><div class="panel h-100"><div class="panel-head"><h2>Status alumni</h2></div>
      <div class="panel-body"><div class="chart-box" id="boxStatus"><canvas id="chartStatus" role="img" aria-label="Grafik status alumni"></canvas></div></div></div></div>
    <div class="col-lg-6"><div class="panel h-100"><div class="panel-head"><h2>Lama mendapatkan pekerjaan</h2></div>
      <div class="panel-body"><div class="chart-box" id="boxWaiting"><canvas id="chartWaiting" role="img" aria-label="Grafik lama mendapatkan pekerjaan"></canvas></div></div></div></div>
    <div class="col-lg-6"><div class="panel h-100"><div class="panel-head"><h2>Rentang penghasilan</h2></div>
      <div class="panel-body"><div class="chart-box" id="boxSalary"><canvas id="chartSalary" role="img" aria-label="Grafik rentang penghasilan"></canvas></div></div></div></div>
    <div class="col-lg-6"><div class="panel h-100"><div class="panel-head"><h2>Kesesuaian pekerjaan dengan jurusan</h2></div>
      <div class="panel-body"><div class="chart-box" id="boxRelevance"><canvas id="chartRelevance" role="img" aria-label="Grafik kesesuaian pekerjaan"></canvas></div></div></div></div>
  </div>

  <?php if ($s['fields']): ?>
    <div class="panel mb-4">
      <div class="panel-head"><h2>Bidang pekerjaan terbanyak</h2></div>
      <div class="table-responsive"><table class="table table-clean">
        <thead><tr><th>Bidang</th><th class="text-end">Jumlah alumni</th></tr></thead>
        <tbody>
        <?php foreach ($s['fields'] as $f): ?>
          <tr><td><?= e($f['field']) ?></td><td class="text-end"><?= (int) $f['n'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
  <?php endif; ?>

  <div class="panel">
    <div class="panel-head"><h2>Data responden</h2></div>
    <div class="table-responsive"><table class="table table-clean">
      <thead><tr><th>Alumni</th><th>Lulus</th><th>Status</th><th>Perusahaan</th><th>Penghasilan</th><th>Kesesuaian</th><th>Masukan</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><strong class="d-block"><?= e($r['full_name']) ?></strong><span class="small text-secondary"><?= e($r['study_program'] ?: '-') ?></span></td>
          <td><?= e($r['graduation_year']) ?></td>
          <td><?= e(TRACER_STATUS_LABELS[$r['employment_status']] ?? '-') ?></td>
          <td><?= $r['company_name'] ? e($r['company_name']) . '<div class="small text-secondary">' . e($r['position']) . '</div>' : '-' ?></td>
          <td class="text-nowrap"><?= e(TRACER_SALARY[$r['salary_range']] ?? '-') ?></td>
          <td class="text-nowrap"><?= e(TRACER_RELEVANCE[$r['job_relevance']] ?? '-') ?></td>
          <td>
            <?php if ($r['feedback']): ?>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#feedbackModal"
                      data-name="<?= e($r['full_name']) ?>" data-feedback="<?= e($r['feedback']) ?>">Baca</button>
            <?php else: ?>-<?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
  <?= render_pagination($page, $pages, ['year' => $year ?: 0], 'admin/tracer/index.php') ?>
<?php endif; ?>

<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header">
      <h2 class="modal-title h5" id="feedbackLabel">Masukan dari <span data-name></span></h2>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
    </div>
    <div class="modal-body"><p class="cover-letter mb-0" data-feedback></p></div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
  </div></div>
</div>

<?php include __DIR__ . '/../../components/dash_end.php'; ?>

<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/jobs.php';
require_role('company');

$companyId = (int) current_company_id();
$statuses  = ['pending', 'reviewed', 'accepted', 'rejected'];

/* Filter dari URL (juga dipakai untuk kembali setelah ubah status) */
$jobFilter    = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT) ?: 0;
$statusFilter = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$page         = max(1, (int) ($_GET['page'] ?? 1));

/* ---------- Ubah status lamaran ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appId     = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['status'] ?? '';
    $back = array_filter([
        'job_id' => (int) ($_POST['f_job'] ?? 0),
        'status' => in_array($_POST['f_status'] ?? '', $statuses, true) ? $_POST['f_status'] : '',
        'page'   => (int) ($_POST['f_page'] ?? 0),
    ]);

    // Lamaran harus milik lowongan perusahaan ini
    $app = $appId ? db_one('SELECT ap.id FROM job_applications ap JOIN jobs j ON j.id = ap.job_id
                            WHERE ap.id = ? AND j.company_id = ?', [$appId, $companyId]) : null;

    if (!csrf_verify()) {
        flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
    } elseif (!$app) {
        flash('danger', 'Lamaran tidak ditemukan.');
    } elseif (!in_array($newStatus, $statuses, true)) {
        flash('danger', 'Status tidak valid.');
    } else {
        db()->prepare('UPDATE job_applications SET status = ? WHERE id = ?')->execute([$newStatus, $app['id']]);
        flash('success', 'Status lamaran diperbarui.');
    }
    redirect('company/applicants.php' . ($back ? '?' . http_build_query($back) : ''));
}

/* ---------- Data ---------- */
$myJobs = db_all('SELECT id, title FROM jobs WHERE company_id = ? ORDER BY created_at DESC, id DESC', [$companyId]);

$where  = ['j.company_id = ?'];
$params = [$companyId];
if ($jobFilter > 0) {
    $where[] = 'ap.job_id = ?';
    $params[] = $jobFilter;
}
if ($statusFilter !== '') {
    $where[] = 'ap.status = ?';
    $params[] = $statusFilter;
}
$whereSql = implode(' AND ', $where);
$from = 'FROM job_applications ap JOIN jobs j ON j.id = ap.job_id JOIN alumni a ON a.id = ap.alumni_id';

$perPage = 15;
$total   = (int) db_scalar("SELECT COUNT(*) $from WHERE $whereSql", $params);
$pages   = max(1, (int) ceil($total / $perPage));
$page    = min($page, $pages);
$offset  = ($page - 1) * $perPage;

$rows = db_all("SELECT ap.id, ap.status, ap.cover_letter, ap.created_at, j.title AS job_title,
                       a.id AS alumni_id, a.full_name, a.study_program, a.graduation_year, a.verification_status
                $from WHERE $whereSql
                ORDER BY ap.created_at DESC, ap.id DESC
                LIMIT $perPage OFFSET $offset", $params);

$statusLabels = ['pending' => 'Menunggu', 'reviewed' => 'Ditinjau', 'accepted' => 'Diterima', 'rejected' => 'Ditolak'];

$pageTitle    = 'Pelamar';
$activeMenu   = 'company/applicants.php';
$extraScripts = ['assets/js/applicants.js'];
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
?>

<form method="get" class="filter-bar mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-5">
      <label for="job_id" class="form-label small mb-1">Lowongan</label>
      <select class="form-select" id="job_id" name="job_id">
        <option value="">Semua lowongan</option>
        <?php foreach ($myJobs as $mj): ?>
          <option value="<?= (int) $mj['id'] ?>" <?= $jobFilter === (int) $mj['id'] ? 'selected' : '' ?>><?= e($mj['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <label for="status" class="form-label small mb-1">Status</label>
      <select class="form-select" id="status" name="status">
        <option value="">Semua status</option>
        <?php foreach ($statusLabels as $k => $lbl): ?>
          <option value="<?= e($k) ?>" <?= $statusFilter === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-grid"><button class="btn btn-primary" type="submit">Terapkan</button></div>
  </div>
</form>

<div class="panel">
  <div class="panel-head"><h2><?= e($total) ?> pelamar</h2></div>
  <?php if (!$rows): ?>
    <div class="empty-state"><i class="bi bi-inbox"></i>Belum ada pelamar<?= ($jobFilter || $statusFilter) ? ' untuk filter ini' : '' ?>.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-clean">
        <thead><tr><th>Pelamar</th><th>Posisi</th><th>Tanggal</th><th>Berkas</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <a class="fw-semibold text-decoration-none" href="<?= e(url('alumni/profile-view.php?id=' . (int) $r['alumni_id'])) ?>"><?= e($r['full_name']) ?></a>
              <div class="small text-secondary"><?= e($r['study_program'] ?: '-') ?><?= $r['graduation_year'] ? ' &middot; lulus ' . e($r['graduation_year']) : '' ?></div>
            </td>
            <td><?= e($r['job_title']) ?></td>
            <td class="text-nowrap"><?= e(date_id($r['created_at'])) ?></td>
            <td>
              <div class="table-actions justify-content-start">
                <a class="btn btn-outline-primary btn-sm" href="<?= e(url('api/cv.php?id=' . (int) $r['id'])) ?>" target="_blank" rel="noopener">Buka CV</a>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#letterModal"
                        data-name="<?= e($r['full_name']) ?>" data-letter="<?= e($r['cover_letter'] ?: '(Tidak ada cover letter)') ?>">Cover letter</button>
              </div>
            </td>
            <td>
              <form method="post" class="d-flex gap-1 m-0">
                <?= csrf_field() ?>
                <input type="hidden" name="application_id" value="<?= (int) $r['id'] ?>">
                <input type="hidden" name="f_job" value="<?= (int) $jobFilter ?>">
                <input type="hidden" name="f_status" value="<?= e($statusFilter) ?>">
                <input type="hidden" name="f_page" value="<?= (int) $page ?>">
                <label class="visually-hidden" for="st-<?= (int) $r['id'] ?>">Status untuk <?= e($r['full_name']) ?></label>
                <select class="form-select form-select-sm" id="st-<?= (int) $r['id'] ?>" name="status" style="min-width: 120px;">
                  <?php foreach ($statusLabels as $k => $lbl): ?>
                    <option value="<?= e($k) ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
              </form>
              <div class="mt-1"><?= status_badge($r['status']) ?></div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?= render_pagination($page, $pages, ['job_id' => $jobFilter, 'status' => $statusFilter], 'company/applicants.php') ?>

<div class="modal fade" id="letterModal" tabindex="-1" aria-labelledby="letterLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="letterLabel">Cover letter &ndash; <span data-name></span></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body"><p class="cover-letter mb-0" data-letter></p></div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../components/dash_end.php'; ?>

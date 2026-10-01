<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/jobs.php';
require_role('admin');

$statuses  = ['pending', 'reviewed', 'accepted', 'rejected'];
$q         = mb_substr(trim($_GET['q'] ?? ''), 0, 100);
$status    = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$jobId     = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT) ?: 0;
$page      = max(1, (int) ($_GET['page'] ?? 1));
$perPage   = 15;

$where = []; $params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(a.full_name LIKE ? OR j.title LIKE ? OR c.name LIKE ?)';
    array_push($params, $like, $like, $like);
}
if ($status !== '') { $where[] = 'ap.status = ?'; $params[] = $status; }
if ($jobId > 0) { $where[] = 'ap.job_id = ?'; $params[] = $jobId; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$from = 'FROM job_applications ap JOIN alumni a ON a.id = ap.alumni_id JOIN jobs j ON j.id = ap.job_id JOIN companies c ON c.id = j.company_id';

$total  = (int) db_scalar("SELECT COUNT(*) $from $whereSql", $params);
$pages  = max(1, (int) ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

$rows = db_all("SELECT ap.id, ap.status, ap.cover_letter, ap.created_at, ap.alumni_id, ap.job_id,
                       a.full_name, j.title AS job_title, c.name AS company_name
                $from $whereSql ORDER BY ap.created_at DESC, ap.id DESC LIMIT $perPage OFFSET $offset", $params);
$jobTitle = $jobId ? db_scalar('SELECT title FROM jobs WHERE id = ?', [$jobId]) : null;

$statusLabels = ['pending' => 'Menunggu', 'reviewed' => 'Ditinjau', 'accepted' => 'Diterima', 'rejected' => 'Ditolak'];

$pageTitle    = 'Kelola lamaran';
$activeMenu   = 'admin/applications/index.php';
$extraScripts = ['assets/js/applicants.js'];
include __DIR__ . '/../../components/head.php';
include __DIR__ . '/../../components/sidebar.php';
?>

<?php if ($jobTitle): ?>
  <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
    <span>Menampilkan lamaran untuk <strong><?= e($jobTitle) ?></strong>.</span>
    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('admin/applications/index.php')) ?>">Hapus filter</a>
  </div>
<?php endif; ?>

<form method="get" class="filter-bar mb-3">
  <input type="hidden" name="job_id" value="<?= (int) $jobId ?>">
  <div class="row g-2">
    <div class="col-md-5"><input type="search" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari pelamar, posisi, atau perusahaan"></div>
    <div class="col-md-4">
      <select class="form-select" name="status">
        <option value="">Semua status</option>
        <?php foreach ($statusLabels as $k => $lbl): ?>
          <option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-grid"><button class="btn btn-primary" type="submit">Terapkan</button></div>
  </div>
</form>

<div class="panel">
  <div class="panel-head"><h2><?= e($total) ?> lamaran</h2></div>
  <?php if (!$rows): ?>
    <div class="empty-state"><i class="bi bi-file-earmark-person"></i>Tidak ada data yang cocok.</div>
  <?php else: ?>
    <div class="table-responsive"><table class="table table-clean">
      <thead><tr><th>Pelamar</th><th>Posisi</th><th>Tanggal</th><th>Berkas</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a class="fw-semibold text-decoration-none" href="<?= e(url('alumni/profile-view.php?id=' . (int) $r['alumni_id'])) ?>"><?= e($r['full_name']) ?></a></td>
          <td><?= e($r['job_title']) ?><div class="small text-secondary"><?= e($r['company_name']) ?></div></td>
          <td class="text-nowrap"><?= e(date_id($r['created_at'])) ?></td>
          <td>
            <div class="table-actions justify-content-start">
              <a class="btn btn-outline-primary btn-sm" href="<?= e(url('api/cv.php?id=' . (int) $r['id'])) ?>" target="_blank" rel="noopener">Buka CV</a>
              <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#letterModal"
                      data-name="<?= e($r['full_name']) ?>" data-letter="<?= e($r['cover_letter'] ?: '(Tidak ada cover letter)') ?>">Cover letter</button>
            </div>
          </td>
          <td><?= status_badge($r['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<?= render_pagination($page, $pages, ['q' => $q, 'status' => $status, 'job_id' => $jobId], 'admin/applications/index.php') ?>

<div class="modal fade" id="letterModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header"><h2 class="modal-title h5">Cover letter &ndash; <span data-name></span></h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
  <div class="modal-body"><p class="cover-letter mb-0" data-letter></p></div>
  <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
</div></div></div>

<?php include __DIR__ . '/../../components/dash_end.php'; ?>

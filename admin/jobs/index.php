<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/jobs.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id  = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
    $row = $id ? db_one('SELECT id, title FROM jobs WHERE id = ?', [$id]) : null;
    $back = $_POST['back'] ?? '';

    if (!csrf_verify()) {
        flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
    } elseif (!$row) {
        flash('danger', 'Lowongan tidak ditemukan.');
    } else {
        $cvFiles = array_column(db_all('SELECT cv_file FROM job_applications WHERE job_id = ?', [$row['id']]), 'cv_file');
        db()->prepare('DELETE FROM jobs WHERE id = ?')->execute([$row['id']]); // lamaran ikut terhapus (CASCADE)
        foreach ($cvFiles as $cv) {
            if ($cv !== 'dummy_cv.pdf') {
                delete_upload(UPLOAD_PATH . '/cv', $cv);
            }
        }
        flash('success', 'Lowongan "' . $row['title'] . '" dihapus.');
    }
    redirect('admin/jobs/index.php' . ($back ? '?' . $back : ''));
}

$statuses = ['open', 'closed'];
$q         = mb_substr(trim($_GET['q'] ?? ''), 0, 100);
$status    = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$companyId = filter_input(INPUT_GET, 'company_id', FILTER_VALIDATE_INT) ?: 0;
$page      = max(1, (int) ($_GET['page'] ?? 1));
$perPage   = 12;

$where = []; $params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(j.title LIKE ? OR c.name LIKE ?)';
    array_push($params, $like, $like);
}
if ($status !== '') { $where[] = 'j.status = ?'; $params[] = $status; }
if ($companyId > 0) { $where[] = 'j.company_id = ?'; $params[] = $companyId; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$from = 'FROM jobs j JOIN companies c ON c.id = j.company_id JOIN job_categories k ON k.id = j.category_id';

$total  = (int) db_scalar("SELECT COUNT(*) $from $whereSql", $params);
$pages  = max(1, (int) ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

$rows = db_all("SELECT j.id, j.title, j.location, j.job_type, j.deadline, j.status, c.name AS company_name, k.name AS category_name,
                       (SELECT COUNT(*) FROM job_applications ap WHERE ap.job_id = j.id) AS applicants
                $from $whereSql ORDER BY j.id DESC LIMIT $perPage OFFSET $offset", $params);
$companyName = $companyId ? db_scalar('SELECT name FROM companies WHERE id = ?', [$companyId]) : null;
$backQs = http_build_query(array_filter(['q' => $q, 'status' => $status, 'company_id' => $companyId, 'page' => $page]));
$today = date('Y-m-d');

$pageTitle    = 'Kelola lowongan';
$activeMenu   = 'admin/jobs/index.php';
$extraScripts = ['assets/js/admin-delete.js'];
include __DIR__ . '/../../components/head.php';
include __DIR__ . '/../../components/sidebar.php';
?>

<?php if ($companyName): ?>
  <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
    <span>Menampilkan lowongan dari <strong><?= e($companyName) ?></strong>.</span>
    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('admin/jobs/index.php')) ?>">Hapus filter</a>
  </div>
<?php endif; ?>

<form method="get" class="filter-bar mb-3">
  <input type="hidden" name="company_id" value="<?= (int) $companyId ?>">
  <div class="row g-2">
    <div class="col-md-5"><input type="search" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari judul atau perusahaan"></div>
    <div class="col-md-4">
      <select class="form-select" name="status">
        <option value="">Semua status</option>
        <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Dibuka</option>
        <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Ditutup</option>
      </select>
    </div>
    <div class="col-md-3 d-grid"><button class="btn btn-primary" type="submit">Terapkan</button></div>
  </div>
</form>

<div class="panel">
  <div class="panel-head"><h2><?= e($total) ?> lowongan</h2></div>
  <?php if (!$rows): ?>
    <div class="empty-state"><i class="bi bi-briefcase"></i>Tidak ada data yang cocok.</div>
  <?php else: ?>
    <div class="table-responsive"><table class="table table-clean">
      <thead><tr><th>Posisi</th><th>Perusahaan</th><th>Deadline</th><th>Pelamar</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $expired = $r['status'] === 'open' && $r['deadline'] < $today; ?>
        <tr>
          <td><strong class="d-block"><?= e($r['title']) ?></strong><span class="small text-secondary"><?= e($r['location']) ?> &middot; <?= e($r['category_name']) ?></span></td>
          <td><?= e($r['company_name']) ?></td>
          <td class="text-nowrap"><?= e(date_id($r['deadline'])) ?></td>
          <td><a href="<?= e(url('admin/applications/index.php?job_id=' . (int) $r['id'])) ?>"><?= (int) $r['applicants'] ?></a></td>
          <td><?= status_badge($expired ? 'expired' : $r['status']) ?></td>
          <td>
            <button type="button" class="btn btn-outline-danger btn-sm js-delete" data-bs-toggle="modal" data-bs-target="#delModal"
                    data-name="<?= e($r['title']) ?>" data-id="<?= (int) $r['id'] ?>" data-field="job_id" data-back="<?= e($backQs) ?>">Hapus</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<?= render_pagination($page, $pages, ['q' => $q, 'status' => $status, 'company_id' => $companyId], 'admin/jobs/index.php') ?>

<div class="modal fade" id="delModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="post" class="modal-content">
  <?= csrf_field() ?><input type="hidden" name="job_id" value=""><input type="hidden" name="back" value="">
  <div class="modal-header"><h2 class="modal-title h5">Hapus lowongan</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
  <div class="modal-body"><p class="mb-0">Hapus lowongan <strong data-name></strong>? Semua lamaran yang masuk ikut terhapus permanen.</p></div>
  <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, hapus</button></div>
</form></div></div>

<?php include __DIR__ . '/../../components/dash_end.php'; ?>

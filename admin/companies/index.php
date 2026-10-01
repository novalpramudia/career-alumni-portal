<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/jobs.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id  = filter_input(INPUT_POST, 'company_id', FILTER_VALIDATE_INT);
    $row = $id ? db_one('SELECT c.id, c.name, c.logo, u.id AS user_id FROM companies c JOIN users u ON u.id = c.user_id WHERE c.id = ?', [$id]) : null;
    $back = $_POST['back'] ?? '';

    if (!csrf_verify()) {
        flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
    } elseif (!$row) {
        flash('danger', 'Perusahaan tidak ditemukan.');
    } else {
        $cvFiles = array_column(db_all(
            'SELECT ap.cv_file FROM job_applications ap JOIN jobs j ON j.id = ap.job_id WHERE j.company_id = ?', [$row['id']]
        ), 'cv_file');
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$row['user_id']]); // perusahaan, lowongan, lamaran ikut terhapus (CASCADE)
        delete_upload(UPLOAD_PATH . '/company', $row['logo']);
        foreach ($cvFiles as $cv) {
            if ($cv !== 'dummy_cv.pdf') {
                delete_upload(UPLOAD_PATH . '/cv', $cv);
            }
        }
        flash('success', 'Perusahaan ' . $row['name'] . ' dihapus.');
    }
    redirect('admin/companies/index.php' . ($back ? '?' . $back : ''));
}

$q = mb_substr(trim($_GET['q'] ?? ''), 0, 100);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

$where = []; $params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(c.name LIKE ? OR c.industry LIKE ? OR u.email LIKE ?)';
    array_push($params, $like, $like, $like);
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$from = 'FROM companies c JOIN users u ON u.id = c.user_id';

$total  = (int) db_scalar("SELECT COUNT(*) $from $whereSql", $params);
$pages  = max(1, (int) ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

$rows = db_all("SELECT c.id, c.name, c.logo, c.industry, u.email,
                       (SELECT COUNT(*) FROM jobs j WHERE j.company_id = c.id) AS total_jobs,
                       (SELECT COUNT(*) FROM jobs j WHERE j.company_id = c.id AND j.status = 'open' AND j.deadline >= CURDATE()) AS open_jobs
                $from $whereSql ORDER BY c.id DESC LIMIT $perPage OFFSET $offset", $params);
$backQs = http_build_query(array_filter(['q' => $q, 'page' => $page]));

$pageTitle    = 'Kelola perusahaan';
$activeMenu   = 'admin/companies/index.php';
$extraScripts = ['assets/js/admin-delete.js'];
include __DIR__ . '/../../components/head.php';
include __DIR__ . '/../../components/sidebar.php';
?>

<form method="get" class="filter-bar mb-3 d-flex gap-2">
  <input type="search" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama, industri, atau email">
  <button class="btn btn-primary" type="submit">Cari</button>
</form>

<div class="panel">
  <div class="panel-head"><h2><?= e($total) ?> perusahaan</h2></div>
  <?php if (!$rows): ?>
    <div class="empty-state"><i class="bi bi-buildings"></i>Tidak ada data yang cocok.</div>
  <?php else: ?>
    <div class="table-responsive"><table class="table table-clean">
      <thead><tr><th>Perusahaan</th><th>Industri</th><th>Lowongan</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <div class="d-flex gap-2 align-items-center">
              <?= avatar_html($r['name'], $r['logo'], 'avatar-sm') ?>
              <div class="min-w-0">
                <a class="fw-semibold text-decoration-none" href="<?= e(url('company/profile-view.php?id=' . (int) $r['id'])) ?>"><?= e($r['name']) ?></a>
                <div class="small text-secondary"><?= e($r['email']) ?></div>
              </div>
            </div>
          </td>
          <td><?= e($r['industry'] ?: '-') ?></td>
          <td><?= (int) $r['open_jobs'] ?> aktif <span class="text-secondary small">/ <?= (int) $r['total_jobs'] ?> total</span></td>
          <td>
            <div class="table-actions">
              <a class="btn btn-outline-primary btn-sm" href="<?= e(url('admin/jobs/index.php?company_id=' . (int) $r['id'])) ?>">Lihat lowongan</a>
              <button type="button" class="btn btn-outline-danger btn-sm js-delete" data-bs-toggle="modal" data-bs-target="#delModal"
                      data-name="<?= e($r['name']) ?>" data-id="<?= (int) $r['id'] ?>" data-field="company_id" data-back="<?= e($backQs) ?>">Hapus</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<?= render_pagination($page, $pages, ['q' => $q], 'admin/companies/index.php') ?>

<div class="modal fade" id="delModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="post" class="modal-content">
  <?= csrf_field() ?><input type="hidden" name="company_id" value=""><input type="hidden" name="back" value="">
  <div class="modal-header"><h2 class="modal-title h5">Hapus akun perusahaan</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
  <div class="modal-body"><p class="mb-0">Hapus <strong data-name></strong>? Seluruh lowongan dan lamaran yang masuk ikut terhapus permanen.</p></div>
  <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, hapus</button></div>
</form></div></div>

<?php include __DIR__ . '/../../components/dash_end.php'; ?>

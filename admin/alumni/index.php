<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/jobs.php'; // render_pagination()
require_role('admin');

/* ---------- Aksi: verifikasi / tolak / hapus ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = filter_input(INPUT_POST, 'alumni_id', FILTER_VALIDATE_INT);
    $row    = $id ? db_one('SELECT a.id, a.full_name, a.photo, u.id AS user_id FROM alumni a JOIN users u ON u.id = a.user_id WHERE a.id = ?', [$id]) : null;
    $back   = $_POST['back'] ?? '';

    if (!csrf_verify()) {
        flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
    } elseif (!$row) {
        flash('danger', 'Data alumni tidak ditemukan.');
    } elseif ($action === 'verify') {
        db()->prepare("UPDATE alumni SET verification_status = 'verified' WHERE id = ?")->execute([$row['id']]);
        flash('success', 'Data ' . $row['full_name'] . ' diverifikasi.');
    } elseif ($action === 'reject') {
        db()->prepare("UPDATE alumni SET verification_status = 'rejected' WHERE id = ?")->execute([$row['id']]);
        flash('success', 'Data ' . $row['full_name'] . ' ditolak.');
    } elseif ($action === 'delete') {
        $cvFiles = array_column(db_all('SELECT cv_file FROM job_applications WHERE alumni_id = ?', [$row['id']]), 'cv_file');
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([$row['user_id']]); // alumni, lamaran, tracer ikut terhapus (CASCADE)
        delete_upload(UPLOAD_PATH . '/profile', $row['photo']);
        foreach ($cvFiles as $cv) {
            if ($cv !== 'dummy_cv.pdf') {
                delete_upload(UPLOAD_PATH . '/cv', $cv);
            }
        }
        flash('success', 'Akun ' . $row['full_name'] . ' dihapus.');
    }
    redirect('admin/alumni/index.php' . ($back ? '?' . $back : ''));
}

$statuses = ['pending', 'verified', 'rejected'];
$q        = mb_substr(trim($_GET['q'] ?? ''), 0, 100);
$status   = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = 12;

$where = []; $params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(a.full_name LIKE ? OR a.nim LIKE ? OR u.email LIKE ?)';
    array_push($params, $like, $like, $like);
}
if ($status !== '') {
    $where[] = 'a.verification_status = ?';
    $params[] = $status;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$from = 'FROM alumni a JOIN users u ON u.id = a.user_id';

$total  = (int) db_scalar("SELECT COUNT(*) $from $whereSql", $params);
$pages  = max(1, (int) ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

$rows = db_all("SELECT a.id, a.full_name, a.nim, a.photo, a.study_program, a.graduation_year, a.verification_status, u.email
               $from $whereSql ORDER BY a.id DESC LIMIT $perPage OFFSET $offset", $params);

$counts = array_fill_keys($statuses, 0);
foreach (db_all('SELECT verification_status AS k, COUNT(*) AS n FROM alumni GROUP BY verification_status') as $r) {
    $counts[$r['k']] = (int) $r['n'];
}
$tabs = ['' => ['Semua', array_sum($counts)], 'pending' => ['Menunggu', $counts['pending']],
         'verified' => ['Terverifikasi', $counts['verified']], 'rejected' => ['Ditolak', $counts['rejected']]];
$backQs = http_build_query(array_filter(['q' => $q, 'status' => $status, 'page' => $page]));

$pageTitle    = 'Kelola alumni';
$activeMenu   = 'admin/alumni/index.php';
$extraScripts = ['assets/js/admin-delete.js'];
include __DIR__ . '/../../components/head.php';
include __DIR__ . '/../../components/sidebar.php';
?>

<ul class="nav nav-pills mb-3 gap-1">
  <?php foreach ($tabs as $key => [$label, $n]): ?>
    <li class="nav-item"><a class="nav-link <?= $status === $key ? 'active' : '' ?>"
       href="<?= e(url('admin/alumni/index.php' . ($key !== '' ? '?status=' . $key : ''))) ?>"><?= e($label) ?> <span class="badge text-bg-light"><?= (int) $n ?></span></a></li>
  <?php endforeach; ?>
</ul>

<form method="get" class="filter-bar mb-3 d-flex gap-2">
  <input type="hidden" name="status" value="<?= e($status) ?>">
  <input type="search" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama, NIM, atau email">
  <button class="btn btn-primary" type="submit">Cari</button>
</form>

<div class="panel">
  <div class="panel-head"><h2><?= e($total) ?> alumni</h2></div>
  <?php if (!$rows): ?>
    <div class="empty-state"><i class="bi bi-mortarboard"></i>Tidak ada data yang cocok.</div>
  <?php else: ?>
    <div class="table-responsive"><table class="table table-clean">
      <thead><tr><th>Nama</th><th>NIM</th><th>Program studi</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <div class="d-flex gap-2 align-items-center">
              <?= avatar_html($r['full_name'], null, 'avatar-sm') ?>
              <div class="min-w-0">
                <a class="fw-semibold text-decoration-none" href="<?= e(url('alumni/profile-view.php?id=' . (int) $r['id'])) ?>"><?= e($r['full_name']) ?></a>
                <div class="small text-secondary"><?= e($r['email']) ?></div>
              </div>
            </div>
          </td>
          <td><?= e($r['nim'] ?: '-') ?></td>
          <td><?= e($r['study_program'] ?: '-') ?> <?= $r['graduation_year'] ? '(' . e($r['graduation_year']) . ')' : '' ?></td>
          <td><?= status_badge($r['verification_status']) ?></td>
          <td>
            <div class="table-actions">
              <?php if ($r['verification_status'] !== 'verified'): ?>
              <form method="post" class="m-0"><?= csrf_field() ?><input type="hidden" name="action" value="verify"><input type="hidden" name="alumni_id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="back" value="<?= e($backQs) ?>">
                <button type="submit" class="btn btn-outline-success btn-sm">Verifikasi</button></form>
              <?php endif; ?>
              <?php if ($r['verification_status'] !== 'rejected'): ?>
              <form method="post" class="m-0"><?= csrf_field() ?><input type="hidden" name="action" value="reject"><input type="hidden" name="alumni_id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="back" value="<?= e($backQs) ?>">
                <button type="submit" class="btn btn-outline-warning btn-sm">Tolak</button></form>
              <?php endif; ?>
              <button type="button" class="btn btn-outline-danger btn-sm js-delete" data-bs-toggle="modal" data-bs-target="#delModal"
                      data-name="<?= e($r['full_name']) ?>" data-id="<?= (int) $r['id'] ?>" data-field="alumni_id" data-back="<?= e($backQs) ?>">Hapus</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>
<?= render_pagination($page, $pages, ['q' => $q, 'status' => $status], 'admin/alumni/index.php') ?>

<div class="modal fade" id="delModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="post" class="modal-content">
  <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="alumni_id" value=""><input type="hidden" name="back" value="">
  <div class="modal-header"><h2 class="modal-title h5">Hapus akun alumni</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
  <div class="modal-body"><p class="mb-0">Hapus akun <strong data-name></strong>? Profil, lamaran, dan tracer study miliknya ikut terhapus permanen.</p></div>
  <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, hapus</button></div>
</form></div></div>

<?php include __DIR__ . '/../../components/dash_end.php'; ?>

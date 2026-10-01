<?php
require_once __DIR__ . '/../config/auth.php';
require_role('alumni');

$alumniId = (int) current_alumni_id();
$statuses = ['pending', 'reviewed', 'accepted', 'rejected'];

/* ---------- Tarik lamaran (hanya yang masih menunggu) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appId = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
    $app = $appId ? db_one('SELECT id, cv_file, status FROM job_applications WHERE id = ? AND alumni_id = ?', [$appId, $alumniId]) : null;

    if (!csrf_verify()) {
        flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
    } elseif (!$app) {
        flash('danger', 'Lamaran tidak ditemukan.');
    } elseif ($app['status'] !== 'pending') {
        flash('warning', 'Lamaran yang sudah ditinjau perusahaan tidak bisa ditarik.');
    } else {
        db()->prepare('DELETE FROM job_applications WHERE id = ? AND alumni_id = ?')->execute([$app['id'], $alumniId]);
        if ($app['cv_file'] !== 'dummy_cv.pdf') {
            delete_upload(UPLOAD_PATH . '/cv', $app['cv_file']);
        }
        flash('success', 'Lamaran berhasil ditarik.');
    }
    redirect('alumni/applications.php');
}

$filter = $_GET['status'] ?? '';
if (!in_array($filter, $statuses, true)) {
    $filter = '';
}

$counts = array_fill_keys($statuses, 0);
foreach (db_all('SELECT status, COUNT(*) AS n FROM job_applications WHERE alumni_id = ? GROUP BY status', [$alumniId]) as $r) {
    $counts[$r['status']] = (int) $r['n'];
}
$totalAll = array_sum($counts);

$sql = 'SELECT ap.id, ap.status, ap.created_at, ap.updated_at, j.id AS job_id, j.title, j.location,
               c.name AS company_name, c.logo AS company_logo
        FROM job_applications ap
        JOIN jobs j ON j.id = ap.job_id
        JOIN companies c ON c.id = j.company_id
        WHERE ap.alumni_id = ?';
$params = [$alumniId];
if ($filter !== '') {
    $sql .= ' AND ap.status = ?';
    $params[] = $filter;
}
$apps = db_all($sql . ' ORDER BY ap.created_at DESC, ap.id DESC', $params);

$tabs = ['' => ['Semua', $totalAll], 'pending' => ['Menunggu', $counts['pending']], 'reviewed' => ['Ditinjau', $counts['reviewed']],
         'accepted' => ['Diterima', $counts['accepted']], 'rejected' => ['Ditolak', $counts['rejected']]];

$pageTitle    = 'Lamaran saya';
$activeMenu   = 'alumni/applications.php';
$extraScripts = ['assets/js/applications.js'];
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
?>

<ul class="nav nav-pills mb-3 gap-1" aria-label="Filter status lamaran">
  <?php foreach ($tabs as $key => [$label, $n]): ?>
    <li class="nav-item">
      <a class="nav-link <?= $filter === $key ? 'active' : '' ?>" href="<?= e(url('alumni/applications.php' . ($key !== '' ? '?status=' . $key : ''))) ?>">
        <?= e($label) ?> <span class="badge text-bg-light"><?= (int) $n ?></span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="panel">
  <?php if (!$apps): ?>
    <div class="empty-state">
      <i class="bi bi-file-earmark-person"></i>
      <?= $totalAll === 0 ? 'Anda belum melamar pekerjaan apa pun.' : 'Tidak ada lamaran dengan status ini.' ?>
      <div class="mt-3"><a class="btn btn-primary btn-sm" href="<?= e(url('alumni/jobs.php')) ?>">Cari lowongan</a></div>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-clean">
        <thead><tr><th>Posisi</th><th>Tanggal melamar</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($apps as $a): ?>
          <tr>
            <td>
              <div class="d-flex gap-3 align-items-center">
                <?= avatar_html($a['company_name'], $a['company_logo']) ?>
                <div class="min-w-0">
                  <a class="fw-semibold text-decoration-none" href="<?= e(url('alumni/job-detail.php?id=' . (int) $a['job_id'])) ?>"><?= e($a['title']) ?></a>
                  <div class="small text-secondary"><?= e($a['company_name']) ?> &middot; <?= e($a['location']) ?></div>
                </div>
              </div>
            </td>
            <td class="text-nowrap"><?= e(date_id($a['created_at'])) ?></td>
            <td>
              <?= status_badge($a['status']) ?>
              <?php if ($a['status'] !== 'pending'): ?><div class="small text-secondary mt-1">Diperbarui <?= e(date_id($a['updated_at'])) ?></div><?php endif; ?>
            </td>
            <td>
              <div class="table-actions">
                <a class="btn btn-outline-primary btn-sm" href="<?= e(url('api/cv.php?id=' . (int) $a['id'])) ?>" target="_blank" rel="noopener">CV saya</a>
                <?php if ($a['status'] === 'pending'): ?>
                  <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#withdrawModal"
                          data-app-id="<?= (int) $a['id'] ?>" data-app-title="<?= e($a['title']) ?>">Tarik</button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="modal fade" id="withdrawModal" tabindex="-1" aria-labelledby="withdrawLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <?= csrf_field() ?>
      <input type="hidden" name="application_id" value="">
      <div class="modal-header">
        <h2 class="modal-title h5" id="withdrawLabel">Tarik lamaran</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Tarik lamaran untuk <strong data-app-title></strong>? CV dan cover letter Anda akan dihapus. Anda bisa melamar lagi selama lowongan masih dibuka.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-danger">Ya, tarik lamaran</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../components/dash_end.php'; ?>

<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/jobs.php';
require_role('company');

$companyId = (int) current_company_id();

/* ---------- Aksi: hapus & buka/tutup lowongan (hanya milik sendiri) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $jobId  = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
    $job    = $jobId ? db_one('SELECT id, title, status, deadline FROM jobs WHERE id = ? AND company_id = ?', [$jobId, $companyId]) : null;

    if (!csrf_verify()) {
        flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
    } elseif (!$job) {
        flash('danger', 'Lowongan tidak ditemukan atau bukan milik perusahaan Anda.');
    } elseif ($action === 'delete') {
        $cvFiles = array_column(db_all('SELECT cv_file FROM job_applications WHERE job_id = ?', [$job['id']]), 'cv_file');
        db()->prepare('DELETE FROM jobs WHERE id = ? AND company_id = ?')->execute([$job['id'], $companyId]); // lamaran ikut terhapus (CASCADE)
        foreach ($cvFiles as $cv) {
            if ($cv !== 'dummy_cv.pdf') {
                delete_upload(UPLOAD_PATH . '/cv', $cv);
            }
        }
        flash('success', 'Lowongan "' . $job['title'] . '" dihapus.');
    } elseif ($action === 'toggle') {
        if ($job['status'] === 'open') {
            db()->prepare("UPDATE jobs SET status = 'closed' WHERE id = ? AND company_id = ?")->execute([$job['id'], $companyId]);
            flash('success', 'Lowongan ditutup.');
        } elseif ($job['deadline'] < date('Y-m-d')) {
            flash('warning', 'Deadline sudah lewat. Ubah deadline lewat menu Edit sebelum membuka kembali.');
        } else {
            db()->prepare("UPDATE jobs SET status = 'open' WHERE id = ? AND company_id = ?")->execute([$job['id'], $companyId]);
            flash('success', 'Lowongan dibuka kembali.');
        }
    }
    redirect('company/jobs.php');
}

$jobs = db_all('SELECT j.id, j.title, j.location, j.job_type, j.deadline, j.status, k.name AS category_name,
                       (SELECT COUNT(*) FROM job_applications ap WHERE ap.job_id = j.id) AS applicants
                FROM jobs j
                JOIN job_categories k ON k.id = j.category_id
                WHERE j.company_id = ?
                ORDER BY j.created_at DESC, j.id DESC', [$companyId]);

$applicantsPage = is_file(__DIR__ . '/applicants.php');
$today = date('Y-m-d');

$pageTitle    = 'Lowongan saya';
$activeMenu   = 'company/jobs.php';
$extraScripts = ['assets/js/company-jobs.js'];
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
?>

<div class="panel">
  <div class="panel-head">
    <h2><?= count($jobs) ?> lowongan</h2>
    <a class="btn btn-primary btn-sm" href="<?= e(url('company/create-job.php')) ?>"><i class="bi bi-plus-lg"></i> Buat lowongan</a>
  </div>

  <?php if (!$jobs): ?>
    <div class="empty-state"><i class="bi bi-briefcase"></i>Anda belum membuat lowongan. Buat lowongan pertama untuk mulai menerima lamaran.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-clean">
        <thead><tr><th>Posisi</th><th>Deadline</th><th>Pelamar</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($jobs as $j):
            $expired = $j['status'] === 'open' && $j['deadline'] < $today; ?>
          <tr>
            <td>
              <strong class="d-block"><?= e($j['title']) ?></strong>
              <span class="small text-secondary"><?= e($j['location']) ?> &middot; <?= e(job_type_label($j['job_type'])) ?> &middot; <?= e($j['category_name']) ?></span>
            </td>
            <td class="text-nowrap"><?= e(date_id($j['deadline'])) ?></td>
            <td>
              <?php if ($applicantsPage): ?>
                <a href="<?= e(url('company/applicants.php?job_id=' . (int) $j['id'])) ?>"><?= (int) $j['applicants'] ?></a>
              <?php else: ?><?= (int) $j['applicants'] ?><?php endif; ?>
            </td>
            <td><?= status_badge($expired ? 'expired' : $j['status']) ?></td>
            <td>
              <div class="table-actions">
                <a class="btn btn-outline-primary btn-sm" href="<?= e(url('company/edit-job.php?id=' . (int) $j['id'])) ?>">Edit</a>
                <form method="post" class="m-0">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="job_id" value="<?= (int) $j['id'] ?>">
                  <button type="submit" class="btn btn-outline-secondary btn-sm"><?= $j['status'] === 'open' ? 'Tutup' : 'Buka' ?></button>
                </form>
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteJobModal"
                        data-job-id="<?= (int) $j['id'] ?>" data-job-title="<?= e($j['title']) ?>">Hapus</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="modal fade" id="deleteJobModal" tabindex="-1" aria-labelledby="deleteJobLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="job_id" value="">
      <div class="modal-header">
        <h2 class="modal-title h5" id="deleteJobLabel">Hapus lowongan</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">Hapus lowongan <strong data-job-title></strong>? Semua lamaran yang masuk ke lowongan ini juga ikut terhapus dan tidak bisa dikembalikan.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-danger">Ya, hapus</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../components/dash_end.php'; ?>

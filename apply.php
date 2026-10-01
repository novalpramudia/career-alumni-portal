<?php
require_once __DIR__ . '/../config/auth.php';
require_role('alumni');

const CV_MAX_BYTES = 2 * 1024 * 1024; // 2 MB
const CV_TYPES     = ['application/pdf' => 'pdf'];

$jobId = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
$job = $jobId ? db_one('SELECT j.id, j.title, j.status, j.deadline, j.location, j.job_type,
                               c.name AS company_name, c.logo AS company_logo
                        FROM jobs j JOIN companies c ON c.id = j.company_id WHERE j.id = ?', [$jobId]) : null;
if (!$job) {
    flash('warning', 'Lowongan tidak ditemukan.');
    redirect('alumni/jobs.php');
}
if ($job['status'] !== 'open' || $job['deadline'] < date('Y-m-d')) {
    flash('warning', 'Lowongan ini sudah ditutup dan tidak menerima lamaran.');
    redirect('alumni/job-detail.php?id=' . $job['id']);
}

$alumniId = (int) current_alumni_id();
if (db_scalar('SELECT COUNT(*) FROM job_applications WHERE job_id = ? AND alumni_id = ?', [$job['id'], $alumniId])) {
    flash('info', 'Anda sudah melamar ke lowongan ini.');
    redirect('alumni/applications.php');
}

$errors = [];
$coverLetter = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $coverLetter = trim($_POST['cover_letter'] ?? '');

    if (!csrf_verify()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    }
    if (mb_strlen($coverLetter) < 20 || mb_strlen($coverLetter) > 3000) {
        $errors[] = 'Cover letter harus 20 sampai 3000 karakter.';
    }
    if (!isset($_FILES['cv']) || $_FILES['cv']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Pilih file CV dalam format PDF.';
    }

    $cvName = null;
    if (!$errors) {
        $up = handle_upload($_FILES['cv'], UPLOAD_PATH . '/cv', CV_TYPES, CV_MAX_BYTES);
        if (!$up['ok']) {
            $errors[] = $up['error'];
        } else {
            $cvName = $up['filename'];
        }
    }

    if (!$errors) {
        try {
            db()->prepare('INSERT INTO job_applications (job_id, alumni_id, cv_file, cover_letter) VALUES (?, ?, ?, ?)')
                ->execute([$job['id'], $alumniId, $cvName, $coverLetter]);
            flash('success', 'Lamaran berhasil dikirim. Pantau statusnya di halaman ini.');
            redirect('alumni/applications.php');
        } catch (PDOException $ex) {
            delete_upload(UPLOAD_PATH . '/cv', $cvName); // batalkan file yang sudah tersimpan
            if ($ex->getCode() === '23000') { // unique (job_id, alumni_id)
                flash('info', 'Anda sudah melamar ke lowongan ini.');
                redirect('alumni/applications.php');
            }
            error_log('Apply error: ' . $ex->getMessage());
            $errors[] = 'Lamaran gagal dikirim. Coba lagi beberapa saat.';
        }
    }
}

$alumni = db_one('SELECT * FROM alumni WHERE id = ?', [$alumniId]) ?? [];
$comp   = alumni_completion($alumni);

$pageTitle    = 'Lamar pekerjaan';
$activeMenu   = 'alumni/jobs.php';
$extraScripts = ['assets/js/apply.js'];
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
?>

<a href="<?= e(url('alumni/job-detail.php?id=' . (int) $job['id'])) ?>" class="btn btn-outline-secondary btn-sm mb-3"><i class="bi bi-arrow-left"></i> Kembali ke lowongan</a>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger" role="alert"><?= e($err) ?></div>
<?php endforeach; ?>

<?php if ($comp['percent'] < 60): ?>
  <div class="alert alert-info" role="alert">
    Profil Anda baru <?= e($comp['percent']) ?>% lengkap. Perusahaan melihat profil Anda saat menilai lamaran,
    jadi sebaiknya <a href="<?= e(url('alumni/profile.php')) ?>">lengkapi profil</a> dulu.
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-4 order-lg-2">
    <div class="panel"><div class="panel-body">
      <div class="d-flex gap-3 align-items-center mb-3">
        <?= avatar_html($job['company_name'], $job['company_logo']) ?>
        <div class="min-w-0"><strong class="d-block"><?= e($job['title']) ?></strong><span class="small text-secondary"><?= e($job['company_name']) ?></span></div>
      </div>
      <div class="job-meta mt-0">
        <span><i class="bi bi-geo-alt"></i> <?= e($job['location']) ?></span>
        <span><i class="bi bi-briefcase"></i> <?= e(job_type_label($job['job_type'])) ?></span>
      </div>
      <p class="small text-secondary mt-3 mb-0">Batas lamaran: <?= e(date_id($job['deadline'])) ?></p>
    </div></div>
  </div>

  <div class="col-lg-8 order-lg-1">
    <form method="post" enctype="multipart/form-data" class="panel" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
      <div class="panel-body">
        <div class="mb-4">
          <label for="cv" class="form-label">CV (PDF)</label>
          <input type="file" class="form-control" id="cv" name="cv" accept="application/pdf,.pdf" aria-describedby="cvHelp" required>
          <div id="cvHelp" class="form-text">Format PDF, maksimal 2 MB.</div>
          <div id="cvError" class="text-danger small" role="alert"></div>
        </div>
        <div class="mb-4">
          <label for="cover_letter" class="form-label">Cover letter</label>
          <textarea class="form-control" id="cover_letter" name="cover_letter" rows="8" maxlength="3000" aria-describedby="clHelp" required><?= e($coverLetter) ?></textarea>
          <div id="clHelp" class="form-text"><span id="clCount"><?= mb_strlen($coverLetter) ?></span>/3000 karakter. Jelaskan singkat mengapa Anda cocok untuk posisi ini.</div>
        </div>
        <div class="d-flex justify-content-end gap-2">
          <a href="<?= e(url('alumni/job-detail.php?id=' . (int) $job['id'])) ?>" class="btn btn-outline-secondary">Batal</a>
          <button type="submit" class="btn btn-accent px-4">Kirim lamaran</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../components/dash_end.php'; ?>

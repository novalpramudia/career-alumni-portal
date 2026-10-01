<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/tracer.php';
require_role('alumni');

$alumniId = (int) current_alumni_id();
$alumni   = db_one('SELECT * FROM alumni WHERE id = ?', [$alumniId]) ?? [];
$existing = db_one('SELECT * FROM tracer_studies WHERE alumni_id = ?', [$alumniId]);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = [];
    foreach (['graduation_year', 'employment_status', 'company_name', 'position', 'job_field',
              'waiting_time', 'salary_range', 'job_relevance', 'feedback'] as $f) {
        $in[$f] = trim((string) ($_POST[$f] ?? ''));
    }
    $working = in_array($in['employment_status'], TRACER_WORKING, true);

    if (!csrf_verify()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    }
    if (!ctype_digit($in['graduation_year']) || (int) $in['graduation_year'] < 1990 || (int) $in['graduation_year'] > (int) date('Y') + 1) {
        $errors[] = 'Tahun lulus tidak valid.';
    }
    if (!in_array($in['employment_status'], TRACER_EMPLOYMENT, true)) {
        $errors[] = 'Pilih status pekerjaan Anda saat ini.';
    }
    if ($working) {
        if ($in['company_name'] === '' || mb_strlen($in['company_name']) > 150) {
            $errors[] = 'Nama perusahaan wajib diisi (maksimal 150 karakter).';
        }
        if ($in['position'] === '' || mb_strlen($in['position']) > 100) {
            $errors[] = 'Jabatan wajib diisi (maksimal 100 karakter).';
        }
        if ($in['job_field'] === '' || mb_strlen($in['job_field']) > 100) {
            $errors[] = 'Bidang pekerjaan wajib diisi (maksimal 100 karakter).';
        }
        if (!array_key_exists($in['waiting_time'], TRACER_WAITING)) {
            $errors[] = 'Pilih lama waktu mendapatkan pekerjaan.';
        }
        if (!array_key_exists($in['salary_range'], TRACER_SALARY)) {
            $errors[] = 'Pilih rentang penghasilan.';
        }
        if (!array_key_exists($in['job_relevance'], TRACER_RELEVANCE)) {
            $errors[] = 'Pilih tingkat kesesuaian pekerjaan dengan jurusan.';
        }
    }
    if (mb_strlen($in['feedback']) > 2000) {
        $errors[] = 'Masukan untuk kampus maksimal 2000 karakter.';
    }

    if (!$errors) {
        // Bagian pekerjaan hanya disimpan jika alumni bekerja/wirausaha
        $row = [
            (int) $in['graduation_year'], $in['employment_status'],
            $working ? $in['company_name'] : null, $working ? $in['position'] : null, $working ? $in['job_field'] : null,
            $working ? $in['waiting_time'] : null, $working ? $in['salary_range'] : null, $working ? $in['job_relevance'] : null,
            nullable($in['feedback']),
        ];
        $pdo = db();
        try {
            $pdo->beginTransaction();
            if ($existing) {
                $pdo->prepare('UPDATE tracer_studies SET graduation_year = ?, employment_status = ?, company_name = ?, position = ?, job_field = ?,
                               waiting_time = ?, salary_range = ?, job_relevance = ?, feedback = ? WHERE id = ? AND alumni_id = ?')
                    ->execute(array_merge($row, [$existing['id'], $alumniId]));
            } else {
                $pdo->prepare('INSERT INTO tracer_studies (graduation_year, employment_status, company_name, position, job_field,
                               waiting_time, salary_range, job_relevance, feedback, alumni_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute(array_merge($row, [$alumniId]));
            }
            // Opsional: samakan data pekerjaan di profil
            if (isset($_POST['sync_profile'])) {
                $pdo->prepare('UPDATE alumni SET employment_status = ?, company_name = ?, position = ? WHERE id = ?')
                    ->execute([$in['employment_status'], $working ? $in['company_name'] : null, $working ? $in['position'] : null, $alumniId]);
            }
            $pdo->commit();
            flash('success', $existing ? 'Tracer study berhasil diperbarui. Terima kasih atas partisipasi Anda.' : 'Tracer study berhasil dikirim. Terima kasih atas partisipasi Anda.');
            redirect('alumni/tracer.php');
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Tracer save error: ' . $ex->getMessage());
            $errors[] = 'Data gagal disimpan. Coba lagi beberapa saat.';
        }
    }
}

/* Nilai awal form: hasil POST > tracer sebelumnya > data profil */
$t = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST
   : ($existing ?: [
        'graduation_year'   => $alumni['graduation_year'] ?? '',
        'employment_status' => $alumni['employment_status'] ?? '',
        'company_name'      => $alumni['company_name'] ?? '',
        'position'          => $alumni['position'] ?? '',
    ]);
$fv = fn(string $k) => e($t[$k] ?? '');
$syncChecked = $_SERVER['REQUEST_METHOD'] === 'POST' ? isset($_POST['sync_profile']) : true;

$pageTitle    = 'Tracer study';
$activeMenu   = 'alumni/tracer.php';
$extraScripts = ['assets/js/tracer.js'];
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
?>

<?php if ($existing): ?>
  <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-check-circle"></i>
    <div>Anda sudah mengisi tracer study (terakhir diperbarui <?= e(date_id($existing['updated_at'])) ?>). Anda bisa memperbaruinya kapan saja.</div>
  </div>
<?php else: ?>
  <p class="text-secondary">Isian ini membantu kampus mengetahui perjalanan karier alumni dan memperbaiki kurikulum. Mengisi hanya membutuhkan sekitar 3 menit.</p>
<?php endif; ?>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger" role="alert"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" novalidate>
  <?= csrf_field() ?>
  <div class="panel"><div class="panel-body">

    <section class="form-section">
      <h2>Data dasar</h2>
      <div class="row g-3">
        <div class="col-md-4">
          <label for="graduation_year" class="form-label">Tahun lulus</label>
          <input type="number" class="form-control" id="graduation_year" name="graduation_year" value="<?= $fv('graduation_year') ?>" min="1990" max="<?= (int) date('Y') + 1 ?>" required>
        </div>
        <div class="col-md-8">
          <label for="employment_status" class="form-label">Status saat ini</label>
          <select class="form-select" id="employment_status" name="employment_status" required>
            <option value="">Pilih status</option>
            <?php foreach (TRACER_STATUS_LABELS as $k => $lbl): ?>
              <option value="<?= e($k) ?>" <?= ($t['employment_status'] ?? '') === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </section>

    <section class="form-section" id="workSection">
      <h2>Pekerjaan</h2>
      <div class="row g-3">
        <div class="col-md-6">
          <label for="company_name" class="form-label">Nama perusahaan</label>
          <input type="text" class="form-control" id="company_name" name="company_name" value="<?= $fv('company_name') ?>" maxlength="150">
        </div>
        <div class="col-md-6">
          <label for="position" class="form-label">Jabatan</label>
          <input type="text" class="form-control" id="position" name="position" value="<?= $fv('position') ?>" maxlength="100">
        </div>
        <div class="col-md-6">
          <label for="job_field" class="form-label">Bidang pekerjaan</label>
          <input type="text" class="form-control" id="job_field" name="job_field" value="<?= $fv('job_field') ?>" maxlength="100" list="fieldList" placeholder="Contoh: Teknologi Informasi">
          <datalist id="fieldList">
            <?php foreach (['Teknologi Informasi', 'Keuangan', 'Pendidikan', 'Manufaktur', 'Kesehatan', 'Pemasaran', 'Desain Kreatif', 'Pemerintahan'] as $f): ?>
              <option value="<?= e($f) ?>"></option>
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="col-md-6">
          <label for="waiting_time" class="form-label">Lama mendapatkan pekerjaan pertama</label>
          <select class="form-select" id="waiting_time" name="waiting_time">
            <option value="">Pilih</option>
            <?php foreach (TRACER_WAITING as $k => $lbl): ?>
              <option value="<?= e($k) ?>" <?= ($t['waiting_time'] ?? '') === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label for="salary_range" class="form-label">Rentang penghasilan per bulan</label>
          <select class="form-select" id="salary_range" name="salary_range">
            <option value="">Pilih</option>
            <?php foreach (TRACER_SALARY as $k => $lbl): ?>
              <option value="<?= e($k) ?>" <?= ($t['salary_range'] ?? '') === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label for="job_relevance" class="form-label">Kesesuaian pekerjaan dengan jurusan</label>
          <select class="form-select" id="job_relevance" name="job_relevance">
            <option value="">Pilih</option>
            <?php foreach (TRACER_RELEVANCE as $k => $lbl): ?>
              <option value="<?= e($k) ?>" <?= ($t['job_relevance'] ?? '') === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </section>

    <section class="form-section">
      <h2>Masukan untuk kampus</h2>
      <label for="feedback" class="form-label visually-hidden">Masukan untuk kampus</label>
      <textarea class="form-control" id="feedback" name="feedback" rows="4" maxlength="2000" placeholder="Apa yang perlu ditingkatkan dari kurikulum, fasilitas, atau layanan career center?"><?= $fv('feedback') ?></textarea>
    </section>

    <div class="form-check mt-3">
      <input class="form-check-input" type="checkbox" id="sync_profile" name="sync_profile" value="1" <?= $syncChecked ? 'checked' : '' ?>>
      <label class="form-check-label" for="sync_profile">Perbarui status pekerjaan, perusahaan, dan jabatan di profil saya sesuai isian ini</label>
    </div>

    <div class="d-flex justify-content-end mt-4">
      <button type="submit" class="btn btn-primary px-4"><?= $existing ? 'Perbarui tracer study' : 'Kirim tracer study' ?></button>
    </div>
  </div></div>
</form>

<?php include __DIR__ . '/../components/dash_end.php'; ?>

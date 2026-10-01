<?php
/**
 * Form lowongan (dipakai create-job.php dan edit-job.php).
 * Variabel: $job (nilai form), $categories, $errors, $submitLabel.
 */
$fv = fn(string $k) => e($job[$k] ?? '');
?>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger" role="alert"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" novalidate>
  <?= csrf_field() ?>
  <div class="panel"><div class="panel-body">
    <section class="form-section">
      <h2>Informasi pekerjaan</h2>
      <div class="row g-3">
        <div class="col-12">
          <label for="title" class="form-label">Judul pekerjaan</label>
          <input type="text" class="form-control" id="title" name="title" value="<?= $fv('title') ?>" maxlength="150" required>
        </div>
        <div class="col-md-6">
          <label for="category_id" class="form-label">Kategori</label>
          <select class="form-select" id="category_id" name="category_id" required>
            <option value="">Pilih kategori</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int) $cat['id'] ?>" <?= (string) ($job['category_id'] ?? '') === (string) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label for="job_type" class="form-label">Tipe pekerjaan</label>
          <select class="form-select" id="job_type" name="job_type" required>
            <?php foreach (JOB_TYPES as $t): ?>
              <option value="<?= e($t) ?>" <?= ($job['job_type'] ?? 'full-time') === $t ? 'selected' : '' ?>><?= e(job_type_label($t)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label for="location" class="form-label">Lokasi</label>
          <input type="text" class="form-control" id="location" name="location" value="<?= $fv('location') ?>" maxlength="100" placeholder="Contoh: Jakarta" required>
        </div>
        <div class="col-md-6">
          <label for="salary" class="form-label">Gaji <span class="text-secondary fw-normal">(opsional)</span></label>
          <input type="text" class="form-control" id="salary" name="salary" value="<?= $fv('salary') ?>" maxlength="100" placeholder="Contoh: Rp 5.000.000 - 7.000.000">
        </div>
      </div>
    </section>

    <section class="form-section">
      <h2>Detail</h2>
      <div class="row g-3">
        <div class="col-12">
          <label for="description" class="form-label">Deskripsi pekerjaan</label>
          <textarea class="form-control" id="description" name="description" rows="6" maxlength="5000" required><?= $fv('description') ?></textarea>
        </div>
        <div class="col-12">
          <label for="requirements" class="form-label">Persyaratan</label>
          <textarea class="form-control" id="requirements" name="requirements" rows="5" maxlength="3000" aria-describedby="reqHelp" required><?= $fv('requirements') ?></textarea>
          <div id="reqHelp" class="form-text">Tulis satu persyaratan per baris.</div>
        </div>
      </div>
    </section>

    <section class="form-section">
      <h2>Pengaturan</h2>
      <div class="row g-3">
        <div class="col-md-6">
          <label for="deadline" class="form-label">Batas lamaran</label>
          <input type="date" class="form-control" id="deadline" name="deadline" value="<?= $fv('deadline') ?>" required>
        </div>
        <div class="col-md-6">
          <label for="status" class="form-label">Status</label>
          <select class="form-select" id="status" name="status">
            <option value="open" <?= ($job['status'] ?? 'open') === 'open' ? 'selected' : '' ?>>Dibuka</option>
            <option value="closed" <?= ($job['status'] ?? '') === 'closed' ? 'selected' : '' ?>>Ditutup</option>
          </select>
        </div>
      </div>
    </section>

    <div class="d-flex justify-content-end gap-2 mt-4">
      <a href="<?= e(url('company/jobs.php')) ?>" class="btn btn-outline-secondary">Batal</a>
      <button type="submit" class="btn btn-primary px-4"><?= e($submitLabel) ?></button>
    </div>
  </div></div>
</form>

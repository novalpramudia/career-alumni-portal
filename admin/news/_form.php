<?php
/** Form berita (dipakai create.php dan edit.php). Variabel: $news, $errors, $submitLabel. */
$fv = fn(string $k) => e($news[$k] ?? '');
$image = logo_url($news['image'] ?? null); // pakai fungsi yang sama, hanya baca file dari folder berbeda
$imageUrl = ($news['image'] ?? null) && is_file(UPLOAD_PATH . '/news/' . basename($news['image'])) ? url('uploads/news/' . rawurlencode($news['image'])) : null;
?>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger" role="alert"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="panel"><div class="panel-body">
        <div class="mb-3">
          <label for="title" class="form-label">Judul</label>
          <input type="text" class="form-control" id="title" name="title" value="<?= $fv('title') ?>" maxlength="200" required>
        </div>
        <div class="mb-0">
          <label for="content" class="form-label">Isi berita</label>
          <textarea class="form-control" id="content" name="content" rows="14" maxlength="10000"><?= $fv('content') ?></textarea>
        </div>
      </div></div>
    </div>
    <div class="col-lg-4">
      <div class="panel"><div class="panel-body">
        <div class="mb-3">
          <label for="status" class="form-label">Status</label>
          <select class="form-select" id="status" name="status">
            <option value="published" <?= ($news['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Publikasikan</option>
            <option value="draft" <?= ($news['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Simpan sebagai draft</option>
          </select>
        </div>
        <div class="mb-2">
          <label for="image" class="form-label">Gambar sampul</label>
          <?php if ($imageUrl): ?><img src="<?= e($imageUrl) ?>" class="w-100 rounded mb-2" alt="Sampul berita saat ini"><?php endif; ?>
          <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp">
          <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB. Opsional.</div>
        </div>
        <div class="d-grid mt-3"><button type="submit" class="btn btn-primary"><?= e($submitLabel) ?></button></div>
      </div></div>
    </div>
  </div>
</form>

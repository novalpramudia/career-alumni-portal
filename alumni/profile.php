<?php
require_once __DIR__ . '/../config/auth.php';
require_role('alumni');

const PHOTO_MAX_BYTES = 2 * 1024 * 1024; // 2 MB
const PHOTO_TYPES     = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const EMPLOYMENT      = ['bekerja', 'wirausaha', 'studi_lanjut', 'mencari_kerja'];

$user   = current_user();
$alumni = db_one('SELECT a.*, u.email FROM alumni a JOIN users u ON u.id = a.user_id WHERE a.user_id = ?', [$user['id']]);
if (!$alumni) {
    http_response_code(404);
    exit('Data alumni tidak ditemukan. Hubungi admin.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';

    if (!csrf_verify()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';

    /* ---------- HAPUS AKUN (Delete) ---------- */
    } elseif ($action === 'delete_account') {
        $hash = db_scalar('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
        if (!password_verify($_POST['confirm_password'] ?? '', (string) $hash)) {
            $errors[] = 'Password salah. Akun tidak dihapus.';
        } else {
            $cvFiles = array_column(db_all('SELECT cv_file FROM job_applications WHERE alumni_id = ?', [$alumni['id']]), 'cv_file');
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$user['id']]); // alumni & lamaran ikut terhapus (CASCADE)

            delete_upload(UPLOAD_PATH . '/profile', $alumni['photo']);
            foreach ($cvFiles as $cv) {
                if ($cv !== 'dummy_cv.pdf') { // file contoh dipakai bersama oleh data dummy
                    delete_upload(UPLOAD_PATH . '/cv', $cv);
                }
            }
            logout_user();
            session_start();
            flash('success', 'Akun Anda telah dihapus.');
            redirect('index.php');
        }

    /* ---------- UBAH PROFIL (Update) ---------- */
    } else {
        $in = [];
        foreach (['full_name', 'nim', 'email', 'phone', 'graduation_year', 'study_program', 'faculty', 'address',
                  'employment_status', 'company_name', 'position', 'linkedin', 'bio'] as $f) {
            $in[$f] = trim($_POST[$f] ?? '');
        }

        if (mb_strlen($in['full_name']) < 3 || mb_strlen($in['full_name']) > 150) {
            $errors[] = 'Nama lengkap harus 3 sampai 150 karakter.';
        }
        if (!preg_match('/^[A-Za-z0-9]{5,30}$/', $in['nim'])) {
            $errors[] = 'NIM harus 5 sampai 30 karakter huruf atau angka.';
        }
        if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($in['email']) > 150) {
            $errors[] = 'Format email tidak valid.';
        }
        if ($in['phone'] !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $in['phone'])) {
            $errors[] = 'Nomor HP hanya boleh berisi angka, spasi, tanda + dan -, sepanjang 8 sampai 20 karakter.';
        }
        if ($in['graduation_year'] !== '' &&
            (!ctype_digit($in['graduation_year']) || (int) $in['graduation_year'] < 1990 || (int) $in['graduation_year'] > (int) date('Y') + 1)) {
            $errors[] = 'Tahun lulus tidak valid.';
        }
        if (mb_strlen($in['study_program']) > 100 || mb_strlen($in['faculty']) > 100) {
            $errors[] = 'Program studi dan fakultas maksimal 100 karakter.';
        }
        if (mb_strlen($in['address']) > 500) {
            $errors[] = 'Alamat maksimal 500 karakter.';
        }
        if ($in['employment_status'] !== '' && !in_array($in['employment_status'], EMPLOYMENT, true)) {
            $errors[] = 'Status pekerjaan tidak valid.';
        }
        if (mb_strlen($in['company_name']) > 150 || mb_strlen($in['position']) > 100) {
            $errors[] = 'Nama perusahaan maksimal 150 karakter dan jabatan maksimal 100 karakter.';
        }
        if ($in['linkedin'] !== '') {
            $host = strtolower((string) parse_url($in['linkedin'], PHP_URL_HOST));
            if (!filter_var($in['linkedin'], FILTER_VALIDATE_URL) || mb_strlen($in['linkedin']) > 255
                || !preg_match('#^(https?)://#i', $in['linkedin']) || !preg_match('/(^|\.)linkedin\.com$/', $host)) {
                $errors[] = 'Link LinkedIn harus berupa URL linkedin.com yang valid, misalnya https://linkedin.com/in/nama.';
            }
        }
        if (mb_strlen($in['bio']) > 1000) {
            $errors[] = 'Bio maksimal 1000 karakter.';
        }

        // Email dan NIM harus unik (selain milik sendiri)
        if (!$errors) {
            if (db_scalar('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?', [$in['email'], $user['id']])) {
                $errors[] = 'Email sudah dipakai akun lain.';
            }
            if (db_scalar('SELECT COUNT(*) FROM alumni WHERE nim = ? AND id <> ?', [$in['nim'], $alumni['id']])) {
                $errors[] = 'NIM sudah terdaftar pada alumni lain.';
            }
        }

        // Upload foto
        $newPhoto = null;
        if (!$errors) {
            $up = handle_upload($_FILES['photo'] ?? [], UPLOAD_PATH . '/profile', PHOTO_TYPES, PHOTO_MAX_BYTES);
            if (!$up['ok']) {
                $errors[] = $up['error'];
            } else {
                $newPhoto = $up['filename'];
            }
        }

        if (!$errors) {
            $removePhoto = isset($_POST['remove_photo']) && $newPhoto === null;
            $photoValue  = $newPhoto ?? ($removePhoto ? null : $alumni['photo']);

            // Perubahan nama/NIM pada data yang sudah diverifikasi perlu diverifikasi ulang
            $verification = $alumni['verification_status'];
            if ($verification !== 'pending' && ($in['nim'] !== $alumni['nim'] || $in['full_name'] !== $alumni['full_name'])) {
                $verification = 'pending';
            }

            $pdo = db();
            try {
                $pdo->beginTransaction();
                $pdo->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$in['email'], $user['id']]);
                $pdo->prepare('UPDATE alumni SET full_name = ?, nim = ?, phone = ?, graduation_year = ?, study_program = ?, faculty = ?,
                                address = ?, photo = ?, employment_status = ?, company_name = ?, position = ?, linkedin = ?, bio = ?,
                                verification_status = ? WHERE id = ?')
                    ->execute([
                        $in['full_name'], $in['nim'], nullable($in['phone']),
                        $in['graduation_year'] === '' ? null : (int) $in['graduation_year'],
                        nullable($in['study_program']), nullable($in['faculty']), nullable($in['address']),
                        $photoValue, nullable($in['employment_status']), nullable($in['company_name']),
                        nullable($in['position']), nullable($in['linkedin']), nullable($in['bio']),
                        $verification, $alumni['id'],
                    ]);
                $pdo->commit();

                if (($newPhoto !== null || $removePhoto) && $alumni['photo']) {
                    delete_upload(UPLOAD_PATH . '/profile', $alumni['photo']); // hapus foto lama
                }
                $_SESSION['user']['name']  = $in['full_name'];
                $_SESSION['user']['email'] = $in['email'];

                flash('success', $verification !== $alumni['verification_status']
                    ? 'Profil disimpan. Perubahan nama atau NIM perlu diverifikasi ulang oleh admin.'
                    : 'Profil berhasil disimpan.');
                redirect('alumni/profile.php');
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                delete_upload(UPLOAD_PATH . '/profile', $newPhoto); // batalkan file baru
                if ($ex->getCode() === '23000') {
                    $errors[] = 'Email atau NIM sudah terdaftar.';
                } else {
                    error_log('Profile update error: ' . $ex->getMessage());
                    $errors[] = 'Profil gagal disimpan. Coba lagi beberapa saat.';
                }
            }
        }
    }
}

/* Nilai form: hasil POST (jika validasi gagal) atau data dari database */
$v = fn(string $k) => e($_POST[$k] ?? ($alumni[$k] ?? ''));
$empValue = $_POST['employment_status'] ?? ($alumni['employment_status'] ?? '');
$comp  = alumni_completion($alumni);
$photo = photo_url($alumni['photo']);

$pageTitle    = 'Profil saya';
$activeMenu   = 'alumni/profile.php';
$extraScripts = ['assets/js/profile.js'];
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
?>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger" role="alert"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="update">
  <div class="row g-3">

    <!-- Ringkasan -->
    <div class="col-lg-4">
      <div class="panel">
        <div class="panel-body text-center">
          <?php if ($photo): ?>
            <img id="photoPreview" class="profile-photo" src="<?= e($photo) ?>" alt="Foto profil">
          <?php else: ?>
            <div id="photoPreview" class="profile-photo" aria-hidden="true"><?= e(initials($alumni['full_name'])) ?></div>
          <?php endif; ?>

          <h2 class="h5 mt-3 mb-1"><?= e($alumni['full_name']) ?></h2>
          <div class="mb-3"><?= status_badge($alumni['verification_status']) ?></div>

          <div class="text-start">
            <label for="photo" class="form-label">Foto profil</label>
            <input type="file" class="form-control" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" aria-describedby="photoHelp">
            <div id="photoHelp" class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
            <div id="photoError" class="text-danger small" role="alert"></div>
            <?php if ($photo): ?>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="remove_photo" id="remove_photo" value="1">
                <label class="form-check-label small" for="remove_photo">Hapus foto saat ini</label>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="panel mt-3">
        <div class="panel-head"><h2>Kelengkapan profil</h2><strong><?= e($comp['percent']) ?>%</strong></div>
        <div class="panel-body">
          <div class="progress mb-2" role="progressbar" aria-label="Kelengkapan profil" aria-valuenow="<?= e($comp['percent']) ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: <?= e($comp['percent']) ?>%"></div>
          </div>
          <p class="small text-secondary mb-0">
            <?= $comp['missing'] ? 'Belum diisi: ' . e(implode(', ', $comp['missing'])) . '.' : 'Semua data penting sudah terisi.' ?>
          </p>
        </div>
      </div>
    </div>

    <!-- Form -->
    <div class="col-lg-8">
      <div class="panel">
        <div class="panel-body">

          <section class="form-section">
            <h2>Data pribadi</h2>
            <div class="row g-3">
              <div class="col-md-8">
                <label for="full_name" class="form-label">Nama lengkap</label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?= $v('full_name') ?>" maxlength="150" required>
              </div>
              <div class="col-md-4">
                <label for="nim" class="form-label">NIM</label>
                <input type="text" class="form-control" id="nim" name="nim" value="<?= $v('nim') ?>" maxlength="30" required>
              </div>
              <div class="col-md-7">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= $v('email') ?>" maxlength="150" required>
              </div>
              <div class="col-md-5">
                <label for="phone" class="form-label">Nomor HP</label>
                <input type="tel" class="form-control" id="phone" name="phone" value="<?= $v('phone') ?>" maxlength="20" placeholder="081234567890">
              </div>
              <div class="col-12">
                <label for="address" class="form-label">Alamat</label>
                <textarea class="form-control" id="address" name="address" rows="2" maxlength="500"><?= $v('address') ?></textarea>
              </div>
            </div>
          </section>

          <section class="form-section">
            <h2>Akademik</h2>
            <div class="row g-3">
              <div class="col-md-4">
                <label for="graduation_year" class="form-label">Tahun lulus</label>
                <input type="number" class="form-control" id="graduation_year" name="graduation_year" value="<?= $v('graduation_year') ?>" min="1990" max="<?= (int) date('Y') + 1 ?>">
              </div>
              <div class="col-md-8">
                <label for="study_program" class="form-label">Program studi</label>
                <input type="text" class="form-control" id="study_program" name="study_program" value="<?= $v('study_program') ?>" maxlength="100">
              </div>
              <div class="col-12">
                <label for="faculty" class="form-label">Fakultas</label>
                <input type="text" class="form-control" id="faculty" name="faculty" value="<?= $v('faculty') ?>" maxlength="100">
              </div>
            </div>
          </section>

          <section class="form-section">
            <h2>Pekerjaan</h2>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="employment_status" class="form-label">Status pekerjaan</label>
                <select class="form-select" id="employment_status" name="employment_status">
                  <option value="">Pilih status</option>
                  <?php foreach (EMPLOYMENT as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= $empValue === $opt ? 'selected' : '' ?>><?= e(employment_label($opt)) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label for="linkedin" class="form-label">LinkedIn</label>
                <input type="url" class="form-control" id="linkedin" name="linkedin" value="<?= $v('linkedin') ?>" maxlength="255" placeholder="https://linkedin.com/in/nama">
              </div>
              <div class="col-md-6">
                <label for="company_name" class="form-label">Nama perusahaan</label>
                <input type="text" class="form-control" id="company_name" name="company_name" value="<?= $v('company_name') ?>" maxlength="150">
              </div>
              <div class="col-md-6">
                <label for="position" class="form-label">Jabatan</label>
                <input type="text" class="form-control" id="position" name="position" value="<?= $v('position') ?>" maxlength="100">
              </div>
            </div>
          </section>

          <section class="form-section">
            <h2>Tentang saya</h2>
            <label for="bio" class="form-label visually-hidden">Bio</label>
            <textarea class="form-control" id="bio" name="bio" rows="4" maxlength="1000" placeholder="Ceritakan singkat latar belakang dan minat karier Anda."><?= $v('bio') ?></textarea>
          </section>

          <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">Hapus akun</button>
            <button type="submit" class="btn btn-primary px-4">Simpan perubahan</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<!-- Modal hapus akun -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete_account">
      <div class="modal-header">
        <h2 class="modal-title h5" id="deleteModalLabel">Hapus akun</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p>Profil, lamaran, dan isian tracer study Anda akan dihapus permanen dan tidak bisa dikembalikan.</p>
        <label for="confirm_password" class="form-label">Masukkan password untuk konfirmasi</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="current-password" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-danger">Hapus akun saya</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../components/dash_end.php'; ?>

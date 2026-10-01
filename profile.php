<?php
require_once __DIR__ . '/../config/auth.php';
require_role('company');

const LOGO_MAX_BYTES = 2 * 1024 * 1024; // 2 MB
const LOGO_TYPES     = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const INDUSTRIES     = ['Teknologi Informasi', 'Keuangan', 'Media & Kreatif', 'Pendidikan', 'Manufaktur', 'Perdagangan & Retail',
                        'Kesehatan', 'Konstruksi', 'Transportasi & Logistik', 'Energi', 'Pariwisata & Perhotelan', 'Lainnya'];

$user    = current_user();
$company = db_one('SELECT * FROM companies WHERE user_id = ?', [$user['id']]);
if (!$company) {
    http_response_code(404);
    exit('Data perusahaan tidak ditemukan. Hubungi admin.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';

    if (!csrf_verify()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';

    /* ---------- HAPUS AKUN PERUSAHAAN ---------- */
    } elseif ($action === 'delete_account') {
        $hash = db_scalar('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
        if (!password_verify($_POST['confirm_password'] ?? '', (string) $hash)) {
            $errors[] = 'Password salah. Akun tidak dihapus.';
        } else {
            // Kumpulkan file CV pelamar sebelum datanya terhapus
            $cvFiles = array_column(db_all(
                'SELECT ap.cv_file FROM job_applications ap JOIN jobs j ON j.id = ap.job_id WHERE j.company_id = ?',
                [$company['id']]
            ), 'cv_file');

            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$user['id']]); // perusahaan, lowongan, lamaran ikut terhapus (CASCADE)

            delete_upload(UPLOAD_PATH . '/company', $company['logo']);
            foreach ($cvFiles as $cv) {
                if ($cv !== 'dummy_cv.pdf') {
                    delete_upload(UPLOAD_PATH . '/cv', $cv);
                }
            }
            logout_user();
            session_start();
            flash('success', 'Akun perusahaan telah dihapus.');
            redirect('index.php');
        }

    /* ---------- UBAH PROFIL ---------- */
    } else {
        $in = [];
        foreach (['name', 'email', 'phone', 'address', 'website', 'industry', 'description'] as $f) {
            $in[$f] = trim($_POST[$f] ?? '');
        }

        if (mb_strlen($in['name']) < 3 || mb_strlen($in['name']) > 150) {
            $errors[] = 'Nama perusahaan harus 3 sampai 150 karakter.';
        }
        if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($in['email']) > 150) {
            $errors[] = 'Format email perusahaan tidak valid.';
        }
        if ($in['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $in['phone'])) {
            $errors[] = 'Nomor telepon hanya boleh berisi angka, spasi, tanda + - ( ), sepanjang 7 sampai 20 karakter.';
        }
        if (mb_strlen($in['address']) > 500) {
            $errors[] = 'Alamat maksimal 500 karakter.';
        }
        if ($in['website'] !== '' &&
            (!filter_var($in['website'], FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $in['website']) || mb_strlen($in['website']) > 255)) {
            $errors[] = 'Website harus berupa URL yang diawali http:// atau https://.';
        }
        if ($in['industry'] !== '' && (mb_strlen($in['industry']) > 100)) {
            $errors[] = 'Industri maksimal 100 karakter.';
        }
        if (mb_strlen($in['description']) > 2000) {
            $errors[] = 'Deskripsi maksimal 2000 karakter.';
        }

        $newLogo = null;
        if (!$errors) {
            $up = handle_upload($_FILES['logo'] ?? [], UPLOAD_PATH . '/company', LOGO_TYPES, LOGO_MAX_BYTES);
            if (!$up['ok']) {
                $errors[] = $up['error'];
            } else {
                $newLogo = $up['filename'];
            }
        }

        if (!$errors) {
            $removeLogo = isset($_POST['remove_logo']) && $newLogo === null;
            $logoValue  = $newLogo ?? ($removeLogo ? null : $company['logo']);

            try {
                db()->prepare('UPDATE companies SET name = ?, email = ?, phone = ?, address = ?, website = ?, industry = ?,
                                description = ?, logo = ? WHERE id = ?')
                    ->execute([
                        $in['name'], $in['email'], nullable($in['phone']), nullable($in['address']), nullable($in['website']),
                        nullable($in['industry']), nullable($in['description']), $logoValue, $company['id'],
                    ]);

                if (($newLogo !== null || $removeLogo) && $company['logo']) {
                    delete_upload(UPLOAD_PATH . '/company', $company['logo']); // hapus logo lama
                }
                $_SESSION['user']['name'] = $in['name'];

                flash('success', 'Profil perusahaan berhasil disimpan.');
                redirect('company/profile.php');
            } catch (PDOException $ex) {
                delete_upload(UPLOAD_PATH . '/company', $newLogo); // batalkan file baru
                error_log('Company profile update error: ' . $ex->getMessage());
                $errors[] = 'Profil gagal disimpan. Coba lagi beberapa saat.';
            }
        }
    }
}

$v    = fn(string $k) => e($_POST[$k] ?? ($company[$k] ?? ''));
$industryValue = $_POST['industry'] ?? ($company['industry'] ?? '');
$comp = company_completion($company);
$logo = logo_url($company['logo']);

$pageTitle    = 'Profil perusahaan';
$activeMenu   = 'company/profile.php';
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

    <div class="col-lg-4">
      <div class="panel">
        <div class="panel-body text-center">
          <?php if ($logo): ?>
            <img id="photoPreview" class="profile-photo logo-square" src="<?= e($logo) ?>" alt="Logo perusahaan">
          <?php else: ?>
            <div id="photoPreview" class="profile-photo logo-square" aria-hidden="true"><?= e(initials($company['name'])) ?></div>
          <?php endif; ?>

          <h2 class="h5 mt-3 mb-1"><?= e($company['name']) ?></h2>
          <p class="small text-secondary mb-3">Login: <?= e($user['email']) ?></p>

          <div class="text-start">
            <label for="photo" class="form-label">Logo</label>
            <input type="file" class="form-control" id="photo" name="logo" accept="image/jpeg,image/png,image/webp" aria-describedby="logoHelp">
            <div id="logoHelp" class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
            <div id="photoError" class="text-danger small" role="alert"></div>
            <?php if ($logo): ?>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="remove_logo" id="remove_logo" value="1">
                <label class="form-check-label small" for="remove_logo">Hapus logo saat ini</label>
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
          <p class="small text-secondary mb-2">
            <?= $comp['missing'] ? 'Belum diisi: ' . e(implode(', ', $comp['missing'])) . '.' : 'Semua data penting sudah terisi.' ?>
          </p>
          <a class="small" href="<?= e(url('company/profile-view.php?id=' . (int) $company['id'])) ?>">Lihat tampilan publik</a>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="panel">
        <div class="panel-body">
          <section class="form-section">
            <h2>Informasi perusahaan</h2>
            <div class="row g-3">
              <div class="col-12">
                <label for="name" class="form-label">Nama perusahaan</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= $v('name') ?>" maxlength="150" required>
              </div>
              <div class="col-md-6">
                <label for="industry" class="form-label">Industri</label>
                <select class="form-select" id="industry" name="industry">
                  <option value="">Pilih industri</option>
                  <?php
                  $opts = INDUSTRIES;
                  if ($industryValue !== '' && !in_array($industryValue, $opts, true)) {
                      $opts[] = $industryValue; // pertahankan nilai lama yang tidak ada di daftar
                  }
                  foreach ($opts as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= $industryValue === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label for="website" class="form-label">Website</label>
                <input type="url" class="form-control" id="website" name="website" value="<?= $v('website') ?>" maxlength="255" placeholder="https://contoh.co.id">
              </div>
              <div class="col-12">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="description" name="description" rows="5" maxlength="2000" placeholder="Jelaskan bidang usaha dan budaya kerja perusahaan."><?= $v('description') ?></textarea>
              </div>
            </div>
          </section>

          <section class="form-section">
            <h2>Kontak</h2>
            <div class="row g-3">
              <div class="col-md-7">
                <label for="email" class="form-label">Email perusahaan</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= $v('email') ?>" maxlength="150" required>
              </div>
              <div class="col-md-5">
                <label for="phone" class="form-label">Nomor telepon</label>
                <input type="tel" class="form-control" id="phone" name="phone" value="<?= $v('phone') ?>" maxlength="20">
              </div>
              <div class="col-12">
                <label for="address" class="form-label">Alamat</label>
                <textarea class="form-control" id="address" name="address" rows="2" maxlength="500"><?= $v('address') ?></textarea>
              </div>
            </div>
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

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete_account">
      <div class="modal-header">
        <h2 class="modal-title h5" id="deleteModalLabel">Hapus akun perusahaan</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <p>Profil, seluruh lowongan, dan lamaran yang masuk akan dihapus permanen dan tidak bisa dikembalikan.</p>
        <label for="confirm_password" class="form-label">Masukkan password untuk konfirmasi</label>
        <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="current-password" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-danger">Hapus akun perusahaan</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../components/dash_end.php'; ?>

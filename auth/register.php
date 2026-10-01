<?php
require_once __DIR__ . '/../config/auth.php';
guest_only();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role     = $_POST['role'] ?? '';
    $name     = trim($_POST['name'] ?? '');
    $nim      = trim($_POST['nim'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if (!csrf_verify()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    }

    // Admin tidak bisa mendaftar lewat form ini
    if (!in_array($role, ['alumni', 'company'], true)) {
        $errors[] = 'Pilih jenis akun: alumni atau perusahaan.';
    }
    if (mb_strlen($name) < 3 || mb_strlen($name) > 150) {
        $errors[] = 'Nama harus 3 sampai 150 karakter.';
    }
    if ($role === 'alumni' && !preg_match('/^[A-Za-z0-9]{5,30}$/', $nim)) {
        $errors[] = 'NIM harus 5 sampai 30 karakter huruf atau angka.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errors[] = 'Format email tidak valid.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Password minimal 8 karakter dan mengandung huruf serta angka.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)');
            $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), $role]);
            $userId = (int) $pdo->lastInsertId();

            if ($role === 'alumni') {
                $stmt = $pdo->prepare('INSERT INTO alumni (user_id, full_name, nim) VALUES (?, ?, ?)');
                $stmt->execute([$userId, $name, $nim]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO companies (user_id, name, email) VALUES (?, ?, ?)');
                $stmt->execute([$userId, $name, $email]);
            }

            $pdo->commit();
            flash('success', 'Pendaftaran berhasil. Silakan login.');
            redirect('auth/login.php');
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($ex->getCode() === '23000') { // duplicate key
                $errors[] = 'Email atau NIM sudah terdaftar.';
            } else {
                error_log('Register error: ' . $ex->getMessage());
                $errors[] = 'Pendaftaran gagal. Coba lagi beberapa saat.';
            }
        }
    }
}

$selectedRole = $_POST['role'] ?? 'alumni';
$pageTitle = 'Daftar';
$extraScripts = ['assets/js/auth.js'];
include __DIR__ . '/../components/head.php';
?>
<div class="auth-shell">
  <aside class="auth-aside">
    <a class="auth-brand" href="<?= e(url('index.php')) ?>"><span class="auth-brand-mark">C</span> <?= e(APP_NAME) ?></a>
    <div>
      <h1>Bergabung dengan jaringan alumni.</h1>
      <p class="mt-3">Alumni mencari kerja dan berbagi kabar. Perusahaan menemukan talenta dari kampus kita.</p>
    </div>
    <ul>
      <li>Alumni: data Anda diverifikasi admin kampus</li>
      <li>Perusahaan: pasang lowongan setelah mendaftar</li>
    </ul>
  </aside>

  <main class="auth-main">
    <div class="auth-card">
      <h2 class="h3 mb-1">Buat akun baru</h2>
      <p class="text-secondary mb-4">Sudah punya akun? <a href="<?= e(url('auth/login.php')) ?>">Masuk</a></p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger" role="alert"><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="post" novalidate>
        <?= csrf_field() ?>

        <fieldset class="mb-3">
          <legend class="form-label">Daftar sebagai</legend>
          <div class="role-choice">
            <div>
              <input type="radio" name="role" id="role-alumni" value="alumni" <?= $selectedRole === 'alumni' ? 'checked' : '' ?>>
              <label for="role-alumni" class="d-block">Alumni</label>
            </div>
            <div>
              <input type="radio" name="role" id="role-company" value="company" <?= $selectedRole === 'company' ? 'checked' : '' ?>>
              <label for="role-company" class="d-block">Perusahaan</label>
            </div>
          </div>
        </fieldset>

        <div class="mb-3">
          <label for="name" class="form-label" id="nameLabel">Nama lengkap</label>
          <input type="text" class="form-control" id="name" name="name" value="<?= old('name') ?>" maxlength="150" required>
        </div>
        <div class="mb-3" data-role-only="alumni">
          <label for="nim" class="form-label">NIM</label>
          <input type="text" class="form-control" id="nim" name="nim" value="<?= old('nim') ?>" maxlength="30">
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" autocomplete="username" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" aria-describedby="pwHelp" required>
          <div id="pwHelp" class="form-text">Minimal 8 karakter, kombinasi huruf dan angka.</div>
        </div>
        <div class="mb-4">
          <label for="password_confirm" class="form-label">Ulangi password</label>
          <input type="password" class="form-control" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">Buat akun</button>
      </form>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../components/scripts.php'; ?>

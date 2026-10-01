<?php
require_once __DIR__ . '/../config/auth.php';
guest_only();

const MAX_ATTEMPTS = 5;      // percobaan gagal berturut-turut
const LOCK_SECONDS = 60;     // durasi tunggu setelah terlalu banyak gagal
// Hash valid untuk password acak; dipakai agar waktu respons sama saat email tidak ditemukan.
const DUMMY_HASH = '$2b$12$LZAzYcxNm3xjr.cI.n1eSeV.ULNjwo/cvqoXf5bs4i2BeliFPh2Ji';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_verify()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } elseif (($_SESSION['login_lock_until'] ?? 0) > time()) {
        $errors[] = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . ($_SESSION['login_lock_until'] - time()) . ' detik.';
    } elseif ($email === '' || $password === '') {
        $errors[] = 'Email dan password wajib diisi.';
    } else {
        $stmt = db()->prepare('SELECT id, email, password_hash, role, is_active FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Tetap jalankan password_verify meski user tidak ditemukan (samarkan waktu respons)
        $hash = $user['password_hash'] ?? DUMMY_HASH;
        $valid = password_verify($password, $hash) && $user;

        if (!$valid) {
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            if ($_SESSION['login_attempts'] >= MAX_ATTEMPTS) {
                $_SESSION['login_lock_until'] = time() + LOCK_SECONDS;
                $_SESSION['login_attempts']   = 0;
            }
            $errors[] = 'Email atau password salah.';
        } elseif (!(int) $user['is_active']) {
            $errors[] = 'Akun Anda dinonaktifkan. Hubungi admin career center.';
        } else {
            // Ambil nama tampilan sesuai role
            if ($user['role'] === 'alumni') {
                $q = db()->prepare('SELECT full_name FROM alumni WHERE user_id = ?');
            } elseif ($user['role'] === 'company') {
                $q = db()->prepare('SELECT name FROM companies WHERE user_id = ?');
            } else {
                $q = null;
            }
            $displayName = 'Administrator';
            if ($q) {
                $q->execute([$user['id']]);
                $displayName = $q->fetchColumn() ?: $user['email'];
            }

            unset($_SESSION['login_attempts'], $_SESSION['login_lock_until']);
            login_user($user, $displayName);
            flash('success', 'Selamat datang, ' . $displayName . '.');
            redirect(dashboard_path($user['role']));
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/../components/head.php';
?>
<div class="auth-shell">
  <aside class="auth-aside">
    <a class="auth-brand" href="<?= e(url('index.php')) ?>"><span class="auth-brand-mark">C</span> <?= e(APP_NAME) ?></a>
    <div>
      <h1>Dari kampus ke karier.</h1>
      <p class="mt-3">Cari lowongan, kirim lamaran, dan tetap terhubung dengan alumni dalam satu portal.</p>
    </div>
    <ul>
      <li>Lowongan dari perusahaan mitra kampus</li>
      <li>Pantau status lamaran Anda</li>
      <li>Isi tracer study untuk kemajuan kampus</li>
    </ul>
  </aside>

  <main class="auth-main">
    <div class="auth-card">
      <h2 class="h3 mb-1">Masuk ke akun</h2>
      <p class="text-secondary mb-4">Belum punya akun? <a href="<?= e(url('auth/register.php')) ?>">Daftar sekarang</a></p>

      <?= render_flashes() ?>
      <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger" role="alert"><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" autocomplete="username" required autofocus>
        </div>
        <div class="mb-4">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">Masuk</button>
      </form>
    </div>
  </main>
</div>
<?php include __DIR__ . '/../components/scripts.php'; ?>

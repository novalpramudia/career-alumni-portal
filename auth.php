<?php
/**
 * Autentikasi, otorisasi berbasis role, dan CSRF.
 * Sertakan file ini di paling atas setiap halaman: require_once __DIR__ . '/../config/auth.php';
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,   // cookie tidak bisa dibaca JavaScript
        'samesite' => 'Lax',  // mengurangi risiko CSRF
    ]);
    session_start();
}

/* ---------- Status login ---------- */

function is_logged_in(): bool
{
    return isset($_SESSION['user']['id']);
}

/** Data user aktif: id, email, role, name. NULL jika belum login. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function dashboard_path(string $role): string
{
    switch ($role) {
        case 'admin':   return 'admin/dashboard.php';
        case 'company': return 'company/dashboard.php';
        default:        return 'alumni/dashboard.php';
    }
}

/** Menyimpan user ke session setelah login berhasil. */
function login_user(array $user, string $displayName): void
{
    session_regenerate_id(true); // cegah session fixation
    $_SESSION['user'] = [
        'id'    => (int) $user['id'],
        'email' => $user['email'],
        'role'  => $user['role'],
        'name'  => $displayName,
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ---------- Middleware / helper role ---------- */

/** Halaman khusus tamu (login/register). User yang sudah login dialihkan ke dashboard. */
function guest_only(): void
{
    if (is_logged_in()) {
        redirect(dashboard_path($_SESSION['user']['role']));
    }
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Silakan login terlebih dahulu.');
        redirect('auth/login.php');
    }
}

/**
 * Membatasi halaman berdasarkan role.
 * Contoh: require_role('admin');  atau  require_role(['alumni', 'company']);
 */
function require_role($roles): void
{
    require_login();
    $roles = (array) $roles;
    $role  = $_SESSION['user']['role'];

    if (!in_array($role, $roles, true)) {
        flash('danger', 'Anda tidak memiliki akses ke halaman tersebut.');
        redirect(dashboard_path($role));
    }
}

/** ID baris pada tabel alumni untuk user yang sedang login (NULL jika bukan alumni). */
function current_alumni_id(): ?int
{
    if (!is_logged_in() || $_SESSION['user']['role'] !== 'alumni') {
        return null;
    }
    $stmt = db()->prepare('SELECT id FROM alumni WHERE user_id = ?');
    $stmt->execute([$_SESSION['user']['id']]);
    $id = $stmt->fetchColumn();
    return $id ? (int) $id : null;
}

/** ID baris pada tabel companies untuk user yang sedang login (NULL jika bukan perusahaan). */
function current_company_id(): ?int
{
    if (!is_logged_in() || $_SESSION['user']['role'] !== 'company') {
        return null;
    }
    $stmt = db()->prepare('SELECT id FROM companies WHERE user_id = ?');
    $stmt->execute([$_SESSION['user']['id']]);
    $id = $stmt->fetchColumn();
    return $id ? (int) $id : null;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Letakkan di dalam setiap <form method="post">. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent) && hash_equals($_SESSION['csrf_token'] ?? '', $sent);
}

/* ---------- Hak akses profil alumni ---------- */

/**
 * Siapa boleh melihat profil alumni tertentu?
 * - admin      : semua alumni
 * - alumni     : hanya profil dirinya sendiri
 * - perusahaan : hanya alumni yang melamar ke lowongan milik perusahaan tersebut
 */
function can_view_alumni(int $alumniId): bool
{
    if (!is_logged_in()) {
        return false;
    }
    switch ($_SESSION['user']['role']) {
        case 'admin':
            return true;
        case 'alumni':
            return current_alumni_id() === $alumniId;
        case 'company':
            $companyId = current_company_id();
            if (!$companyId) {
                return false;
            }
            return (int) db_scalar(
                'SELECT COUNT(*) FROM job_applications ap JOIN jobs j ON j.id = ap.job_id
                 WHERE ap.alumni_id = ? AND j.company_id = ?',
                [$alumniId, $companyId]
            ) > 0;
    }
    return false;
}

<?php
/**
 * Fungsi bantu umum: URL, escape output (XSS), redirect, flash message.
 */
date_default_timezone_set('Asia/Jakarta');
define('APP_NAME', 'Career & Alumni Portal');
// Nama folder project di htdocs. Ubah jika folder Anda berbeda.
define('BASE_URL', '/career-alumni-portal');

/** Escape output HTML (proteksi XSS). Pakai di setiap echo data dinamis. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Membuat URL absolut dari root project. */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Menyimpan pesan sekali tampil. $type: success | danger | warning | info */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Menampilkan dan menghapus semua flash message sebagai alert Bootstrap. */
function render_flashes(): string
{
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $html .= '<div class="alert alert-' . e($f['type']) . ' alert-dismissible fade show" role="alert">'
              .  e($f['message'])
              .  '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

/** Mengisi ulang nilai form setelah validasi gagal. */
function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

/* ---------- Format tampilan ---------- */

/** Contoh: 2026-11-30 => 30 November 2026 */
function date_id(?string $date): string
{
    $ts = $date ? strtotime($date) : false;
    if (!$ts) {
        return '-';
    }
    $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return date('j', $ts) . ' ' . $bulan[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

/** Potong teks menjadi ringkasan (tanpa tag HTML). */
function excerpt(?string $text, int $length = 110): string
{
    return mb_strimwidth(strip_tags((string) $text), 0, $length, '...', 'UTF-8');
}

/** Inisial untuk avatar (mengabaikan awalan PT/CV). */
function initials(string $name): string
{
    $name  = preg_replace('/^(PT|CV)\s+/i', '', trim($name));
    $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
    $out   = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out !== '' ? $out : '?';
}

function job_type_label(string $type): string
{
    $map = ['full-time' => 'Full time', 'part-time' => 'Part time', 'internship' => 'Magang', 'contract' => 'Kontrak'];
    return $map[$type] ?? $type;
}

function employment_label(?string $status): string
{
    $map = ['bekerja' => 'Bekerja', 'wirausaha' => 'Wirausaha', 'studi_lanjut' => 'Studi lanjut', 'mencari_kerja' => 'Mencari kerja'];
    return $map[$status] ?? '-';
}

/** Badge status untuk lamaran, verifikasi alumni, dan lowongan. */
function status_badge(string $status): string
{
    $labels = [
        'pending'  => 'Menunggu',  'reviewed' => 'Ditinjau',
        'accepted' => 'Diterima',  'rejected' => 'Ditolak',
        'verified' => 'Terverifikasi',
        'open'     => 'Dibuka',    'closed'   => 'Ditutup',
    ];
    return '<span class="badge status-' . e($status) . '">' . e($labels[$status] ?? $status) . '</span>';
}

/* ---------- Komponen HTML kecil yang dipakai ulang ---------- */

/** Kartu statistik dashboard. $tone: blue | gold | green | red | slate */
function stat_card(string $icon, string $label, $value, string $tone = 'blue'): string
{
    return '<div class="stat-card"><div class="stat-icon tone-' . e($tone) . '"><i class="bi bi-' . e($icon) . '"></i></div>'
         . '<div><div class="stat-value">' . e($value) . '</div><div class="stat-label">' . e($label) . '</div></div></div>';
}

/** Kartu lowongan. Data: id, title, company_name, location, job_type, salary, deadline, category_name. */
function job_card(array $job): string
{
    $href = url('alumni/job-detail.php?id=' . (int) $job['id']);
    $html  = '<article class="job-card">';
    $html .= '<div class="d-flex gap-3 align-items-start">';
    $html .= '<div class="avatar-box">' . e(initials($job['company_name'])) . '</div>';
    $html .= '<div class="min-w-0"><h3 class="h6 mb-1"><a class="stretched-link text-decoration-none" href="' . e($href) . '">' . e($job['title']) . '</a></h3>';
    $html .= '<div class="text-secondary small">' . e($job['company_name']) . '</div></div></div>';
    $html .= '<div class="job-meta">';
    $html .= '<span><i class="bi bi-geo-alt"></i> ' . e($job['location']) . '</span>';
    $html .= '<span><i class="bi bi-briefcase"></i> ' . e(job_type_label($job['job_type'])) . '</span>';
    $html .= '<span><i class="bi bi-tag"></i> ' . e($job['category_name']) . '</span></div>';
    $html .= '<div class="d-flex justify-content-between align-items-end mt-auto pt-3 small">';
    $html .= '<span class="fw-semibold">' . e($job['salary'] ?: 'Gaji dirahasiakan') . '</span>';
    $html .= '<span class="text-secondary">Tutup ' . e(date_id($job['deadline'])) . '</span></div>';
    $html .= '</article>';
    return $html;
}

/* ---------- Upload file (dipakai foto profil, CV, logo) ---------- */
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads');

/**
 * Validasi dan simpan file upload dengan aman.
 * - $allowed: peta MIME => ekstensi, contoh ['image/jpeg' => 'jpg']
 * - Tipe dicek dari ISI file (finfo), bukan dari nama/ekstensi yang dikirim user.
 * - Nama file diganti acak agar tidak bisa ditebak atau menimpa file lain.
 * Mengembalikan ['ok' => bool, 'filename' => ?string, 'error' => ?string].
 * Jika user tidak memilih file: ok = true dan filename = null.
 */
function handle_upload(array $file, string $dir, array $allowed, int $maxBytes): array
{
    $fail = fn(string $msg) => ['ok' => false, 'filename' => null, 'error' => $msg];
    $maxMb = round($maxBytes / 1048576, 1);

    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => true, 'filename' => null, 'error' => null];
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'filename' => null, 'error' => null];
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        return $fail("Ukuran file melebihi batas {$maxMb} MB.");
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return $fail('Upload gagal. Silakan coba lagi.');
    }
    if ($file['size'] > $maxBytes) {
        return $fail("Ukuran file melebihi batas {$maxMb} MB.");
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return $fail('Upload tidak valid.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        return $fail('Tipe file tidak diizinkan. Gunakan: ' . implode(', ', array_unique(array_values($allowed))) . '.');
    }
    if (strpos($mime, 'image/') === 0 && @getimagesize($file['tmp_name']) === false) {
        return $fail('File gambar rusak atau tidak valid.');
    }

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return $fail('Folder upload tidak dapat dibuat.');
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], rtrim($dir, '/\\') . '/' . $name)) {
        return $fail('File gagal disimpan di server.');
    }
    return ['ok' => true, 'filename' => $name, 'error' => null];
}

/** Menghapus file upload. basename() mencegah path traversal. */
function delete_upload(string $dir, ?string $filename): void
{
    if (!$filename) {
        return;
    }
    $path = rtrim($dir, '/\\') . '/' . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

/** URL foto profil, atau NULL jika belum ada / file hilang. */
function photo_url(?string $filename): ?string
{
    if ($filename && is_file(UPLOAD_PATH . '/profile/' . basename($filename))) {
        return url('uploads/profile/' . rawurlencode(basename($filename)));
    }
    return null;
}

/** String kosong menjadi NULL (untuk kolom opsional di database). */
function nullable(?string $value): ?string
{
    $value = trim((string) $value);
    return $value === '' ? null : $value;
}

/** Kelengkapan profil alumni: ['percent' => int, 'missing' => [label, ...]] */
function alumni_completion(array $alumni): array
{
    $fields = [
        'full_name' => 'Nama lengkap', 'nim' => 'NIM', 'phone' => 'Nomor HP', 'graduation_year' => 'Tahun lulus',
        'study_program' => 'Program studi', 'faculty' => 'Fakultas', 'address' => 'Alamat', 'photo' => 'Foto profil',
        'employment_status' => 'Status pekerjaan', 'linkedin' => 'LinkedIn', 'bio' => 'Bio',
    ];
    $missing = [];
    foreach ($fields as $col => $label) {
        $val = $alumni[$col] ?? null;
        if ($val === null || trim((string) $val) === '') {
            $missing[] = $label;
        }
    }
    return [
        'percent' => (int) round((count($fields) - count($missing)) / count($fields) * 100),
        'missing' => $missing,
    ];
}

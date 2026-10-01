<?php
/**
 * Logika lowongan yang dipakai bersama: pencarian (alumni + API), validasi form (perusahaan), pagination.
 */
const JOB_TYPES = ['full-time', 'part-time', 'internship', 'contract'];

/** Menyaring parameter filter dari $_GET menjadi array yang aman. */
function job_filters_from(array $src): array
{
    $type = trim((string) ($src['type'] ?? ''));
    return [
        'q'        => mb_substr(trim((string) ($src['q'] ?? '')), 0, 100),
        'location' => mb_substr(trim((string) ($src['location'] ?? '')), 0, 100),
        'category' => ctype_digit((string) ($src['category'] ?? '')) ? (int) $src['category'] : 0,
        'type'     => in_array($type, JOB_TYPES, true) ? $type : '',
    ];
}

/**
 * Mencari lowongan aktif (status open dan deadline belum lewat).
 * Mengembalikan ['items' => [...], 'total' => int, 'page' => int, 'pages' => int].
 */
function search_jobs(array $f, int $page = 1, int $perPage = 9): array
{
    $where  = ["j.status = 'open'", 'j.deadline >= CURDATE()'];
    $params = [];

    if ($f['q'] !== '') {
        $like = '%' . addcslashes($f['q'], '%_\\') . '%'; // % dan _ dari user diperlakukan sebagai teks biasa
        $where[] = '(j.title LIKE ? OR j.description LIKE ? OR c.name LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    if ($f['location'] !== '') {
        $where[] = 'j.location = ?';
        $params[] = $f['location'];
    }
    if ($f['category'] > 0) {
        $where[] = 'j.category_id = ?';
        $params[] = $f['category'];
    }
    if ($f['type'] !== '') {
        $where[] = 'j.job_type = ?';
        $params[] = $f['type'];
    }

    $whereSql = implode(' AND ', $where);
    $from = 'FROM jobs j JOIN companies c ON c.id = j.company_id JOIN job_categories k ON k.id = j.category_id';

    $total  = (int) db_scalar("SELECT COUNT(*) $from WHERE $whereSql", $params);
    $pages  = max(1, (int) ceil($total / $perPage));
    $page   = min(max(1, $page), $pages);
    $offset = ($page - 1) * $perPage;

    $items = db_all("SELECT j.id, j.title, j.location, j.job_type, j.salary, j.deadline,
                            c.name AS company_name, c.logo AS company_logo, k.name AS category_name
                     $from WHERE $whereSql
                     ORDER BY j.created_at DESC, j.id DESC
                     LIMIT $perPage OFFSET $offset", $params); // $perPage & $offset sudah int

    return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages];
}

/** Grid kartu lowongan (atau empty state). */
function render_job_grid(array $items): string
{
    if (!$items) {
        return '<div class="panel"><div class="empty-state"><i class="bi bi-search"></i>Tidak ada lowongan yang cocok. Coba ubah kata kunci atau filter.</div></div>';
    }
    $html = '<div class="row g-3">';
    foreach ($items as $job) {
        $html .= '<div class="col-md-6 col-xl-4">' . job_card($job) . '</div>';
    }
    return $html . '</div>';
}

/** Navigasi halaman. $params = filter aktif (tanpa 'page'). */
function render_pagination(int $page, int $pages, array $params, string $path): string
{
    if ($pages <= 1) {
        return '';
    }
    $params = array_filter($params, fn($v) => $v !== '' && $v !== 0 && $v !== null);
    $link = function (int $p) use ($params, $path) {
        return e(url($path) . '?' . http_build_query($params + ['page' => $p]));
    };
    $start = max(1, min($page - 2, $pages - 4));
    $end   = min($pages, $start + 4);

    $html = '<nav aria-label="Halaman lowongan" class="mt-4"><ul class="pagination justify-content-center mb-0">';
    $html .= '<li class="page-item' . ($page <= 1 ? ' disabled' : '') . '"><a class="page-link" href="' . $link(max(1, $page - 1)) . '">Sebelumnya</a></li>';
    for ($i = $start; $i <= $end; $i++) {
        $html .= '<li class="page-item' . ($i === $page ? ' active" aria-current="page' : '') . '"><a class="page-link" href="' . $link($i) . '">' . $i . '</a></li>';
    }
    $html .= '<li class="page-item' . ($page >= $pages ? ' disabled' : '') . '"><a class="page-link" href="' . $link(min($pages, $page + 1)) . '">Berikutnya</a></li>';
    return $html . '</ul></nav>';
}

/**
 * Validasi form lowongan (dipakai create-job.php dan edit-job.php).
 * Mengembalikan [data_bersih, daftar_error].
 */
function validate_job_input(array $src, array $categoryIds): array
{
    $d = [];
    foreach (['title', 'category_id', 'location', 'job_type', 'salary', 'deadline', 'description', 'requirements', 'status'] as $f) {
        $d[$f] = trim((string) ($src[$f] ?? ''));
    }
    $err = [];

    if (mb_strlen($d['title']) < 5 || mb_strlen($d['title']) > 150) {
        $err[] = 'Judul pekerjaan harus 5 sampai 150 karakter.';
    }
    if (!ctype_digit($d['category_id']) || !in_array((int) $d['category_id'], $categoryIds, true)) {
        $err[] = 'Pilih kategori pekerjaan.';
    }
    if (mb_strlen($d['location']) < 2 || mb_strlen($d['location']) > 100) {
        $err[] = 'Lokasi harus 2 sampai 100 karakter.';
    }
    if (!in_array($d['job_type'], JOB_TYPES, true)) {
        $err[] = 'Pilih tipe pekerjaan.';
    }
    if (mb_strlen($d['salary']) > 100) {
        $err[] = 'Gaji maksimal 100 karakter.';
    }
    if (!in_array($d['status'], ['open', 'closed'], true)) {
        $err[] = 'Status tidak valid.';
    }
    if (mb_strlen($d['description']) < 20 || mb_strlen($d['description']) > 5000) {
        $err[] = 'Deskripsi harus 20 sampai 5000 karakter.';
    }
    if (mb_strlen($d['requirements']) < 10 || mb_strlen($d['requirements']) > 3000) {
        $err[] = 'Persyaratan harus 10 sampai 3000 karakter.';
    }

    $dt = DateTime::createFromFormat('!Y-m-d', $d['deadline']);
    if (!$dt || $dt->format('Y-m-d') !== $d['deadline']) {
        $err[] = 'Deadline tidak valid.';
    } elseif ($d['status'] === 'open' && $dt < new DateTime('today')) {
        $err[] = 'Lowongan yang dibuka tidak boleh memiliki deadline sebelum hari ini.';
    }

    return [$d, $err];
}

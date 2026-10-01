<?php
/**
 * Tracer study: pilihan form, label tampilan, dan perhitungan statistik (dipakai form alumni, halaman admin, dan API).
 */
const TRACER_EMPLOYMENT = ['bekerja', 'wirausaha', 'studi_lanjut', 'mencari_kerja'];
const TRACER_WORKING    = ['bekerja', 'wirausaha']; // status yang mengisi bagian "pekerjaan"

const TRACER_WAITING = [
    'sebelum_lulus' => 'Sudah bekerja sebelum lulus', '0-3_bulan' => '0 - 3 bulan', '3-6_bulan' => '3 - 6 bulan',
    '6-12_bulan' => '6 - 12 bulan', 'lebih_12_bulan' => 'Lebih dari 12 bulan',
];
const TRACER_SALARY = [
    '<3jt' => 'Kurang dari Rp 3 juta', '3-5jt' => 'Rp 3 - 5 juta', '5-8jt' => 'Rp 5 - 8 juta',
    '8-12jt' => 'Rp 8 - 12 juta', '>12jt' => 'Lebih dari Rp 12 juta',
];
const TRACER_RELEVANCE = [
    'sangat_sesuai' => 'Sangat sesuai', 'sesuai' => 'Sesuai', 'kurang_sesuai' => 'Kurang sesuai', 'tidak_sesuai' => 'Tidak sesuai',
];
const TRACER_STATUS_LABELS = [
    'bekerja' => 'Bekerja', 'wirausaha' => 'Wirausaha', 'studi_lanjut' => 'Studi lanjut', 'mencari_kerja' => 'Mencari kerja',
];

/**
 * Statistik tracer study. $year = filter tahun lulus (NULL = semua).
 * Kolom k/n dipakai chart_series(); statistik pekerjaan hanya dari responden yang bekerja/wirausaha.
 */
function tracer_stats(?int $year = null): array
{
    $p    = $year ? [$year] : [];
    $base = $year ? 'WHERE t.graduation_year = ?' : 'WHERE 1 = 1';
    $work = $base . " AND t.employment_status IN ('bekerja','wirausaha')";

    $total = (int) db_scalar("SELECT COUNT(*) FROM tracer_studies t $base", $p);
    $alumniTotal = (int) db_scalar('SELECT COUNT(*) FROM alumni' . ($year ? ' WHERE graduation_year = ?' : ''), $p);

    $status    = db_all("SELECT t.employment_status AS k, COUNT(*) AS n FROM tracer_studies t $base GROUP BY t.employment_status", $p);
    $waiting   = db_all("SELECT t.waiting_time AS k, COUNT(*) AS n FROM tracer_studies t $work AND t.waiting_time IS NOT NULL GROUP BY t.waiting_time", $p);
    $salary    = db_all("SELECT t.salary_range AS k, COUNT(*) AS n FROM tracer_studies t $work AND t.salary_range IS NOT NULL GROUP BY t.salary_range", $p);
    $relevance = db_all("SELECT t.job_relevance AS k, COUNT(*) AS n FROM tracer_studies t $work AND t.job_relevance IS NOT NULL GROUP BY t.job_relevance", $p);
    $fields    = db_all("SELECT t.job_field AS field, COUNT(*) AS n FROM tracer_studies t $work AND t.job_field IS NOT NULL AND t.job_field <> ''
                         GROUP BY t.job_field ORDER BY n DESC, t.job_field LIMIT 5", $p);

    $count = function (array $rows, array $keys): int {
        $sum = 0;
        foreach ($rows as $r) {
            if (in_array($r['k'], $keys, true)) {
                $sum += (int) $r['n'];
            }
        }
        return $sum;
    };
    $relTotal = array_sum(array_map(fn($r) => (int) $r['n'], $relevance));

    return [
        'total'        => $total,
        'alumni_total' => $alumniTotal,
        'response_pct' => $alumniTotal > 0 ? (int) round($total / $alumniTotal * 100) : 0,
        'working_pct'  => $total > 0 ? (int) round($count($status, TRACER_WORKING) / $total * 100) : 0,
        'relevant_pct' => $relTotal > 0 ? (int) round($count($relevance, ['sangat_sesuai', 'sesuai']) / $relTotal * 100) : 0,
        'status'       => $status,
        'waiting'      => $waiting,
        'salary'       => $salary,
        'relevance'    => $relevance,
        'fields'       => $fields,
    ];
}

/** Tahun lulus yang tersedia untuk filter. */
function tracer_years(): array
{
    return array_map('intval', array_column(db_all('SELECT DISTINCT graduation_year FROM tracer_studies ORDER BY graduation_year DESC'), 'graduation_year'));
}

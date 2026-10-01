<?php
/** Unduh data tracer study sebagai CSV (bisa dibuka di Excel). Hanya admin. */
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/tracer.php';
require_role('admin');

$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: null;
$rows = db_all('SELECT a.full_name, a.nim, a.study_program, t.graduation_year, t.employment_status, t.company_name, t.position,
                       t.job_field, t.waiting_time, t.salary_range, t.job_relevance, t.feedback, t.updated_at
                FROM tracer_studies t JOIN alumni a ON a.id = t.alumni_id
                ' . ($year ? 'WHERE t.graduation_year = ?' : '') . ' ORDER BY t.graduation_year DESC, a.full_name', $year ? [$year] : []);

/** Cegah CSV injection: sel yang diawali = + - @ dianggap rumus oleh Excel. */
function csv_safe($v): string
{
    $v = (string) $v;
    return ($v !== '' && strpos("=+-@\t\r", $v[0]) !== false) ? "'" . $v : $v;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="tracer-study-' . ($year ?: 'semua') . '-' . date('Ymd') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
fputcsv($out, ['Nama', 'NIM', 'Program Studi', 'Tahun Lulus', 'Status', 'Perusahaan', 'Jabatan', 'Bidang', 'Lama Mendapat Kerja', 'Penghasilan', 'Kesesuaian', 'Masukan', 'Diperbarui']);
foreach ($rows as $r) {
    fputcsv($out, array_map('csv_safe', [
        $r['full_name'], $r['nim'], $r['study_program'], $r['graduation_year'],
        TRACER_STATUS_LABELS[$r['employment_status']] ?? '', $r['company_name'], $r['position'], $r['job_field'],
        TRACER_WAITING[$r['waiting_time']] ?? '', TRACER_SALARY[$r['salary_range']] ?? '', TRACER_RELEVANCE[$r['job_relevance']] ?? '',
        $r['feedback'], $r['updated_at'],
    ]));
}
fclose($out);

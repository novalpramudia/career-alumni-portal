<?php
/**
 * GET api/stats.php  -> data grafik dashboard admin (JSON)
 * Hanya bisa diakses admin yang sudah login.
 */
require_once __DIR__ . '/../config/auth.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Belum login.']);
    exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak.']);
    exit;
}

$appStatus = db_all('SELECT status AS k, COUNT(*) AS n FROM job_applications GROUP BY status');
$empStatus = db_all('SELECT employment_status AS k, COUNT(*) AS n FROM alumni WHERE employment_status IS NOT NULL GROUP BY employment_status');
$byYear    = db_all('SELECT graduation_year AS k, COUNT(*) AS n FROM alumni WHERE graduation_year IS NOT NULL GROUP BY graduation_year ORDER BY graduation_year');

$years = array_map(fn($r) => (string) $r['k'], $byYear);

echo json_encode([
    'applications_by_status' => chart_series($appStatus,
        ['pending', 'reviewed', 'accepted', 'rejected'],
        ['pending' => 'Menunggu', 'reviewed' => 'Ditinjau', 'accepted' => 'Diterima', 'rejected' => 'Ditolak']),
    'alumni_by_employment' => chart_series($empStatus,
        ['bekerja', 'wirausaha', 'studi_lanjut', 'mencari_kerja'],
        ['bekerja' => 'Bekerja', 'wirausaha' => 'Wirausaha', 'studi_lanjut' => 'Studi lanjut', 'mencari_kerja' => 'Mencari kerja']),
    'alumni_by_year' => chart_series($byYear, $years, []),
], JSON_UNESCAPED_UNICODE);

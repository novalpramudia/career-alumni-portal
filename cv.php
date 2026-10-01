<?php
/**
 * GET api/cv.php?id=<id lamaran>  -> menampilkan CV (PDF) setelah cek hak akses.
 * Boleh: admin, alumni pemilik lamaran, dan perusahaan pemilik lowongan tersebut.
 */
require_once __DIR__ . '/../config/auth.php';

if (!is_logged_in()) {
    http_response_code(401);
    exit('Silakan login terlebih dahulu.');
}

$id  = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$app = $id ? db_one('SELECT ap.cv_file, ap.alumni_id, j.company_id, a.full_name
                     FROM job_applications ap
                     JOIN jobs j ON j.id = ap.job_id
                     JOIN alumni a ON a.id = ap.alumni_id
                     WHERE ap.id = ?', [$id]) : null;

$allowed = false;
if ($app) {
    switch ($_SESSION['user']['role']) {
        case 'admin':   $allowed = true; break;
        case 'alumni':  $allowed = current_alumni_id() === (int) $app['alumni_id']; break;
        case 'company': $allowed = current_company_id() === (int) $app['company_id']; break;
    }
}

$path = $app ? UPLOAD_PATH . '/cv/' . basename($app['cv_file']) : '';
if (!$allowed || !is_file($path)) {
    // Respons sama untuk "tidak ada" dan "tidak berhak" agar tidak membocorkan data
    http_response_code(404);
    exit('CV tidak ditemukan.');
}

$downloadName = 'CV-' . trim(preg_replace('/[^A-Za-z0-9]+/', '-', $app['full_name']), '-') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, no-store');
readfile($path);

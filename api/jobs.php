<?php
/**
 * GET api/jobs.php?q=&location=&category=&type=&page=  -> hasil pencarian lowongan (JSON)
 * Dipakai live search di halaman lowongan alumni. Hanya untuk alumni yang login.
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/jobs.php';
header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Belum login.']);
    exit;
}
if ($_SESSION['user']['role'] !== 'alumni') {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak.']);
    exit;
}

$filters = job_filters_from($_GET);
$page    = max(1, (int) ($_GET['page'] ?? 1));
$res     = search_jobs($filters, $page);

echo json_encode([
    'total'      => $res['total'],
    'page'       => $res['page'],
    'pages'      => $res['pages'],
    'items'      => array_map(fn($j) => [
        'id' => (int) $j['id'], 'title' => $j['title'], 'company' => $j['company_name'],
        'location' => $j['location'], 'type' => $j['job_type'], 'deadline' => $j['deadline'],
    ], $res['items']),
    'html'       => render_job_grid($res['items']),
    'pagination' => render_pagination($res['page'], $res['pages'], $filters, 'alumni/jobs.php'),
], JSON_UNESCAPED_UNICODE);

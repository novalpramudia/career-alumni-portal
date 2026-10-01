<?php
/**
 * GET api/tracer-stats.php?year=  -> data grafik tracer study (JSON). Hanya admin.
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/tracer.php';
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

$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: null;
$s = tracer_stats($year);

echo json_encode([
    'total'     => $s['total'],
    'status'    => chart_series($s['status'],    TRACER_EMPLOYMENT,               TRACER_STATUS_LABELS),
    'waiting'   => chart_series($s['waiting'],   array_keys(TRACER_WAITING),      TRACER_WAITING),
    'salary'    => chart_series($s['salary'],    array_keys(TRACER_SALARY),       TRACER_SALARY),
    'relevance' => chart_series($s['relevance'], array_keys(TRACER_RELEVANCE),    TRACER_RELEVANCE),
], JSON_UNESCAPED_UNICODE);

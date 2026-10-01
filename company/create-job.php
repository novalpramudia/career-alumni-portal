<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/jobs.php';
require_role('company');

$companyId  = (int) current_company_id();
$categories = db_all('SELECT id, name FROM job_categories ORDER BY name');
$errors = [];
$job = ['job_type' => 'full-time', 'status' => 'open', 'deadline' => date('Y-m-d', strtotime('+30 days'))];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job = $_POST;
    if (!csrf_verify()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        [$data, $errors] = validate_job_input($_POST, array_map('intval', array_column($categories, 'id')));
        if (!$errors) {
            try {
                // company_id selalu dari session, bukan dari form
                db()->prepare('INSERT INTO jobs (company_id, category_id, title, description, requirements, location, job_type, salary, deadline, status)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$companyId, (int) $data['category_id'], $data['title'], $data['description'], $data['requirements'],
                               $data['location'], $data['job_type'], nullable($data['salary']), $data['deadline'], $data['status']]);
                flash('success', 'Lowongan berhasil dibuat.');
                redirect('company/jobs.php');
            } catch (PDOException $ex) {
                error_log('Create job error: ' . $ex->getMessage());
                $errors[] = 'Lowongan gagal disimpan. Coba lagi beberapa saat.';
            }
        }
    }
}

$pageTitle   = 'Buat lowongan';
$activeMenu  = 'company/create-job.php';
$submitLabel = 'Simpan lowongan';
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
include __DIR__ . '/_job-form.php';
include __DIR__ . '/../components/dash_end.php';

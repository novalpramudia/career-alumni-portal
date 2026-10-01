<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/jobs.php';
require_role('company');

$companyId = (int) current_company_id();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Hanya pemilik lowongan yang boleh mengedit
$existing = $id ? db_one('SELECT * FROM jobs WHERE id = ? AND company_id = ?', [$id, $companyId]) : null;
if (!$existing) {
    flash('danger', 'Lowongan tidak ditemukan atau bukan milik perusahaan Anda.');
    redirect('company/jobs.php');
}

$categories = db_all('SELECT id, name FROM job_categories ORDER BY name');
$errors = [];
$job = $existing;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job = $_POST;
    if (!csrf_verify()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.';
    } else {
        [$data, $errors] = validate_job_input($_POST, array_map('intval', array_column($categories, 'id')));
        if (!$errors) {
            try {
                db()->prepare('UPDATE jobs SET category_id = ?, title = ?, description = ?, requirements = ?, location = ?,
                               job_type = ?, salary = ?, deadline = ?, status = ? WHERE id = ? AND company_id = ?')
                    ->execute([(int) $data['category_id'], $data['title'], $data['description'], $data['requirements'], $data['location'],
                               $data['job_type'], nullable($data['salary']), $data['deadline'], $data['status'], $existing['id'], $companyId]);
                flash('success', 'Lowongan berhasil diperbarui.');
                redirect('company/jobs.php');
            } catch (PDOException $ex) {
                error_log('Edit job error: ' . $ex->getMessage());
                $errors[] = 'Lowongan gagal disimpan. Coba lagi beberapa saat.';
            }
        }
    }
}

$pageTitle   = 'Edit lowongan';
$activeMenu  = 'company/jobs.php';
$submitLabel = 'Simpan perubahan';
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
include __DIR__ . '/_job-form.php';
include __DIR__ . '/../components/dash_end.php';

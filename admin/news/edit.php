<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/news.php';
require_role('admin');

const NEWS_IMG_MAX = 2 * 1024 * 1024;
const NEWS_IMG_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$existing = $id ? db_one('SELECT * FROM news WHERE id = ?', [$id]) : null;
if (!$existing) {
    flash('danger', 'Berita tidak ditemukan.');
    redirect('admin/news/index.php');
}

$errors = [];
$news = $existing;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $news = $_POST;
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? $_POST['status'] : 'published';

    if (!csrf_verify()) { $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.'; }
    if (mb_strlen($title) < 5 || mb_strlen($title) > 200) { $errors[] = 'Judul harus 5 sampai 200 karakter.'; }
    if (mb_strlen($content) < 20) { $errors[] = 'Isi berita minimal 20 karakter.'; }

    $imgName = null;
    if (!$errors) {
        $up = handle_upload($_FILES['image'] ?? [], UPLOAD_PATH . '/news', NEWS_IMG_TYPES, NEWS_IMG_MAX);
        if (!$up['ok']) { $errors[] = $up['error']; } else { $imgName = $up['filename']; }
    }

    if (!$errors) {
        try {
            $imageValue = $imgName ?? $existing['image'];
            db()->prepare('UPDATE news SET title = ?, slug = ?, content = ?, image = ?, status = ? WHERE id = ?')
                ->execute([$title, make_slug($title, $existing['id']), $content, $imageValue, $status, $existing['id']]);
            if ($imgName && $existing['image']) {
                delete_upload(UPLOAD_PATH . '/news', $existing['image']);
            }
            flash('success', 'Berita berhasil diperbarui.');
            redirect('admin/news/index.php');
        } catch (PDOException $ex) {
            delete_upload(UPLOAD_PATH . '/news', $imgName);
            error_log('Edit news error: ' . $ex->getMessage());
            $errors[] = 'Berita gagal disimpan. Coba lagi beberapa saat.';
        }
    }
}

$pageTitle   = 'Edit berita';
$activeMenu  = 'admin/news/index.php';
$submitLabel = 'Simpan perubahan';
include __DIR__ . '/../../components/head.php';
include __DIR__ . '/../../components/sidebar.php';
include __DIR__ . '/_form.php';
include __DIR__ . '/../../components/dash_end.php';

<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/jobs.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id  = filter_input(INPUT_POST, 'news_id', FILTER_VALIDATE_INT);
    $row = $id ? db_one('SELECT id, title, status, image FROM news WHERE id = ?', [$id]) : null;

    if (!csrf_verify()) {
        flash('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
    } elseif (!$row) {
        flash('danger', 'Berita tidak ditemukan.');
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM news WHERE id = ?')->execute([$row['id']]);
        delete_upload(UPLOAD_PATH . '/news', $row['image']);
        flash('success', 'Berita "' . $row['title'] . '" dihapus.');
    } elseif ($action === 'toggle') {
        $new = $row['status'] === 'published' ? 'draft' : 'published';
        db()->prepare('UPDATE news SET status = ? WHERE id = ?')->execute([$new, $row['id']]);
        flash('success', $new === 'published' ? 'Berita dipublikasikan.' : 'Berita dijadikan draft.');
    }
    redirect('admin/news/index.php');
}

$rows = db_all('SELECT id, title, slug, status, created_at FROM news ORDER BY created_at DESC, id DESC');

$pageTitle    = 'Kelola berita';
$activeMenu   = 'admin/news/index.php';
$extraScripts = ['assets/js/admin-delete.js'];
include __DIR__ . '/../../components/head.php';
include __DIR__ . '/../../components/sidebar.php';
?>

<div class="panel">
  <div class="panel-head">
    <h2><?= count($rows) ?> berita</h2>
    <a class="btn btn-primary btn-sm" href="<?= e(url('admin/news/create.php')) ?>"><i class="bi bi-plus-lg"></i> Tulis berita</a>
  </div>
  <?php if (!$rows): ?>
    <div class="empty-state"><i class="bi bi-newspaper"></i>Belum ada berita.</div>
  <?php else: ?>
    <div class="table-responsive"><table class="table table-clean">
      <thead><tr><th>Judul</th><th>Tanggal</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $n): ?>
        <tr>
          <td><a class="fw-semibold text-decoration-none" href="<?= e(url('news-detail.php?slug=' . urlencode($n['slug']))) ?>" target="_blank"><?= e($n['title']) ?></a></td>
          <td class="text-nowrap"><?= e(date_id($n['created_at'])) ?></td>
          <td><?= status_badge($n['status'] === 'published' ? 'open' : 'closed') ?></td>
          <td>
            <div class="table-actions">
              <a class="btn btn-outline-primary btn-sm" href="<?= e(url('admin/news/edit.php?id=' . (int) $n['id'])) ?>">Edit</a>
              <form method="post" class="m-0"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="news_id" value="<?= (int) $n['id'] ?>">
                <button type="submit" class="btn btn-outline-secondary btn-sm"><?= $n['status'] === 'published' ? 'Jadikan draft' : 'Publikasikan' ?></button></form>
              <button type="button" class="btn btn-outline-danger btn-sm js-delete" data-bs-toggle="modal" data-bs-target="#delModal"
                      data-name="<?= e($n['title']) ?>" data-id="<?= (int) $n['id'] ?>" data-field="news_id">Hapus</button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>

<div class="modal fade" id="delModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><form method="post" class="modal-content">
  <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="news_id" value="">
  <div class="modal-header"><h2 class="modal-title h5">Hapus berita</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
  <div class="modal-body"><p class="mb-0">Hapus berita <strong data-name></strong>? Tindakan ini tidak bisa dibatalkan.</p></div>
  <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-danger">Ya, hapus</button></div>
</form></div></div>

<?php include __DIR__ . '/../../components/dash_end.php'; ?>

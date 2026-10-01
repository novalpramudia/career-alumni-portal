<?php
/**
 * Melihat profil alumni (read-only) sesuai hak akses:
 * admin = semua, alumni = miliknya sendiri, perusahaan = hanya pelamar di lowongannya.
 */
require_once __DIR__ . '/../config/auth.php';
require_role(['admin', 'alumni', 'company']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || !can_view_alumni($id)) {
    flash('danger', 'Anda tidak memiliki akses untuk melihat profil tersebut.');
    redirect(dashboard_path($_SESSION['user']['role']));
}

$a = db_one('SELECT a.*, u.email FROM alumni a JOIN users u ON u.id = a.user_id WHERE a.id = ?', [$id]);
if (!$a) {
    flash('warning', 'Data alumni tidak ditemukan.');
    redirect(dashboard_path($_SESSION['user']['role']));
}

$role   = $_SESSION['user']['role'];
$isSelf = $role === 'alumni';
// Alamat rumah hanya untuk pemilik profil dan admin
$showAddress = $isSelf || $role === 'admin';
$photo = photo_url($a['photo']);

$pageTitle  = 'Profil alumni';
$activeMenu = $role === 'admin' ? 'admin/alumni/index.php' : ($role === 'company' ? 'company/applicants.php' : 'alumni/profile.php');
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/sidebar.php';
?>

<div class="mb-3">
  <a href="#" class="btn btn-outline-secondary btn-sm" onclick="history.back(); return false;"><i class="bi bi-arrow-left"></i> Kembali</a>
  <?php if ($isSelf): ?>
    <a href="<?= e(url('alumni/profile.php')) ?>" class="btn btn-primary btn-sm">Ubah profil</a>
  <?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="panel"><div class="panel-body text-center">
      <?php if ($photo): ?>
        <img class="profile-photo" src="<?= e($photo) ?>" alt="Foto <?= e($a['full_name']) ?>">
      <?php else: ?>
        <div class="profile-photo" aria-hidden="true"><?= e(initials($a['full_name'])) ?></div>
      <?php endif; ?>
      <h2 class="h5 mt-3 mb-1"><?= e($a['full_name']) ?></h2>
      <p class="text-secondary mb-2"><?= e($a['study_program'] ?: 'Program studi belum diisi') ?></p>
      <?= status_badge($a['verification_status']) ?>
    </div></div>
  </div>

  <div class="col-lg-8">
    <div class="panel"><div class="panel-body">
      <dl class="info-list row mb-0">
        <div class="col-md-6"><dt>NIM</dt><dd><?= e($a['nim'] ?: '-') ?></dd></div>
        <div class="col-md-6"><dt>Email</dt><dd><a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a></dd></div>
        <div class="col-md-6"><dt>Nomor HP</dt><dd><?= e($a['phone'] ?: '-') ?></dd></div>
        <div class="col-md-6"><dt>Tahun lulus</dt><dd><?= e($a['graduation_year'] ?: '-') ?></dd></div>
        <div class="col-md-6"><dt>Fakultas</dt><dd><?= e($a['faculty'] ?: '-') ?></dd></div>
        <div class="col-md-6"><dt>Status pekerjaan</dt><dd><?= e(employment_label($a['employment_status'])) ?></dd></div>
        <div class="col-md-6"><dt>Perusahaan</dt><dd><?= e($a['company_name'] ?: '-') ?></dd></div>
        <div class="col-md-6"><dt>Jabatan</dt><dd><?= e($a['position'] ?: '-') ?></dd></div>
        <div class="col-12"><dt>LinkedIn</dt><dd>
          <?php if ($a['linkedin']): ?>
            <a href="<?= e($a['linkedin']) ?>" target="_blank" rel="noopener noreferrer"><?= e($a['linkedin']) ?></a>
          <?php else: ?>-<?php endif; ?>
        </dd></div>
        <?php if ($showAddress): ?>
          <div class="col-12"><dt>Alamat</dt><dd><?= nl2br(e($a['address'] ?: '-')) ?></dd></div>
        <?php endif; ?>
        <div class="col-12"><dt>Bio</dt><dd><?= nl2br(e($a['bio'] ?: '-')) ?></dd></div>
      </dl>
    </div></div>
  </div>
</div>

<?php include __DIR__ . '/../components/dash_end.php'; ?>

<?php
/**
 * Profil perusahaan (publik): bisa dilihat siapa saja, termasuk pengunjung yang belum login.
 * Menampilkan info perusahaan dan lowongan yang sedang dibuka.
 */
require_once __DIR__ . '/../config/auth.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$company = $id ? db_one('SELECT * FROM companies WHERE id = ?', [$id]) : null;
if (!$company) {
    http_response_code(404);
    flash('warning', 'Perusahaan tidak ditemukan.');
    redirect('index.php');
}

$jobs = db_all("SELECT j.id, j.title, j.location, j.job_type, j.salary, j.deadline,
                       c.name AS company_name, c.logo AS company_logo, k.name AS category_name
                FROM jobs j
                JOIN companies c ON c.id = j.company_id
                JOIN job_categories k ON k.id = j.category_id
                WHERE j.company_id = ? AND j.status = 'open' AND j.deadline >= CURDATE()
                ORDER BY j.deadline ASC", [$company['id']]);

$pageTitle = $company['name'];
include __DIR__ . '/../components/head.php';
include __DIR__ . '/../components/navbar.php';
?>
<section class="section">
  <div class="container">
    <?= render_flashes() ?>
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="panel"><div class="panel-body text-center">
          <?php if ($logo = logo_url($company['logo'])): ?>
            <img class="profile-photo logo-square" src="<?= e($logo) ?>" alt="Logo <?= e($company['name']) ?>">
          <?php else: ?>
            <div class="profile-photo logo-square" aria-hidden="true"><?= e(initials($company['name'])) ?></div>
          <?php endif; ?>
          <h1 class="h4 mt-3 mb-1"><?= e($company['name']) ?></h1>
          <p class="text-secondary mb-0"><?= e($company['industry'] ?: 'Industri belum diisi') ?></p>
        </div></div>

        <div class="panel mt-3"><div class="panel-body">
          <dl class="info-list mb-0">
            <dt>Alamat</dt><dd><?= e($company['address'] ?: '-') ?></dd>
            <dt>Email</dt><dd><?= $company['email'] ? '<a href="mailto:' . e($company['email']) . '">' . e($company['email']) . '</a>' : '-' ?></dd>
            <dt>Telepon</dt><dd><?= e($company['phone'] ?: '-') ?></dd>
            <dt>Website</dt>
            <dd class="mb-0">
              <?php if ($company['website']): ?>
                <a href="<?= e($company['website']) ?>" target="_blank" rel="noopener noreferrer"><?= e($company['website']) ?></a>
              <?php else: ?>-<?php endif; ?>
            </dd>
          </dl>
        </div></div>
      </div>

      <div class="col-lg-8">
        <h2 class="h5">Tentang perusahaan</h2>
        <p class="mb-4"><?= nl2br(e($company['description'] ?: 'Perusahaan ini belum menambahkan deskripsi.')) ?></p>

        <h2 class="h5">Lowongan yang dibuka (<?= count($jobs) ?>)</h2>
        <?php if (!$jobs): ?>
          <div class="panel"><div class="empty-state"><i class="bi bi-briefcase"></i>Belum ada lowongan yang dibuka.</div></div>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($jobs as $job): ?>
              <div class="col-md-6"><?= job_card($job) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
<?php
include __DIR__ . '/../components/footer.php';
include __DIR__ . '/../components/scripts.php';

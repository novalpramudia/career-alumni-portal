<?php
require_once __DIR__ . '/config/auth.php';

/* ---------- Data untuk landing page ---------- */
$activeJob = "j.status = 'open' AND j.deadline >= CURDATE()";
$jobSelect = "SELECT j.id, j.title, j.location, j.job_type, j.salary, j.deadline,
                     c.name AS company_name, c.logo AS company_logo, k.name AS category_name
              FROM jobs j
              JOIN companies c ON c.id = j.company_id
              JOIN job_categories k ON k.id = j.category_id
              WHERE $activeJob";

$stats = [
    'alumni'    => (int) db_scalar('SELECT COUNT(*) FROM alumni'),
    'working'   => (int) db_scalar("SELECT COUNT(*) FROM alumni WHERE employment_status IN ('bekerja','wirausaha')"),
    'companies' => (int) db_scalar('SELECT COUNT(*) FROM companies'),
    'jobs'      => (int) db_scalar("SELECT COUNT(*) FROM jobs j WHERE $activeJob"),
];

$latestJobs  = db_all($jobSelect . ' ORDER BY j.created_at DESC, j.id DESC LIMIT 6');
$closingJobs = db_all($jobSelect . ' ORDER BY j.deadline ASC LIMIT 3');
$categories  = db_all('SELECT id, name FROM job_categories ORDER BY name');
$locations   = db_all("SELECT j.location, COUNT(*) AS n FROM jobs j WHERE $activeJob GROUP BY j.location ORDER BY n DESC LIMIT 4");

$companies = db_all("SELECT c.id, c.name, c.logo, c.industry, COUNT(j.id) AS open_jobs
                     FROM companies c
                     LEFT JOIN jobs j ON j.company_id = c.id AND $activeJob
                     GROUP BY c.id, c.name, c.logo, c.industry
                     ORDER BY open_jobs DESC, c.name LIMIT 6");

$news = db_all("SELECT title, slug, content, created_at FROM news WHERE status = 'published' ORDER BY created_at DESC, id DESC LIMIT 3");

$workingPct = $stats['alumni'] > 0 ? round($stats['working'] / $stats['alumni'] * 100) : 0;

$pageTitle = 'Beranda';
include __DIR__ . '/components/head.php';
include __DIR__ . '/components/navbar.php';
?>

<?php if ($flashes = render_flashes()): ?>
  <div class="container pt-3"><?= $flashes ?></div>
<?php endif; ?>

<!-- HERO -->
<header class="hero">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-7">
        <h1>Temukan pekerjaan pertama Anda, atau yang berikutnya.</h1>
        <p class="lead mt-3 mb-0">Lowongan dari perusahaan mitra kampus, khusus untuk alumni.</p>

        <form class="search-box" action="<?= e(url('alumni/jobs.php')) ?>" method="get" role="search">
          <input type="search" class="form-control" name="q" placeholder="Posisi atau kata kunci, misalnya web developer" aria-label="Kata kunci lowongan">
          <select class="form-select" name="category" aria-label="Kategori">
            <option value="">Semua kategori</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int) $cat['id'] ?>"><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-accent px-4">Cari lowongan</button>
        </form>

        <?php if ($locations): ?>
          <div class="hero-chips">
            <span>Lokasi populer:</span>
            <?php foreach ($locations as $loc): ?>
              <a href="<?= e(url('alumni/jobs.php?location=' . urlencode($loc['location']))) ?>"><?= e($loc['location']) ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="col-lg-5">
        <div class="closing-panel">
          <h2>Segera ditutup</h2>
          <?php if (!$closingJobs): ?>
            <div class="empty-state py-3">Belum ada lowongan aktif.</div>
          <?php endif; ?>
          <?php foreach ($closingJobs as $job): ?>
            <a class="closing-row" href="<?= e(url('alumni/job-detail.php?id=' . (int) $job['id'])) ?>">
              <?= avatar_html($job['company_name'], $job['company_logo'] ?? null) ?>
              <span class="min-w-0">
                <span class="closing-title d-block"><?= e($job['title']) ?></span>
                <span class="small text-secondary"><?= e($job['company_name']) ?> &middot; tutup <?= e(date_id($job['deadline'])) ?></span>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- LOWONGAN TERBARU -->
<section class="section" id="lowongan">
  <div class="container">
    <div class="section-head">
      <div>
        <h2>Lowongan terbaru</h2>
        <p>Dibuka oleh perusahaan mitra dan masih menerima lamaran.</p>
      </div>
      <a class="btn btn-outline-primary" href="<?= e(url('alumni/jobs.php')) ?>">Lihat semua lowongan</a>
    </div>
    <?php if (!$latestJobs): ?>
      <div class="empty-state"><i class="bi bi-briefcase"></i>Belum ada lowongan yang dibuka.</div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($latestJobs as $job): ?>
          <div class="col-md-6 col-lg-4"><?= job_card($job) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- STATISTIK -->
<section class="section section-alt" aria-label="Statistik">
  <div class="container">
    <div class="stat-band">
      <div><div class="num"><?= e($stats['alumni']) ?></div><div class="lbl">Alumni terdaftar</div></div>
      <div><div class="num"><?= e($workingPct) ?>%</div><div class="lbl">Alumni sudah bekerja atau berwirausaha</div></div>
      <div><div class="num"><?= e($stats['companies']) ?></div><div class="lbl">Perusahaan mitra</div></div>
      <div><div class="num"><?= e($stats['jobs']) ?></div><div class="lbl">Lowongan aktif</div></div>
    </div>
  </div>
</section>

<!-- PERUSAHAAN -->
<section class="section" id="perusahaan">
  <div class="container">
    <div class="section-head">
      <div>
        <h2>Perusahaan yang terdaftar</h2>
        <p>Mitra kampus yang membuka kesempatan untuk alumni.</p>
      </div>
    </div>
    <?php if (!$companies): ?>
      <div class="empty-state"><i class="bi bi-buildings"></i>Belum ada perusahaan terdaftar.</div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($companies as $co): ?>
          <div class="col-md-6 col-lg-4">
            <a class="company-tile text-decoration-none text-reset" href="<?= e(url('company/profile-view.php?id=' . (int) $co['id'])) ?>">
              <?= avatar_html($co['name'], $co['logo']) ?>
              <div class="min-w-0">
                <div class="fw-semibold"><?= e($co['name']) ?></div>
                <div class="small text-secondary"><?= e($co['industry'] ?: 'Industri belum diisi') ?> &middot; <?= (int) $co['open_jobs'] ?> lowongan aktif</div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- CAREER CENTER -->
<section class="section section-alt" id="career-center">
  <div class="container">
    <div class="section-head">
      <div>
        <h2>Layanan career center</h2>
        <p>Pendampingan karier untuk mahasiswa dan alumni.</p>
      </div>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-3"><div class="service-item"><h3>Konsultasi karier</h3><p class="small text-secondary mb-0">Diskusi minat, peluang, dan rencana karier bersama konselor.</p></div></div>
      <div class="col-md-6 col-lg-3"><div class="service-item"><h3>Workshop CV dan wawancara</h3><p class="small text-secondary mb-0">Latihan menyusun CV dan simulasi wawancara dengan praktisi.</p></div></div>
      <div class="col-md-6 col-lg-3"><div class="service-item"><h3>Job fair</h3><p class="small text-secondary mb-0">Bertemu langsung dengan perusahaan mitra setiap tahun.</p></div></div>
      <div class="col-md-6 col-lg-3"><div class="service-item"><h3>Tracer study</h3><p class="small text-secondary mb-0">Masukan alumni dipakai untuk memperbaiki kurikulum kampus.</p></div></div>
    </div>
  </div>
</section>

<!-- BERITA -->
<section class="section" id="berita">
  <div class="container">
    <div class="section-head">
      <div>
        <h2>Berita terbaru</h2>
        <p>Kabar dan tips dari career center.</p>
      </div>
      <a class="btn btn-outline-primary" href="<?= e(url('news.php')) ?>">Lihat semua berita</a>
    </div>
    <?php if (!$news): ?>
      <div class="empty-state"><i class="bi bi-newspaper"></i>Belum ada berita.</div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($news as $n): ?>
          <div class="col-md-6 col-lg-4">
            <a class="news-card d-block text-decoration-none text-reset h-100" href="<?= e(url('news-detail.php?slug=' . urlencode($n['slug']))) ?>">
              <time datetime="<?= e(substr($n['created_at'], 0, 10)) ?>"><?= e(date_id($n['created_at'])) ?></time>
              <h3 class="h6 mt-2"><?= e($n['title']) ?></h3>
              <p class="small text-secondary mb-0"><?= e(excerpt($n['content'], 130)) ?></p>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php
include __DIR__ . '/components/footer.php';
include __DIR__ . '/components/scripts.php';

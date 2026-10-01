<footer class="site-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-5">
        <div class="d-flex align-items-center gap-2 mb-3 text-white fw-bold"><span class="brand-mark">C</span> <?= e(APP_NAME) ?></div>
        <p class="mb-0">Pusat informasi alumni, lowongan kerja, dan layanan career center kampus.</p>
      </div>
      <div class="col-6 col-lg-3">
        <h2 class="h6 text-white">Menu</h2>
        <ul class="list-unstyled mb-0">
          <li><a href="<?= e(url('index.php#lowongan')) ?>">Lowongan terbaru</a></li>
          <li><a href="<?= e(url('index.php#perusahaan')) ?>">Perusahaan mitra</a></li>
          <li><a href="<?= e(url('news.php')) ?>">Berita</a></li>
          <li><a href="<?= e(url('auth/login.php')) ?>">Masuk</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-4">
        <h2 class="h6 text-white">Career center</h2>
        <ul class="list-unstyled mb-0">
          <li>Gedung Rektorat lantai 2</li>
          <li>Senin - Jumat, 08.00 - 16.00</li>
          <li>careercenter@kampus.example.ac.id</li>
        </ul>
      </div>
    </div>
    <hr class="border-secondary-subtle my-4">
    <p class="small mb-0">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Project mata kuliah Pemrograman Web Lanjut.</p>
  </div>
</footer>

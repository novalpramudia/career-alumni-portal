# Panduan Pengujian — Career & Alumni Portal

Checklist manual untuk memastikan semua fitur dari Tahap 2 sampai 9 berjalan sebelum presentasi.
Jalankan dari atas ke bawah dengan browser biasa (tidak perlu tool khusus).

## Persiapan
- [ ] XAMPP: Apache dan MySQL menyala.
- [ ] Database `career_alumni` sudah diimport dari `database/career_alumni.sql`.
- [ ] `config/database.php` sesuai pengaturan MySQL Anda (default XAMPP: user `root`, password kosong).
- [ ] Buka `http://localhost/career-alumni-portal/` — landing page tampil tanpa error PHP.

## 1. Autentikasi & Otorisasi
- [ ] Login admin/alumni/perusahaan dengan akun demo berhasil, masing-masing masuk ke dashboard yang berbeda.
- [ ] Login dengan password salah 5 kali berturut-turut → muncul pesan terkunci sementara.
- [ ] Saat login, coba buka langsung `admin/dashboard.php` sebagai alumni → dialihkan dengan pesan tidak punya akses.
- [ ] Logout, lalu tekan tombol Back browser → tidak bisa masuk kembali ke halaman dashboard tanpa login ulang.
- [ ] Register alumni baru dengan NIM yang sudah dipakai → ditolak dengan pesan jelas.

## 2. Modul Alumni
- [ ] Ubah profil, kelengkapan profil di dashboard bertambah.
- [ ] Upload foto > 2 MB atau file bukan gambar → ditolak.
- [ ] Ubah nama/NIM pada alumni yang sudah terverifikasi → status kembali "Menunggu".
- [ ] Buka `alumni/profile-view.php?id=<id alumni lain>` → ditolak (bukan admin/bukan diri sendiri/bukan pelamar ke lowongan perusahaan Anda).

## 3. Modul Perusahaan
- [ ] Lengkapi profil perusahaan dan upload logo, muncul di landing page dan kartu lowongan.
- [ ] Buka halaman publik `company/profile-view.php?id=` tanpa login — tetap bisa diakses pengunjung.

## 4. Modul Lowongan
- [ ] Buat lowongan dengan deadline kemarin dan status "Dibuka" → ditolak.
- [ ] Edit lowongan milik perusahaan lain lewat URL (`company/edit-job.php?id=`) → ditolak.
- [ ] Alumni mencari lowongan dengan kata kunci → hasil berubah tanpa reload (live search).
- [ ] Tutup lowongan dari sisi perusahaan → tidak lagi muncul di pencarian aktif alumni.

## 5. Modul Lamaran
- [ ] Alumni melamar dengan CV bukan PDF (ganti ekstensi `.jpg` jadi `.pdf`) → ditolak.
- [ ] Alumni melamar dua kali ke lowongan yang sama → ditolak/diarahkan ke halaman lamaran.
- [ ] Perusahaan mengubah status lamaran → status ikut berubah di halaman "Lamaran saya" milik alumni.
- [ ] Buka `uploads/cv/dummy_cv.pdf` langsung lewat URL browser → harus diblokir (403).
- [ ] Login sebagai perusahaan lain yang tidak terkait, buka `api/cv.php?id=<id lamaran milik perusahaan lain>` → 404.
- [ ] Alumni menarik lamaran berstatus "Menunggu" → berhasil dan file CV terhapus; lamaran berstatus lain tidak bisa ditarik.

## 6. Modul Tracer Study
- [ ] Isi tracer study dengan status "Mencari kerja" → field pekerjaan tersembunyi dan tidak wajib.
- [ ] Isi tracer study dengan status "Bekerja" tapi field pekerjaan dikosongkan → ditolak.
- [ ] Isi ulang tracer study yang sudah ada → memperbarui data lama, bukan membuat baris baru.
- [ ] Dashboard admin → Tracer study: filter tahun lulus mengubah angka dan grafik.
- [ ] Unduh CSV tracer study, buka di Excel/LibreOffice — data terbaca rapi.

## 7. Modul Admin
- [ ] Verifikasi dan tolak alumni dari halaman Kelola Alumni, status berubah sesuai.
- [ ] Hapus satu alumni/perusahaan contoh, pastikan data terkait (lamaran, lowongan) ikut hilang tanpa error.
- [ ] Tulis berita baru dengan gambar, publikasikan, buka link publiknya.
- [ ] Jadikan berita draft → tidak muncul di `news.php` publik, tapi tetap bisa dibuka admin yang login.

## 8. Keamanan dasar
- [ ] Buka `http://localhost/career-alumni-portal/config/database.php` langsung → harus 403 Forbidden.
- [ ] Buka `http://localhost/career-alumni-portal/database/career_alumni.sql` langsung → harus 403 Forbidden.
- [ ] Coba submit form (misalnya ubah profil) tanpa token CSRF (misalnya lewat refresh form lama/tab lama yang dibuka sejak lama) → ditolak dengan pesan sesi tidak valid.
- [ ] Pastikan tidak ada pesan error PHP (Warning/Fatal error) yang tampil ke pengunjung dalam kondisi normal.

## 9. Responsif
- [ ] Perkecil lebar browser ke ukuran HP — navbar dan sidebar berubah menjadi menu geser (offcanvas), tabel bisa digeser horizontal.

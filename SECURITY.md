# Catatan Keamanan — Career & Alumni Portal

Ringkasan langkah keamanan yang diterapkan, untuk referensi saat presentasi/demo Tahap 10.

## 1. Autentikasi & Sesi
- Password memakai `password_hash()` (bcrypt) dan `password_verify()`. Tidak ada password plaintext di database.
- `session_regenerate_id(true)` dipanggil setiap login berhasil (mencegah session fixation).
- Cookie sesi: `httponly` (tidak bisa dibaca JavaScript) dan `samesite=Lax`.
- Login dibatasi 5 kali gagal berturut-turut, lalu dikunci 60 detik (`auth/login.php`).
- Waktu respons login disamakan antara "email tidak ditemukan" dan "password salah" (pakai `DUMMY_HASH`) agar email terdaftar tidak bisa ditebak dari kecepatan respons.
- Logout hanya lewat POST + token CSRF, supaya tidak bisa dipicu dari link/gambar milik pihak lain.

## 2. Otorisasi (Role-Based Access Control)
- `require_role()` di `config/auth.php` dipanggil di awal setiap halaman yang perlu dibatasi. Mencegah alumni membuka halaman admin, dsb.
- Kepemilikan data selalu dicek lewat kondisi SQL, bukan dipercaya dari input form:
  - Perusahaan mengedit/menghapus lowongan: `WHERE id = ? AND company_id = ?`.
  - Perusahaan mengubah status lamaran: lamaran harus terhubung ke lowongan miliknya.
  - `can_view_alumni()` membatasi siapa yang boleh membuka profil alumni tertentu.
- `company_id` dan `alumni_id` **selalu** diambil dari session (`current_company_id()`, `current_alumni_id()`), bukan dari field form tersembunyi.

## 3. Database
- Seluruh query memakai PDO prepared statement (`db()->prepare(...)->execute([...])`). Tidak ada input pengguna yang digabung langsung ke string SQL.
- `PDO::ATTR_EMULATE_PREPARES => false` — prepared statement asli dari MySQL, bukan emulasi PHP.
- Pencarian dengan `LIKE` meng-escape karakter `%` dan `_` dari pengguna (`addcslashes($q, '%_\\')`) supaya tidak bisa dipakai sebagai wildcard oleh pengguna.
- Foreign key dengan `ON DELETE CASCADE` memastikan data anak (lamaran, tracer study) ikut terhapus saat data induk dihapus, konsisten dengan penghapusan file upload yang dilakukan manual di kode.

## 4. Upload File
- Tipe file dicek dari **isi file** (`finfo`), bukan dari ekstensi yang dikirim browser — mencegah file `.php` yang diganti nama menjadi `.jpg`.
- Nama file disimpan ulang secara acak (`random_bytes`), mencegah tebakan nama file dan penimpaan file.
- Validasi ukuran: foto/logo/berita maksimal 2 MB, CV maksimal 2 MB (PDF saja).
- `uploads/.htaccess` memblokir eksekusi script PHP dan directory listing di semua subfolder upload.
- `uploads/cv/.htaccess` memblokir **semua** akses langsung — CV pribadi hanya bisa dibuka lewat `api/cv.php?id=` setelah lolos pengecekan hak akses (admin, alumni pemilik, atau perusahaan pemilik lowongan).

## 5. CSRF (Cross-Site Request Forgery)
- Semua form POST (login, register, update profil, hapus akun, buat/edit/hapus lowongan, melamar, ubah status lamaran, dll.) menyertakan token (`csrf_field()`) yang diverifikasi dengan `hash_equals()` di server (`csrf_verify()`).

## 6. XSS (Cross-Site Scripting)
- Semua output dinamis ke HTML melewati `e()` (`htmlspecialchars` dengan `ENT_QUOTES`), termasuk lewat closure `$v()`/`$fv()` yang dipakai di form-form besar.
- Konten yang ditampilkan lewat modal JavaScript (cover letter, masukan tracer study) memakai `textContent`, bukan `innerHTML`, sehingga tidak bisa menyisipkan tag HTML/script.

## 7. Lain-lain
- `config/`, `components/`, dan `database/` punya `.htaccess` sendiri yang memblokir akses langsung lewat URL — folder ini hanya untuk di-include oleh PHP.
- `display_errors` dimatikan (`APP_DEBUG = false` di `config/database.php`) agar pesan error teknis (path server, query SQL) tidak terlihat pengunjung. Ubah ke `true` hanya saat development di komputer sendiri.
- Header keamanan dasar (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`) diset lewat `.htaccess` di folder utama.
- Ekspor CSV tracer study membubuhi tanda kutip di sel yang diawali `=`, `+`, `-`, `@` untuk mencegah CSV/formula injection saat dibuka di Excel.

## Batasan yang disadari (wajar untuk tugas kuliah)
- Tidak ada rate limiting di level jaringan (hanya di level aplikasi untuk login) — cukup untuk demo lokal.
- Tidak ada verifikasi email saat registrasi (di luar cakupan tugas).
- HTTPS tidak dikonfigurasi karena berjalan di XAMPP lokal (`http://localhost`).

## 8. Pengelolaan Informasi Sensitif
Untuk menjaga kerahasiaan informasi pengguna dan konfigurasi aplikasi, beberapa hal berikut perlu diperhatikan:
- Kredensial database, API key, token autentikasi, dan informasi sensitif lainnya tidak boleh disimpan di repository publik.
- Konfigurasi yang berisi informasi sensitif sebaiknya disimpan dalam file konfigurasi lokal yang tidak diunggah ke GitHub.
- File `.env`, apabila digunakan, harus dimasukkan ke dalam `.gitignore`. File contoh seperti `.env.example` dapat disediakan tanpa mencantumkan nilai rahasia yang sebenarnya.
- Password dan informasi autentikasi pengguna tidak boleh dicatat dalam log aplikasi.
- Apabila informasi sensitif terlanjur terunggah ke repository, segera ganti atau cabut kredensial tersebut. Menghapus file dari commit terbaru saja tidak menjamin informasi tersebut hilang dari riwayat Git.

## 9. Rekomendasi Keamanan Deployment
Sebelum aplikasi digunakan pada lingkungan produksi, beberapa langkah keamanan berikut perlu diperhatikan:
- **HTTPS:** Gunakan HTTPS untuk melindungi komunikasi antara pengguna dan server, terutama ketika pengguna memasukkan kredensial atau mengakses data pribadi.
- **Konfigurasi produksi:** Pastikan mode debug dinonaktifkan dan pesan kesalahan teknis tidak ditampilkan kepada pengguna.
- **Hak akses file:** Batasi izin baca dan tulis pada file serta direktori sesuai kebutuhan aplikasi.
- **Konfigurasi server:** Pastikan directory listing dinonaktifkan dan file konfigurasi internal tidak dapat diakses secara langsung melalui browser.
- **Manajemen akun:** Pastikan setiap peran pengguna hanya memiliki akses sesuai dengan tanggung jawabnya.
- **Pencadangan database:** Lakukan backup secara berkala dan pastikan proses pemulihan data dapat berjalan dengan baik.
- **Pembaruan perangkat lunak:** Periksa dan perbarui PHP, MySQL atau MariaDB, serta komponen lain yang digunakan agar kerentanan yang telah diketahui dapat ditangani.
Catatan: HTTPS dan konfigurasi keamanan produksi harus disesuaikan dengan lingkungan hosting yang digunakan. Pengaturan untuk pengembangan lokal tidak otomatis menjamin keamanan ketika aplikasi dipublikasikan.
## 10. Rekomendasi Pengujian Keamanan
Pengujian keamanan perlu dilakukan untuk memastikan bahwa mekanisme perlindungan aplikasi bekerja sesuai dengan yang diharapkan.
| No. | Skenario Pengujian | Hasil yang Diharapkan |
|---|---|---|
| 1 | Pengguna memasukkan password yang salah saat login. | Login ditolak dan pembatasan percobaan login diterapkan sesuai konfigurasi. |
| 2 | Alumni mencoba membuka halaman khusus admin. | Akses ditolak atau pengguna diarahkan ke halaman yang sesuai. |
| 3 | Pengguna mengirimkan formulir tanpa token CSRF yang valid. | Permintaan ditolak oleh server. |
| 4 | Pengguna memasukkan karakter atau skrip HTML berbahaya pada kolom input. | Input ditampilkan dengan aman dan tidak dieksekusi sebagai skrip. |
| 5 | Pengguna mengunggah file dengan tipe yang tidak diizinkan. | File ditolak oleh server. |
| 6 | Perusahaan mencoba mengubah lowongan milik perusahaan lain. | Perubahan ditolak karena pengguna tidak memiliki hak atas data tersebut. |
| 7 | Pengguna yang tidak berwenang mencoba mengakses CV alumni. | Akses ditolak oleh mekanisme otorisasi aplikasi. |
| 8 | Pengguna mencoba mengakses file konfigurasi internal melalui URL. | File tidak dapat diakses secara langsung. |
**Catatan:** Tabel ini merupakan skenario pengujian yang direkomendasikan, bukan pernyataan bahwa seluruh pengujian telah dilakukan atau berhasil. Hasil aktual perlu dicatat setelah setiap skenario dijalankan.

## 11. Prosedur Pelaporan Kerentanan
Apabila ditemukan dugaan kerentanan keamanan pada Career & Alumni Portal, pelapor disarankan mengikuti prosedur berikut:
1. Identifikasi bagian aplikasi yang diduga memiliki kerentanan.
2. Siapkan penjelasan singkat mengenai masalah, kondisi terjadinya, serta dampak yang mungkin ditimbulkan.
3. Sertakan langkah reproduksi yang aman dan bukti pendukung seperlunya tanpa menyertakan password, token, data pribadi, atau informasi sensitif lainnya.
4. Laporkan masalah kepada pengelola repository atau tim pengembang melalui saluran komunikasi privat yang telah disepakati.
5. Hindari memublikasikan rincian kerentanan sebelum pengelola memperoleh kesempatan untuk memeriksa dan menangani masalah tersebut.
6. Setelah perbaikan dilakukan, lakukan pengujian ulang untuk memastikan kerentanan telah ditangani dan tidak menimbulkan masalah baru.
Untuk menjaga keamanan pengguna, jangan mencoba mengakses, mengubah, atau menghapus data milik pihak lain ketika melakukan pengujian keamanan.

## 12. Tinjauan dan Pemeliharaan Keamanan
Dokumentasi keamanan perlu diperbarui ketika terdapat perubahan pada fitur, arsitektur aplikasi, konfigurasi server, atau mekanisme autentikasi dan otorisasi.
Setiap perubahan yang berkaitan dengan keamanan sebaiknya melalui peninjauan kode dan pengujian yang sesuai sebelum digabungkan ke branch utama.
Dokumentasi ini menjadi panduan bagi tim pengembang untuk menjaga keamanan aplikasi selama proses pengembangan, pengujian, dan pemeliharaan.

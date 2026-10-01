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

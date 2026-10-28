
# Career & Alumni Portal

Aplikasi web full-stack untuk pengelolaan informasi alumni, lowongan pekerjaan, lamaran kerja, tracer study, dan layanan career center kampus. Project ini dikembangkan sebagai tugas mata kuliah **Pemrograman Web Lanjut** menggunakan PHP Native, MySQL, HTML5, CSS3, JavaScript, dan Bootstrap 5.

## 1. Deskripsi Project

Career & Alumni Portal merupakan aplikasi web kampus yang dirancang sebagai pusat informasi alumni, lowongan pekerjaan, tracer study, dan layanan career center. Aplikasi ini menghubungkan tiga pihak utama, yaitu Alumni, Perusahaan, dan Admin (Career Center), dalam satu sistem yang terintegrasi.

Alumni dapat mengelola profil profesional, mencari lowongan pekerjaan, mengirim lamaran, serta mengisi tracer study untuk membantu kampus mengevaluasi perkembangan karier lulusannya. Perusahaan dapat mempublikasikan lowongan, melihat pelamar, dan mengelola proses rekrutmen. Sementara itu, Admin bertugas mengelola data, memverifikasi alumni, memantau statistik, dan memublikasikan informasi kampus.

Aplikasi dikembangkan sebagai implementasi pembelajaran full-stack web development, mulai dari pembuatan antarmuka, pengolahan data di sisi server, integrasi database, autentikasi pengguna, hingga penerapan dasar keamanan aplikasi web.

## 2. Tujuan Pengembangan

Tujuan pengembangan Career & Alumni Portal adalah:

1. Menyediakan pusat informasi alumni dan peluang kerja yang terintegrasi.
2. Mempermudah alumni mencari pekerjaan dan memantau status lamaran.
3. Membantu perusahaan mempublikasikan lowongan dan mengelola pelamar.
4. Mendukung pengumpulan data tracer study sebagai bahan evaluasi lulusan.
5. Mempermudah career center mengelola data alumni, perusahaan, berita, dan statistik.
6. Menerapkan konsep pengembangan aplikasi web full-stack dan database relasional.
7. Menerapkan autentikasi, pembatasan hak akses, validasi input, dan pengelolaan file.

## 3. Fitur Aplikasi

### 3.1 Admin / Career Center

Admin memiliki akses untuk mengelola data dan aktivitas career center.

Fitur utama:
- Login dan logout administrator.
- Dashboard dengan ringkasan data dan grafik statistik.
- Pengelolaan data alumni.
- Verifikasi data alumni.
- Pengelolaan data perusahaan.
- Pengelolaan lowongan pekerjaan.
- Pemantauan dan pengelolaan lamaran kerja.
- Pengelolaan data tracer study.
- Visualisasi statistik tracer study.
- Ekspor data tracer study ke format CSV.
- Pembuatan, pengeditan, penghapusan, dan publikasi berita.

### 3.2 Alumni

Alumni dapat menggunakan portal untuk mengembangkan profil profesional dan mencari peluang karier.

Fitur utama:
- Registrasi akun dan login.
- Dashboard alumni.
- Melengkapi dan memperbarui profil.
- Mengunggah foto profil.
- Mencari dan memfilter lowongan pekerjaan.
- Melihat detail lowongan dan persyaratan.
- Mengirim lamaran pekerjaan.
- Mengunggah CV dalam format PDF.
- Menyertakan cover letter pada lamaran.
- Melihat riwayat dan status lamaran.
- Menarik lamaran sesuai aturan aplikasi.
- Mengisi dan memperbarui tracer study.
- Membaca berita dan informasi kampus.
- Mengelola akun pribadi sesuai ketentuan sistem.
- Logout.

### 3.3 Perusahaan

Perusahaan dapat menggunakan portal untuk mempublikasikan lowongan dan mengelola proses rekrutmen.

Fitur utama:
- Registrasi akun dan login.
- Dashboard perusahaan.
- Melengkapi dan memperbarui profil perusahaan.
- Mengunggah logo perusahaan.
- Membuat lowongan pekerjaan.
- Mengedit dan menghapus lowongan milik sendiri.
- Membuka atau menutup lowongan sesuai status yang didukung aplikasi.
- Melihat daftar pelamar.
- Mengakses CV dan cover letter pelamar sesuai hak akses.
- Memperbarui status lamaran.
- Mengelola akun perusahaan sesuai ketentuan sistem.
- Logout.

### 3.4 Halaman Publik

Pengunjung dapat mengakses informasi umum yang disediakan portal.

Fitur utama:
- Landing page.
- Ringkasan statistik portal.
- Daftar lowongan pekerjaan terbaru.
- Daftar perusahaan.
- Halaman profil perusahaan publik.
- Daftar berita kampus.
- Halaman detail berita.

## 4. Teknologi yang Digunakan

| Komponen | Teknologi | Fungsi |
|---|---|---|
| Frontend | HTML5 | Struktur halaman web |
| Styling | CSS3 | Desain dan tata letak antarmuka |
| Interaksi | JavaScript | Interaksi dinamis pada browser |
| UI Framework | Bootstrap 5 | Komponen dan layout responsif |
| Ikon | Bootstrap Icons | Ikon antarmuka |
| Backend | PHP Native | Logika aplikasi dan pemrosesan data |
| Database | MySQL | Penyimpanan data aplikasi |
| Database Access | PDO | Koneksi dan eksekusi query database |
| Visualisasi | Chart.js | Grafik statistik |
| Autentikasi | PHP Session | Pengelolaan sesi pengguna |
| Password Security | password_hash() dan password_verify() | Hash dan verifikasi password |
| Web Server | Apache | Menjalankan aplikasi PHP |
| Lingkungan Lokal | XAMPP | Menyediakan Apache dan MySQL/MariaDB |

## 5. Arsitektur Aplikasi

Career & Alumni Portal menggunakan arsitektur aplikasi web dengan tiga bagian utama.

### Frontend

Frontend merupakan bagian yang berinteraksi langsung dengan pengguna. Bagian ini menggunakan HTML5, CSS3, JavaScript, dan Bootstrap 5 untuk menampilkan landing page, formulir, dashboard, tabel data, pencarian lowongan, dan grafik statistik.

### Backend

Backend dikembangkan menggunakan PHP Native tanpa framework. Backend menangani autentikasi, pengelolaan session, validasi input, pembatasan hak akses, operasi CRUD, pemrosesan lamaran, pengelolaan berita, dan interaksi dengan database.

Folder `api/` juga menyediakan endpoint tertentu yang menghasilkan data dalam format JSON, termasuk data lowongan, statistik, dan akses CV yang dilindungi.

### Database

Database menggunakan MySQL untuk menyimpan akun pengguna, profil alumni, profil perusahaan, kategori pekerjaan, lowongan, lamaran, tracer study, dan berita.

### Penyimpanan File

File yang diunggah pengguna disimpan melalui direktori `uploads/`, sesuai implementasi yang tersedia. File tersebut mencakup foto profil, logo perusahaan, CV, dan gambar berita.

**Catatan:** frontend dan backend masih berada dalam satu project PHP. Aplikasi ini belum menggunakan frontend React dan backend Node.js sebagai dua aplikasi terpisah.

## 6. Struktur Folder

```text
career-alumni-portal/
├── admin/
│   ├── alumni/          # Pengelolaan dan verifikasi alumni
│   ├── companies/       # Pengelolaan perusahaan
│   ├── jobs/            # Pengelolaan lowongan
│   ├── applications/    # Pengelolaan lamaran
│   ├── tracer/          # Statistik dan ekspor tracer study
│   └── news/             # Pengelolaan berita
├── alumni/               # Dashboard dan fitur alumni
├── company/              # Dashboard dan fitur perusahaan
├── auth/                 # Login, registrasi, dan logout
├── api/                  # Endpoint JSON dan akses CV
├── assets/
│   ├── css/              # Stylesheet
│   ├── js/               # JavaScript frontend
│   └── images/           # Aset gambar
├── components/           # Komponen antarmuka yang digunakan ulang
├── config/               # Database, autentikasi, helper, dan logika aplikasi
├── uploads/
│   ├── profile/          # Foto profil
│   ├── company/          # Logo perusahaan
│   ├── cv/               # CV pelamar
│   └── news/             # Gambar berita
├── database/
│   └── career_alumni.sql # Struktur database dan data contoh
├── index.php             # Landing page
├── news.php              # Daftar berita publik
├── news-detail.php       # Detail berita publik
├── SECURITY.md           # Dokumentasi keamanan
├── TESTING.md            # Checklist pengujian
└── README.md             # Dokumentasi project
```

Struktur di atas merupakan gambaran direktori utama. File tambahan atau file dengan nama serupa yang tersedia di root project perlu diperiksa sebelum melakukan perubahan atau refactoring.

## 7. Struktur Database

Database aplikasi menggunakan nama `career_alumni` dan mendefinisikan delapan tabel utama.

| No. | Tabel | Deskripsi |
|---|---|---|
| 1 | `users` | Menyimpan akun, email, hash password, dan role pengguna. |
| 2 | `alumni` | Menyimpan profil alumni, informasi akademik, dan data karier. |
| 3 | `companies` | Menyimpan profil dan informasi perusahaan. |
| 4 | `job_categories` | Menyimpan kategori lowongan pekerjaan. |
| 5 | `jobs` | Menyimpan informasi lowongan dan perusahaan pemilik. |
| 6 | `job_applications` | Menyimpan data lamaran, dokumen, dan status lamaran. |
| 7 | `tracer_studies` | Menyimpan informasi karier alumni setelah lulus. |
| 8 | `news` | Menyimpan artikel dan informasi kampus. |

Relasi antar tabel digunakan untuk menjaga keterhubungan data. Sebagai contoh, lowongan terhubung dengan perusahaan, sedangkan lamaran menghubungkan alumni dengan lowongan yang dilamar.

Database juga menggunakan batasan seperti foreign key dan indeks atau constraint tertentu untuk membantu menjaga konsistensi data.

File SQL tersedia di:

`database/career_alumni.sql`

## 8. Persyaratan Sistem

Sebelum menjalankan aplikasi, pastikan perangkat memiliki:

- Sistem operasi yang mendukung XAMPP.
- XAMPP dengan Apache dan MySQL/MariaDB.
- PHP yang sesuai dengan kebutuhan kode aplikasi.
- Browser modern, seperti Google Chrome, Microsoft Edge, atau Mozilla Firefox.
- phpMyAdmin untuk mempermudah pengelolaan database.
- Koneksi internet untuk memuat aset eksternal melalui CDN, seperti Bootstrap, Bootstrap Icons, font, dan Chart.js.

## 9. Cara Instalasi dan Menjalankan Aplikasi

### 9.1 Instal XAMPP

1. Unduh XAMPP melalui https://www.apachefriends.org/.
2. Jalankan installer dan ikuti petunjuk instalasi.
3. Buka XAMPP Control Panel.
4. Jalankan Apache dan MySQL/MariaDB.

Pastikan kedua layanan berjalan tanpa error.

### 9.2 Salin Folder Project

Ekstrak project ke direktori `htdocs`.

Contoh lokasi:

```text
C:\xampp\htdocs\career-alumni-portal\
```

Pastikan file `index.php`, folder `config/`, `database/`, dan direktori utama lainnya berada di dalam folder project tersebut.

Jika nama folder hasil ekstraksi adalah `career-alumni-portal-main`, URL localhost harus disesuaikan dengan nama tersebut atau folder dapat diganti namanya.

### 9.3 Import Database

1. Buka browser.
2. Akses http://localhost/phpmyadmin.
3. Pilih menu **Import**.
4. Klik **Choose File**.
5. Pilih file `database/career_alumni.sql`.
6. Jalankan proses impor.
7. Pastikan database `career_alumni` dan tabel-tabelnya berhasil dibuat.

Jika file SQL telah menyediakan perintah pembuatan database, database tidak perlu dibuat secara manual terlebih dahulu.

**Penting:** periksa isi file SQL sebelum melakukan impor ulang. Jika terdapat perintah penghapusan tabel, impor ulang dapat menghapus data lama pada tabel yang terdampak.

### 9.4 Konfigurasi Database

Buka file:

`config/database.php`

Sesuaikan pengaturan database dengan konfigurasi MySQL lokal.

Contoh konfigurasi standar XAMPP:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'career_alumni');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Pastikan konfigurasi tersebut sesuai dengan definisi yang ada di file project dan jangan mendefinisikan konstanta yang sama dua kali.

Jika MySQL menggunakan password, isi `DB_PASS` dengan password yang benar.

Apabila aplikasi menyediakan opsi `APP_DEBUG`, aktifkan hanya untuk membantu proses debugging lokal. Nonaktifkan kembali sebelum aplikasi digunakan pada lingkungan publik.

### 9.5 Jalankan Aplikasi

Setelah server berjalan dan database berhasil diimpor, buka:

```text
http://localhost/career-alumni-portal/
```

Jika nama folder project berbeda, sesuaikan URL dengan nama folder yang berada di dalam `htdocs`.

## 10. Akun Demo

Berikut akun demo yang tercantum dalam dokumentasi project.

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `Admin123!` |
| Alumni | `alumni@example.com` | `Alumni123!` |
| Perusahaan | `company@example.com` | `Company123!` |

Database juga dapat berisi akun alumni dan perusahaan tambahan. Informasi akun tersebut dapat diperiksa pada file SQL.

Akun demo hanya ditujukan untuk pengujian lokal. Pastikan akun dan hash password yang sesuai benar-benar tersedia setelah database diimpor.

## 11. Pembagian Hak Akses

### Admin

Admin bertanggung jawab mengelola data alumni, perusahaan, lowongan, lamaran, tracer study, dan berita. Halaman administratif harus dibatasi untuk pengguna dengan role Admin.

### Alumni

Alumni hanya boleh mengelola profil dan lamaran miliknya sendiri serta mengisi tracer study. Alumni tidak boleh mengakses dashboard Admin atau mengubah data milik pengguna lain tanpa kewenangan.

### Perusahaan

Perusahaan hanya boleh mengelola profil dan lowongan miliknya sendiri serta melihat dan memproses pelamar yang terkait dengan lowongannya.

Pemeriksaan kepemilikan data perlu dilakukan pada backend menggunakan identitas perusahaan yang terautentikasi, bukan sekadar mempercayai `company_id` dari input pengguna.

Rincian mekanisme keamanan dapat dilihat pada `SECURITY.md`.

## 12. Keamanan Aplikasi

Project menyediakan dokumentasi keamanan dan kode pendukung untuk beberapa mekanisme berikut:

- **Password hashing:** menggunakan `password_hash()` dan `password_verify()`.
- **Session management:** mengelola status autentikasi pengguna melalui PHP Session.
- **Role-based access control:** membatasi halaman dan tindakan berdasarkan role.
- **Prepared statements:** menggunakan PDO untuk membantu mencegah SQL injection.
- **CSRF protection:** menggunakan token pada operasi yang relevan.
- **Output escaping:** membantu mencegah Cross-Site Scripting (XSS).
- **Upload validation:** memvalidasi file yang diunggah sesuai aturan aplikasi.
- **File access control:** membatasi akses terhadap dokumen CV berdasarkan kewenangan.
- **Ownership verification:** memeriksa kepemilikan data sebelum melakukan operasi tertentu.
- **Upload directory protection:** menyediakan konfigurasi untuk membatasi eksekusi file di direktori upload.

Keamanan harus tetap diverifikasi melalui pengujian. Dokumentasi dan keberadaan fungsi keamanan tidak otomatis membuktikan seluruh mekanisme telah bekerja dengan benar.

## 13. Pengujian Aplikasi

Checklist pengujian manual tersedia dalam file `TESTING.md`.

Pengujian yang disarankan meliputi:

1. Registrasi akun, login, logout, dan password yang tidak valid.
2. Pembatasan akses halaman berdasarkan role.
3. Pembuatan, pengubahan, dan penghapusan data.
4. Verifikasi data alumni oleh Admin.
5. Pengelolaan profil perusahaan.
6. Pembuatan, pengubahan, dan penghapusan lowongan.
7. Pencarian serta pemfilteran lowongan.
8. Pengiriman lamaran dan validasi file CV.
9. Pemantauan serta perubahan status lamaran.
10. Pengisian dan pembaruan tracer study.
11. Statistik dan ekspor tracer study ke CSV.
12. Pembuatan, pengeditan, dan publikasi berita.
13. Pengujian CSRF, XSS, SQL injection, dan upload file tidak valid.
14. Pengujian akses CV dan kepemilikan data.
15. Pengujian tampilan pada berbagai ukuran layar.

Catat hasil setiap pengujian sebagai berhasil, gagal, atau belum diuji. Lakukan perbaikan jika ditemukan error sebelum aplikasi digunakan untuk presentasi atau demonstrasi.

## 14. Screenshot Aplikasi

Tambahkan screenshot aktual setelah aplikasi berhasil dijalankan.

Screenshot yang disarankan:

- Landing page publik.
- Dashboard Admin beserta grafik.
- Halaman pengelolaan dan verifikasi alumni.
- Dashboard Alumni.
- Halaman pencarian dan detail lowongan.
- Formulir pengiriman lamaran.
- Dashboard Perusahaan.
- Halaman daftar pelamar.
- Halaman tracer study.
- Dashboard statistik tracer study.
- Halaman pengelolaan berita.

Screenshot dapat disimpan dalam folder `assets/images/` atau direktori dokumentasi khusus, kemudian ditampilkan menggunakan Markdown.

Contoh:

```markdown
![Landing Page](assets/images/landing-page.png)
```

Pastikan file gambar benar-benar tersedia pada path yang digunakan.

## 15. Penyimpanan File dan Pengembangan Selanjutnya

Penyimpanan lokal melalui folder `uploads/` merupakan pendekatan yang digunakan untuk instalasi standar, selama konfigurasi dan izin folder telah sesuai.

Integrasi AWS S3 dapat dipertimbangkan sebagai pengembangan tambahan. Sebelum digunakan, pastikan konfigurasi storage, dependensi Composer/AWS SDK, pengelolaan kredensial, dan proses upload-download-delete sudah tersedia serta diuji.

Pengembangan selanjutnya dapat mencakup:

- Penyempurnaan validasi dan penanganan error.
- Pengujian otomatis untuk fitur penting.
- Perapian file duplikat atau versi alternatif.
- Penyempurnaan dokumentasi endpoint API.
- Peningkatan keamanan dan pengelolaan file.
- Pengembangan notifikasi lowongan dan lamaran.
- Pemisahan frontend dan backend apabila dibutuhkan.

## 16. Kontribusi dan Pemeliharaan

Perubahan pada kode sebaiknya dilakukan secara bertahap. Sebelum perubahan digabungkan ke project utama, jalankan pengujian yang relevan dan perbarui dokumentasi jika terdapat perubahan struktur folder, konfigurasi, database, atau perilaku aplikasi.

Jangan menghapus file yang tampak duplikat sebelum memastikan bahwa file tersebut tidak digunakan oleh halaman lain.

## 17. Lisensi

Tambahkan informasi lisensi sesuai ketentuan yang dipilih oleh pemilik project.

## 18. Penulis dan Konteks Akademik

Project ini dikembangkan sebagai tugas mata kuliah **Pemrograman Web Lanjut** dengan fokus pada pengembangan aplikasi web full-stack, integrasi database relasional, autentikasi dan otorisasi pengguna, pengolahan data, serta penerapan dasar keamanan aplikasi web.

---

**Career & Alumni Portal** — Mendukung pengelolaan alumni, pengembangan karier, dan layanan career center kampus melalui aplikasi web yang terintegrasi.

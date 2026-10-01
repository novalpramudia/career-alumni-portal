-- =====================================================
-- Career & Alumni Portal - Database
-- Import lewat phpMyAdmin (tab Import) atau MySQL CLI
-- =====================================================
CREATE DATABASE IF NOT EXISTS career_alumni
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE career_alumni;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS news, tracer_studies, job_applications, jobs, job_categories, companies, alumni, users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS (akun login untuk semua role)
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','alumni','company') NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role)
) ENGINE=InnoDB;

-- 2. ALUMNI (profil, 1 user = 1 alumni)
CREATE TABLE alumni (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  full_name VARCHAR(150) NOT NULL,
  nim VARCHAR(30) NULL UNIQUE,
  phone VARCHAR(20) NULL,
  graduation_year YEAR NULL,
  study_program VARCHAR(100) NULL,
  faculty VARCHAR(100) NULL,
  address TEXT NULL,
  photo VARCHAR(255) NULL,
  employment_status ENUM('bekerja','wirausaha','studi_lanjut','mencari_kerja') NULL,
  company_name VARCHAR(150) NULL,
  position VARCHAR(100) NULL,
  linkedin VARCHAR(255) NULL,
  bio TEXT NULL,
  verification_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_alumni_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_alumni_year (graduation_year),
  INDEX idx_alumni_verif (verification_status)
) ENGINE=InnoDB;

-- 3. COMPANIES (profil perusahaan, 1 user = 1 perusahaan)
CREATE TABLE companies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NULL,
  phone VARCHAR(20) NULL,
  address TEXT NULL,
  website VARCHAR(255) NULL,
  industry VARCHAR(100) NULL,
  description TEXT NULL,
  logo VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_company_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. JOB_CATEGORIES
CREATE TABLE job_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5. JOBS
CREATE TABLE jobs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  requirements TEXT NOT NULL,
  location VARCHAR(100) NOT NULL,
  job_type ENUM('full-time','part-time','internship','contract') NOT NULL,
  salary VARCHAR(100) NULL,
  deadline DATE NOT NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_jobs_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
  CONSTRAINT fk_jobs_category FOREIGN KEY (category_id) REFERENCES job_categories(id) ON DELETE RESTRICT,
  INDEX idx_jobs_location (location),
  INDEX idx_jobs_type (job_type),
  INDEX idx_jobs_status_deadline (status, deadline)
) ENGINE=InnoDB;

-- 6. JOB_APPLICATIONS
CREATE TABLE job_applications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id INT UNSIGNED NOT NULL,
  alumni_id INT UNSIGNED NOT NULL,
  cv_file VARCHAR(255) NOT NULL,
  cover_letter TEXT NULL,
  status ENUM('pending','reviewed','accepted','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_app_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
  CONSTRAINT fk_app_alumni FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
  UNIQUE KEY uq_app_job_alumni (job_id, alumni_id),
  INDEX idx_app_status (status)
) ENGINE=InnoDB;

-- 7. TRACER_STUDIES (1 alumni = 1 isian, bisa diperbarui)
CREATE TABLE tracer_studies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  alumni_id INT UNSIGNED NOT NULL,
  graduation_year YEAR NOT NULL,
  employment_status ENUM('bekerja','wirausaha','studi_lanjut','mencari_kerja') NOT NULL,
  company_name VARCHAR(150) NULL,
  position VARCHAR(100) NULL,
  job_field VARCHAR(100) NULL,
  waiting_time ENUM('sebelum_lulus','0-3_bulan','3-6_bulan','6-12_bulan','lebih_12_bulan') NULL,
  salary_range ENUM('<3jt','3-5jt','5-8jt','8-12jt','>12jt') NULL,
  job_relevance ENUM('sangat_sesuai','sesuai','kurang_sesuai','tidak_sesuai') NULL,
  feedback TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tracer_alumni FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
  UNIQUE KEY uq_tracer_alumni (alumni_id),
  INDEX idx_tracer_status (employment_status)
) ENGINE=InnoDB;

-- 8. NEWS
CREATE TABLE news (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  author_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  content TEXT NOT NULL,
  image VARCHAR(255) NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_news_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_news_status (status, created_at)
) ENGINE=InnoDB;

-- =====================================================
-- DATA DUMMY
-- Password: Admin123! / Alumni123! / Company123! (bcrypt)
-- Semua akun alumni memakai Alumni123!, semua perusahaan Company123!
-- =====================================================
INSERT INTO users (id, email, password_hash, role) VALUES
(1, 'admin@example.com',   '$2b$12$LZAzYcxNm3xjr.cI.n1eSeV.ULNjwo/cvqoXf5bs4i2BeliFPh2Ji', 'admin'),
(2, 'alumni@example.com',  '$2b$12$fE2EcyXKMsTJUK/y8bP13OpOeHZ2Z3LOLS9y.n/N0L7vPTO9eR2.6', 'alumni'),
(3, 'rina@example.com',    '$2b$12$fE2EcyXKMsTJUK/y8bP13OpOeHZ2Z3LOLS9y.n/N0L7vPTO9eR2.6', 'alumni'),
(4, 'bagas@example.com',   '$2b$12$fE2EcyXKMsTJUK/y8bP13OpOeHZ2Z3LOLS9y.n/N0L7vPTO9eR2.6', 'alumni'),
(5, 'sinta@example.com',   '$2b$12$fE2EcyXKMsTJUK/y8bP13OpOeHZ2Z3LOLS9y.n/N0L7vPTO9eR2.6', 'alumni'),
(6, 'dimas@example.com',   '$2b$12$fE2EcyXKMsTJUK/y8bP13OpOeHZ2Z3LOLS9y.n/N0L7vPTO9eR2.6', 'alumni'),
(7, 'company@example.com', '$2b$12$3XDD0sV3FIskX7dMnJIeR.DemcJFq8H4lFmutAOZPU7zK4Mz82/mW', 'company'),
(8, 'hrd@mitrafinansia.example.com', '$2b$12$3XDD0sV3FIskX7dMnJIeR.DemcJFq8H4lFmutAOZPU7zK4Mz82/mW', 'company'),
(9, 'hr@kreasimedia.example.com',    '$2b$12$3XDD0sV3FIskX7dMnJIeR.DemcJFq8H4lFmutAOZPU7zK4Mz82/mW', 'company');

INSERT INTO alumni (id, user_id, full_name, nim, phone, graduation_year, study_program, faculty, address, employment_status, company_name, position, linkedin, bio, verification_status) VALUES
(1, 2, 'Ahmad Fauzi',   '2018101001', '081234567801', 2022, 'Teknik Informatika', 'Fakultas Teknik', 'Jl. Merdeka No. 10, Bandung', 'mencari_kerja', NULL, NULL, 'https://linkedin.com/in/ahmadfauzi', 'Lulusan Teknik Informatika yang tertarik pada pengembangan web.', 'verified'),
(2, 3, 'Rina Wulandari','2017101002', '081234567802', 2021, 'Sistem Informasi', 'Fakultas Teknik', 'Jl. Melati No. 5, Jakarta', 'bekerja', 'PT Nusantara Teknologi', 'Business Analyst', 'https://linkedin.com/in/rinawulan', 'Business analyst dengan minat pada data.', 'verified'),
(3, 4, 'Bagas Pratama', '2016102003', '081234567803', 2020, 'Akuntansi', 'Fakultas Ekonomi', 'Jl. Kenanga No. 7, Surabaya', 'bekerja', 'Mitra Finansia', 'Staff Akuntansi', NULL, NULL, 'verified'),
(4, 5, 'Sinta Maharani','2019102004', '081234567804', 2023, 'Manajemen', 'Fakultas Ekonomi', 'Jl. Anggrek No. 3, Yogyakarta', 'mencari_kerja', NULL, NULL, NULL, NULL, 'pending'),
(5, 6, 'Dimas Saputra', '2018101005', '081234567805', 2022, 'Desain Komunikasi Visual', 'Fakultas Seni', 'Jl. Dahlia No. 12, Semarang', 'wirausaha', 'Studio Dimas', 'Founder', NULL, 'Freelance desainer grafis.', 'verified');

INSERT INTO companies (id, user_id, name, email, phone, address, website, industry, description) VALUES
(1, 7, 'PT Nusantara Teknologi', 'company@example.com', '021-5550101', 'Jl. Sudirman Kav. 1, Jakarta', 'https://nusantaratek.example.com', 'Teknologi Informasi', 'Perusahaan pengembang perangkat lunak dan layanan cloud.'),
(2, 8, 'Mitra Finansia', 'hrd@mitrafinansia.example.com', '022-5550202', 'Jl. Asia Afrika No. 20, Bandung', 'https://mitrafinansia.example.com', 'Keuangan', 'Perusahaan jasa keuangan dan konsultasi bisnis.'),
(3, 9, 'Kreasi Media Digital', 'hr@kreasimedia.example.com', '024-5550303', 'Jl. Pemuda No. 8, Semarang', 'https://kreasimedia.example.com', 'Media & Kreatif', 'Agensi kreatif dan pemasaran digital.');

INSERT INTO job_categories (id, name) VALUES
(1, 'Teknologi Informasi'), (2, 'Keuangan & Akuntansi'), (3, 'Pemasaran'),
(4, 'Pendidikan'), (5, 'Teknik'), (6, 'Administrasi & Umum');

INSERT INTO jobs (id, company_id, category_id, title, description, requirements, location, job_type, salary, deadline, status) VALUES
(1, 1, 1, 'Web Developer', 'Mengembangkan dan memelihara aplikasi web internal dan klien.', '- S1 Informatika/Sistem Informasi\n- Menguasai PHP & MySQL\n- Paham HTML, CSS, JavaScript', 'Jakarta', 'full-time', 'Rp 6.000.000 - 9.000.000', '2026-11-30', 'open'),
(2, 1, 1, 'Backend Developer Intern', 'Program magang 6 bulan pada tim backend.', '- Mahasiswa tingkat akhir/fresh graduate\n- Dasar PHP atau Node.js', 'Jakarta', 'internship', 'Rp 2.500.000', '2026-10-31', 'open'),
(3, 1, 1, 'UI/UX Designer', 'Merancang antarmuka aplikasi web dan mobile.', '- Menguasai Figma\n- Portofolio desain', 'Bandung', 'contract', 'Rp 5.000.000 - 7.000.000', '2026-12-15', 'open'),
(4, 2, 2, 'Staff Akuntansi', 'Menyusun laporan keuangan bulanan dan rekonsiliasi.', '- S1 Akuntansi\n- Menguasai Excel', 'Bandung', 'full-time', 'Rp 5.000.000 - 6.500.000', '2026-11-20', 'open'),
(5, 2, 6, 'Admin Operasional', 'Mengelola dokumen dan administrasi kantor.', '- Minimal D3\n- Teliti dan komunikatif', 'Bandung', 'part-time', 'Rp 2.500.000', '2026-10-15', 'open'),
(6, 3, 3, 'Digital Marketing Specialist', 'Mengelola kampanye iklan digital dan media sosial.', '- S1 Manajemen/Komunikasi\n- Paham Meta Ads & Google Ads', 'Semarang', 'full-time', 'Rp 4.500.000 - 7.000.000', '2026-11-25', 'open'),
(7, 3, 3, 'Graphic Designer', 'Membuat materi visual untuk klien agensi.', '- Menguasai Adobe Illustrator/Photoshop', 'Semarang', 'contract', 'Rp 4.000.000 - 6.000.000', '2026-12-01', 'open'),
(8, 3, 6, 'Content Admin (Ditutup)', 'Lowongan contoh yang sudah ditutup.', '- Fresh graduate', 'Semarang', 'part-time', 'Rp 2.000.000', '2026-08-31', 'closed');

INSERT INTO job_applications (job_id, alumni_id, cv_file, cover_letter, status) VALUES
(1, 1, 'dummy_cv.pdf', 'Saya tertarik pada posisi Web Developer dan siap belajar cepat.', 'pending'),
(2, 1, 'dummy_cv.pdf', 'Saya ingin memulai karier sebagai backend developer.', 'reviewed'),
(3, 1, 'dummy_cv.pdf', 'Saya memiliki minat pada desain antarmuka.', 'rejected'),
(4, 4, 'dummy_cv.pdf', 'Saya lulusan Manajemen dengan minat pada keuangan.', 'pending'),
(6, 4, 'dummy_cv.pdf', 'Saya berpengalaman mengelola akun media sosial organisasi.', 'accepted'),
(7, 5, 'dummy_cv.pdf', 'Portofolio desain saya terlampir.', 'reviewed'),
(1, 2, 'dummy_cv.pdf', 'Saya memiliki pengalaman analisis sistem dan pemrograman.', 'pending'),
(5, 3, 'dummy_cv.pdf', 'Saya berpengalaman di administrasi keuangan.', 'accepted');

INSERT INTO tracer_studies (alumni_id, graduation_year, employment_status, company_name, position, job_field, waiting_time, salary_range, job_relevance, feedback) VALUES
(2, 2021, 'bekerja', 'PT Nusantara Teknologi', 'Business Analyst', 'Teknologi Informasi', '0-3_bulan', '5-8jt', 'sesuai', 'Perbanyak mata kuliah praktik dan kerja sama industri.'),
(3, 2020, 'bekerja', 'Mitra Finansia', 'Staff Akuntansi', 'Keuangan', '3-6_bulan', '3-5jt', 'sangat_sesuai', 'Sertifikasi profesi perlu difasilitasi kampus.'),
(5, 2022, 'wirausaha', 'Studio Dimas', 'Founder', 'Desain Kreatif', 'sebelum_lulus', '3-5jt', 'sesuai', 'Tambah kelas kewirausahaan.'),
(1, 2022, 'mencari_kerja', NULL, NULL, NULL, NULL, NULL, NULL, 'Career center perlu lebih sering mengadakan job fair.');

INSERT INTO news (author_id, title, slug, content, status) VALUES
(1, 'Job Fair Kampus 2026 Resmi Dibuka', 'job-fair-kampus-2026', 'Career center menggelar job fair yang diikuti puluhan perusahaan mitra. Alumni dapat mendaftar melalui portal ini.', 'published'),
(1, 'Tips Menulis CV yang Menarik Perekrut', 'tips-menulis-cv', 'Gunakan format ringkas satu halaman, tonjolkan proyek dan pencapaian, serta sesuaikan CV dengan posisi yang dilamar.', 'published'),
(1, 'Workshop Persiapan Wawancara Kerja', 'workshop-wawancara-kerja', 'Career center mengadakan workshop simulasi wawancara bersama praktisi HRD untuk seluruh alumni.', 'published'),
(1, 'Hasil Tracer Study Tahun Lalu Dirilis', 'hasil-tracer-study', 'Mayoritas alumni mendapatkan pekerjaan dalam enam bulan setelah lulus. Terima kasih atas partisipasi Anda.', 'published');

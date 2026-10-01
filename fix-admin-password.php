<?php
/**
 * CARA PAKAI:
 * 1. Salin file ini ke C:\xampp\htdocs\career-alumni-portal\fix-admin-password.php
 * 2. Buka http://localhost/career-alumni-portal/fix-admin-password.php di browser.
 * 3. Salin (copy) kode hash yang muncul.
 * 4. Buka phpMyAdmin > database career_alumni > tabel users > cari baris admin@example.com > klik Edit.
 * 5. Tempel (paste) kode hash tadi ke kolom password_hash, lalu klik Go/Simpan.
 * 6. Coba login lagi dengan email admin@example.com dan password Admin123!
 * 7. PENTING: setelah berhasil, HAPUS file fix-admin-password.php ini dari folder project
 *    (file ini tidak boleh ada saat dikumpulkan/dipresentasikan, karena membocorkan cara reset password).
 */

$password = 'Admin123!';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo '<h2>Hash baru untuk password: ' . htmlspecialchars($password) . '</h2>';
echo '<p>Salin seluruh teks di bawah ini (klik lalu Ctrl+A, Ctrl+C):</p>';
echo '<textarea style="width:100%;height:80px;font-size:16px;" onclick="this.select()">' . htmlspecialchars($hash) . '</textarea>';

echo '<hr><h3>Cek otomatis: apakah hash ini cocok?</h3>';
echo password_verify($password, $hash) ? '<p style="color:green">✔ Cocok (seharusnya begitu).</p>' : '<p style="color:red">✘ Tidak cocok — ini aneh, hubungi saya.</p>';

echo '<hr><p style="color:red"><b>Jangan lupa hapus file ini setelah selesai dipakai.</b></p>';

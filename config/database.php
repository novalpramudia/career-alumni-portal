<?php
/**
 * Koneksi database (PDO).
 * Sesuaikan konstanta di bawah jika pengaturan MySQL XAMPP Anda berbeda.
 */

// Ganti ke true HANYA saat development untuk melihat pesan error PHP secara lengkap di browser.
// Di lingkungan produksi/presentasi, harus tetap false agar detail teknis (path server, query, dsb)
// tidak terlihat oleh pengunjung — cukup dicatat ke log server.
define('APP_DEBUG', false);
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

define('DB_HOST', 'localhost');
define('DB_NAME', 'career_alumni');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default: kosong
define('DB_CHARSET', 'utf8mb4');

/**
 * Mengembalikan satu koneksi PDO yang dipakai ulang di seluruh request.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // prepared statement asli
            ]);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Koneksi database gagal. Pastikan MySQL di XAMPP sudah berjalan, database sudah diimport, dan config/database.php sudah benar.');
        }
    }

    return $pdo;
}

/* ---------- Helper query singkat (selalu pakai prepared statement) ---------- */

/** Mengambil semua baris hasil query. */
function db_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Mengambil satu baris (atau NULL). */
function db_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/** Mengambil satu nilai (misal COUNT). */
function db_scalar(string $sql, array $params = [])
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

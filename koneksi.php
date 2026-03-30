<?php
/**
 * TOKO FHIKA - Database Connection
 * File: koneksi.php
 * Deskripsi: Konfigurasi dan koneksi ke database MySQL menggunakan MySQLi
 */

// ============================================================
// KONFIGURASI DATABASE
// Sesuaikan nilai berikut dengan konfigurasi server Anda
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Username MySQL Anda
define('DB_PASS', '');            // Password MySQL Anda
define('DB_NAME', 'toko_fhika');
define('DB_PORT', 3306);

// ============================================================
// KONFIGURASI APLIKASI
// ============================================================
define('APP_NAME', 'TOKO FHIKA');
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', false);     // Set ke true saat development untuk melihat error detail

// Auto-detect BASE_URL
$_script_dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

// Jika sedang berada di dalam folder admin, naik satu tingkat
if (defined('IS_ADMIN') || strpos($_script_dir, '/admin') !== false) {
    // Potong '/admin' dari akhir path
    if (substr($_script_dir, -6) === '/admin') {
        $_script_dir = substr($_script_dir, 0, -6);
    }
}

// Encode karakter '#' menjadi '%23' agar aman di URL (browser HTTP Redirect)
$_script_dir_encoded = str_replace('#', '%23', $_script_dir);

$_scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$_host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

$_base_url = $_scheme . '://' . $_host . $_script_dir_encoded . '/';

define('BASE_URL', $_base_url);
define('PROJECT_ROOT', __DIR__);  // Selalu menunjuk ke folder root project (lokasi koneksi.php)
define('UPLOAD_DIR', __DIR__ . '/assets/uploads/');
define('UPLOAD_URL', BASE_URL . 'assets/uploads/');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// ============================================================
// BUAT KONEKSI DATABASE
// ============================================================
$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Cek jika koneksi gagal
if ($koneksi->connect_error) {
    // Di production, jangan tampilkan detail error
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die('<div style="color:red;padding:20px;font-family:sans-serif;">
            <h3>❌ Koneksi Database Gagal</h3>
            <p><strong>Error:</strong> ' . htmlspecialchars($koneksi->connect_error) . '</p>
            <p>Pastikan MySQL berjalan dan konfigurasi pada <code>koneksi.php</code> sudah benar.</p>
        </div>');
    } else {
        die('<div style="color:red;padding:20px;font-family:sans-serif;">
            <h3>Server sedang mengalami gangguan. Silakan coba beberapa saat lagi.</h3>
        </div>');
    }
}

// Set charset UTF-8 untuk mendukung karakter Indonesia
$koneksi->set_charset('utf8mb4');

// ============================================================
// FUNGSI-FUNGSI HELPER GLOBAL
// ============================================================

/**
 * Format angka menjadi format Rupiah
 * @param float $angka
 * @return string
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Sanitasi input untuk mencegah SQL Injection & XSS
 * @param string $input
 * @return string
 */
function bersihkan($input) {
    global $koneksi;
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $koneksi->real_escape_string($input);
}

/**
 * Redirect ke URL lain
 * @param string $url
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Cek apakah user sudah login
 */
function cekLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect(BASE_URL . 'login.php');
    }
}

/**
 * Cek apakah user adalah admin
 */
function cekAdmin() {
    cekLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        redirect(BASE_URL . 'index.php?error=akses_ditolak');
    }
}

/**
 * Generate kode transaksi unik
 * Format: TRX-YYYYMMDD-XXXXX
 */
function generateKodeTransaksi() {
    return 'TRX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

/**
 * Buat slug dari string
 */
function buatSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/\s+/', '-', $text);
    $text = preg_replace('/[^a-z0-9\-]/', '', $text);
    return $text;
}

// ============================================================
// SESSION START (hanya jika belum dimulai)
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

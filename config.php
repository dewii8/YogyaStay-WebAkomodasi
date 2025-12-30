<?php
// config.php - Konfigurasi Database
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_yogyastay');

// Koneksi ke database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Base URL
define('BASE_URL', 'http://localhost/yogyastay/');

// Session timeout (30 menit)
define('SESSION_TIMEOUT', 1800);
?>
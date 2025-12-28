<?php
require_once '../config.php';

// AKTIFKAN ERROR REPORTING UNTUK DEBUG
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log semua POST data
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - POST DATA: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Anda harus login terlebih dahulu!";
    header("Location: ../autentikasi/login.php");
    exit;
}

// Cek apakah ini POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Method tidak valid!";
    header("Location: riwayat_reservasi.php");
    exit;
}

// Cek apakah ada submit_pembatalan
if (!isset($_POST['submit_pembatalan'])) {
    $_SESSION['error_message'] = "Request tidak valid!";
    header("Location: riwayat_reservasi.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$id_booking = isset($_POST['id_booking']) ? intval($_POST['id_booking']) : 0;
$alasan_pembatalan = isset($_POST['alasan_pembatalan']) ? trim($_POST['alasan_pembatalan']) : '';

// Log data yang diterima
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - USER ID: $user_id, BOOKING ID: $id_booking, ALASAN: $alasan_pembatalan\n", FILE_APPEND);

// Validasi
if ($id_booking <= 0) {
    $_SESSION['error_message'] = "ID Booking tidak valid!";
    header("Location: riwayat_reservasi.php");
    exit;
}

if (empty($alasan_pembatalan)) {
    $_SESSION['error_message'] = "Alasan pembatalan harus diisi!";
    header("Location: proses_pembatalan.php?id=" . $id_booking);
    exit;
}

if (strlen($alasan_pembatalan) < 20) {
    $_SESSION['error_message'] = "Alasan pembatalan minimal 20 karakter! (Saat ini: " . strlen($alasan_pembatalan) . " karakter)";
    header("Location: proses_pembatalan.php?id=" . $id_booking);
    exit;
}

// Validasi booking milik user
$check_booking = "SELECT b.*, p.nama_penginapan 
                  FROM booking b
                  LEFT JOIN penginapan p ON b.id_penginapan = p.id_penginapan
                  WHERE b.id_booking = $id_booking 
                  AND b.id_user = $user_id
                  AND b.status_reservasi IN ('confirmed', 'check-in')";

$result_check = mysqli_query($conn, $check_booking);

if (!$result_check || mysqli_num_rows($result_check) == 0) {
    $_SESSION['error_message'] = "Booking tidak ditemukan atau tidak dapat dibatalkan!";
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - ERROR: Booking validation failed\n", FILE_APPEND);
    header("Location: riwayat_reservasi.php");
    exit;
}

$booking_data = mysqli_fetch_assoc($result_check);

// Escape alasan pembatalan
$alasan_escaped = mysqli_real_escape_string($conn, $alasan_pembatalan);

// Log sebelum transaction
file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - Starting transaction...\n", FILE_APPEND);

// MULAI TRANSACTION
mysqli_begin_transaction($conn);

try {
    // 1. INSERT ke tabel pembatalan
    $sql_insert = "INSERT INTO pembatalan (
                        id_booking,
                        alasan_pembatalan,
                        status_pembatalan,
                        tanggal_pengajuan
                    ) VALUES (
                        $id_booking,
                        '$alasan_escaped',
                        'diproses',
                        NOW()
                    )";
    
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - SQL INSERT: $sql_insert\n", FILE_APPEND);
    
    if (!mysqli_query($conn, $sql_insert)) {
        throw new Exception("Error INSERT pembatalan: " . mysqli_error($conn));
    }
    
    $id_pembatalan = mysqli_insert_id($conn);
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - INSERT SUCCESS, ID: $id_pembatalan\n", FILE_APPEND);
    
    // 2. UPDATE status booking
    $sql_update = "UPDATE booking 
                   SET status_reservasi = 'dibatalkan' 
                   WHERE id_booking = $id_booking";
    
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - SQL UPDATE: $sql_update\n", FILE_APPEND);
    
    if (!mysqli_query($conn, $sql_update)) {
        throw new Exception("Error UPDATE booking: " . mysqli_error($conn));
    }
    
    $affected = mysqli_affected_rows($conn);
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - UPDATE SUCCESS, Affected rows: $affected\n", FILE_APPEND);
    
    // COMMIT TRANSACTION
    mysqli_commit($conn);
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - TRANSACTION COMMITTED!\n", FILE_APPEND);
    
    // Set success message
    $_SESSION['batal_success'] = "✅ Pembatalan berhasil diajukan! Booking " . $booking_data['kode_booking'] . " untuk " . $booking_data['nama_penginapan'] . " sedang diproses.";
    
    // Redirect ke status pembatalan
    header("Location: status_pembatalan.php");
    exit;
    
} catch (Exception $e) {
    // ROLLBACK jika ada error
    mysqli_rollback($conn);
    
    $error_msg = $e->getMessage();
    file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - EXCEPTION: $error_msg\n", FILE_APPEND);
    
    $_SESSION['error_message'] = "Gagal membatalkan booking: " . $error_msg;
    header("Location: proses_pembatalan.php?id=" . $id_booking);
    exit;
}
?>
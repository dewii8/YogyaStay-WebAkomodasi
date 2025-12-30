<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi admin
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_gambar'])) {

    $id_gambar = intval($_POST['id_gambar']);

    try {
        $query = "SELECT path_gambar FROM gambar_penginapan WHERE id_gambar = $id_gambar";
        $result = mysqli_query($conn, $query);

        if (!$result || mysqli_num_rows($result) === 0) {
            echo json_encode(['success' => false, 'message' => 'Gambar tidak ditemukan']);
            exit;
        }

        $row = mysqli_fetch_assoc($result);
        $path_gambar = $row['path_gambar'];

        $full_path = "../../" . $path_gambar;
        if (file_exists($full_path)) {
            unlink($full_path);
        }

        $delete_query = "DELETE FROM gambar_penginapan WHERE id_gambar = $id_gambar";
        $delete_result = mysqli_query($conn, $delete_query);

        if ($delete_result) {
            // Log aktivitas 
            if (isset($_SESSION['admin_id'])) {
                $adminId = intval($_SESSION['admin_id']);
                $aksi = "Hapus gambar ID $id_gambar";
                mysqli_query($conn, "
                    INSERT INTO log_aktivitas_admin (admin_id, aksi, target_id)
                    VALUES ($adminId, '" . mysqli_real_escape_string($conn, $aksi) . "', $id_gambar)
                ");
            }

            echo json_encode(['success' => true, 'message' => 'Gambar berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus gambar dari database']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
<?php
date_default_timezone_set('Asia/Jakarta');
function addAdminLog($conn, $admin_id, $aksi, $deskripsi = null) {
    $stmt = $conn->prepare("INSERT INTO log_aktivitas_admin (id_admin, aksi, deskripsi) VALUES (?, ?, ?)");
    if (!$stmt) {
        // Kalau prepare gagal, tampilkan error
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("iss", $admin_id, $aksi, $deskripsi);
    $stmt->execute();
    $stmt->close();
}
?>
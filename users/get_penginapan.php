<?php
require_once '../config.php';

$kabupatenId = isset($_GET['kabupaten']) ? $_GET['kabupaten'] : 'all';

// Query untuk mengambil penginapan
if ($kabupatenId === 'all') {
    $query = "SELECT p.*, k.nama_kabupaten, kec.nama_kecamatan, 
              gp.path_gambar as gambar
              FROM penginapan p
              LEFT JOIN kabupaten k ON p.id_kabupaten = k.id_kabupaten
              LEFT JOIN kecamatan kec ON p.id_kecamatan = kec.id_kecamatan
              LEFT JOIN gambar_penginapan gp ON p.id_penginapan = gp.id_penginapan AND gp.is_thumbnail = 1
              WHERE p.status = 'aktif'
              ORDER BY p.created_at DESC
              LIMIT 4";
    $stmt = mysqli_prepare($conn, $query);
} else {
    $query = "SELECT p.*, k.nama_kabupaten, kec.nama_kecamatan, 
              gp.path_gambar as gambar
              FROM penginapan p
              LEFT JOIN kabupaten k ON p.id_kabupaten = k.id_kabupaten
              LEFT JOIN kecamatan kec ON p.id_kecamatan = kec.id_kecamatan
              LEFT JOIN gambar_penginapan gp ON p.id_penginapan = gp.id_penginapan AND gp.is_thumbnail = 1
              WHERE p.id_kabupaten = ? AND p.status = 'aktif'
              ORDER BY p.created_at DESC
              LIMIT 4";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $kabupatenId);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$penginapan = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Ambil fasilitas dari tabel penginapan_fasilitas
    $fasilitas_query = "SELECT f.nama_fasilitas 
                        FROM penginapan_fasilitas pf
                        JOIN fasilitas f ON pf.id_fasilitas = f.id_fasilitas
                        WHERE pf.id_penginapan = ?
                        LIMIT 3";
    $stmt_fas = mysqli_prepare($conn, $fasilitas_query);
    mysqli_stmt_bind_param($stmt_fas, "i", $row['id_penginapan']);
    mysqli_stmt_execute($stmt_fas);
    $result_fas = mysqli_stmt_get_result($stmt_fas);
    
    $fasilitas = [];
    while ($fas = mysqli_fetch_assoc($result_fas)) {
        $fasilitas[] = $fas['nama_fasilitas'];
    }
    
    $row['fasilitas'] = $fasilitas; // Langsung array, bukan JSON string
    $penginapan[] = $row;
}

header('Content-Type: application/json');
echo json_encode($penginapan);
?>
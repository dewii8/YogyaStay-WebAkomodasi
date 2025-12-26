<?php
require_once '../config.php';

// Tambahkan ini untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$kabupatenId = isset($_GET['kabupaten']) ? $_GET['kabupaten'] : 'all';

// Ambil 4 penginapan terbaik
function getPenginapan($kabupatenId) {
    global $conn;
    
    $query = "SELECT 
                p.id_penginapan, 
                p.nama_penginapan, 
                p.tipe_penginapan, 
                p.alamat, 
                p.harga_mulai, 
                p.rating, 
                p.jumlah_review,
                p.is_featured, 
                k.nama_kabupaten, 
                kc.nama_kecamatan
              FROM 
                penginapan p
              JOIN 
                kabupaten k ON p.id_kabupaten = k.id_kabupaten
              JOIN 
                kecamatan kc ON p.id_kecamatan = kc.id_kecamatan
              WHERE 
                p.status = 'aktif'";
    
    // Tambahkan filter kabupaten jika bukan 'all'
    if ($kabupatenId !== 'all') {
        $query .= " AND p.id_kabupaten = " . intval($kabupatenId);
    }
    
    // Urutkan dan batasi jumlah
    $query .= " ORDER BY p.rating DESC, p.jumlah_review DESC LIMIT 4";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        return ["error" => mysqli_error($conn)];
    }
    
    $penginapanList = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Dapatkan gambar thumbnail
        $gambar_query = "SELECT path_gambar 
                         FROM gambar_penginapan 
                         WHERE id_penginapan = {$row['id_penginapan']} 
                         AND is_thumbnail = 1 
                         LIMIT 1";
                         
        $gambar_result = mysqli_query($conn, $gambar_query);
        
        if ($gambar_result && mysqli_num_rows($gambar_result) > 0) {
            $gambar = mysqli_fetch_assoc($gambar_result);
            $row['gambar'] = $gambar['path_gambar'];
        } else {
            $row['gambar'] = 'uploads/default.jpg';
        }
        
        // Dapatkan fasilitas
        $fasilitas_query = "SELECT f.nama_fasilitas
                           FROM fasilitas f
                           JOIN penginapan_fasilitas pf ON f.id_fasilitas = pf.id_fasilitas
                           WHERE pf.id_penginapan = {$row['id_penginapan']} 
                           LIMIT 3";
                           
        $fasilitas_result = mysqli_query($conn, $fasilitas_query);
        $row['fasilitas'] = [];
        
        if ($fasilitas_result) {
            while ($fasilitas = mysqli_fetch_assoc($fasilitas_result)) {
                $row['fasilitas'][] = $fasilitas;
            }
        }
        
        $penginapanList[] = $row;
    }
    
    return $penginapanList;
}

// Ambil penginapan
$penginapan = getPenginapan($kabupatenId);

// Kembalikan hasil
header('Content-Type: application/json');
echo json_encode($penginapan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<?php
require_once '../../config.php';

if (isset($_GET['id_kabupaten'])) {
    $id_kabupaten = mysqli_real_escape_string($conn, $_GET['id_kabupaten']);
    
    $query = mysqli_query($conn, "
        SELECT * FROM kecamatan 
        WHERE id_kabupaten = '$id_kabupaten' 
        ORDER BY nama_kecamatan ASC
    ");
    
    if ($query && mysqli_num_rows($query) > 0) {
        while ($row = mysqli_fetch_assoc($query)) {
            echo '<option value="' . $row['id_kecamatan'] . '">' . htmlspecialchars($row['nama_kecamatan']) . '</option>';
        }
    } else {
        echo '<option value="">Tidak ada kecamatan</option>';
    }
}
?>
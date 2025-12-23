<?php
require_once '../config.php';
header('Content-Type: application/json');

if (isset($_GET['id_kabupaten']) && $_GET['id_kabupaten'] !== '') {
    $id = mysqli_real_escape_string($conn, $_GET['id_kabupaten']);

    $query = "SELECT id_kecamatan AS id, nama_kecamatan AS nama 
              FROM kecamatan 
              WHERE id_kabupaten = '$id'
              ORDER BY nama_kecamatan";

    $result = mysqli_query($conn, $query);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    echo json_encode($data);
} else {
    echo json_encode([]);
}
?>
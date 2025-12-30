<?php
require_once '../config.php';
header('Content-Type: application/json');

$query = "SELECT id_kabupaten AS id, nama_kabupaten AS nama FROM kabupaten ORDER BY nama_kabupaten";
$result = mysqli_query($conn, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode($data);
?>
<?php
require_once '../../config.php';

if (isset($_GET['id'])) {
    $id_penginapan = mysqli_real_escape_string($conn, $_GET['id']);

    // Hapus gambar penginapan dari database
    $sqlGambar = "DELETE FROM gambar_penginapan WHERE id_penginapan = '$id_penginapan'";
    mysqli_query($conn, $sqlGambar);

    // Hapus tipe kamar yang terkait dengan penginapan
    $sqlTipeKamar = "DELETE FROM tipe_kamar WHERE id_penginapan = '$id_penginapan'";
    mysqli_query($conn, $sqlTipeKamar);

    // Hapus fasilitas penginapan
    $sqlFasilitas = "DELETE FROM penginapan_fasilitas WHERE id_penginapan = '$id_penginapan'";
    mysqli_query($conn, $sqlFasilitas);

    // Hapus penginapan dari tabel penginapan
    $sqlPenginapan = "DELETE FROM penginapan WHERE id_penginapan = '$id_penginapan'";
    mysqli_query($conn, $sqlPenginapan);

    header("Location: inventori.php?tipe=hotel"); 
    exit;
} else {
    header("Location: inventori.php?tipe=hotel"); 
}
?>
+
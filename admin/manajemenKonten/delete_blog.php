<?php
require_once '../../config.php';
require_once '../log/functions.php';

session_start();

// Pastikan ID dikirim
if (!isset($_GET['id'])) {
    header("Location: konten.php");
    exit;
}

$id_blog = (int) $_GET['id'];

// Ambil data blog dulu (judul + thumbnail) sebelum dihapus
$query = mysqli_query($conn, "SELECT judul, thumbnail FROM blog WHERE id_blog=$id_blog");
if (!$query) {
    die("Query gagal: " . mysqli_error($conn));
}

$blog = mysqli_fetch_assoc($query);
if (!$blog) {
    die("Blog dengan ID $id_blog tidak ditemukan.");
}

// Hapus file thumbnail jika ada
$file = '../../assets/blog/' . $blog['thumbnail'];
if (file_exists($file)) {
    unlink($file);
}

// Hapus data blog
$delete = mysqli_query($conn, "DELETE FROM blog WHERE id_blog=$id_blog");
if (!$delete) {
    die("Gagal menghapus blog: " . mysqli_error($conn));
}

// Tambah log aktivitas admin
$aksi = "Hapus Blog";
$deskripsi = "Admin ID " . $_SESSION['user_id'] . " menghapus blog ID $id_blog berjudul '" . $blog['judul'] . "'";
addAdminLog($conn, $_SESSION['user_id'], $aksi, $deskripsi);

// Redirect kembali
header("Location: konten.php?deleted=1");
exit;
?>
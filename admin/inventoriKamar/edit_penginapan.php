<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*  VALIDASI ID */
$id = $_POST['id_penginapan'] ?? $_GET['id'] ?? null;
if (!$id) die("ID penginapan hilang");

/* DATA MASTER  */
$qFasilitas = mysqli_query($conn, "SELECT * FROM fasilitas");
$qKabupaten = mysqli_query($conn, "SELECT * FROM kabupaten ORDER BY nama_kabupaten");

/* DATA PENGINAPAN */
$qData = mysqli_query($conn, "SELECT * FROM penginapan WHERE id_penginapan='$id'");
$data = mysqli_fetch_assoc($qData);
if (!$data) die("Data penginapan tidak ditemukan");

/* FASILITAS TERPILIH  */
$fasilitasDipilih = [];
$q = mysqli_query($conn, "SELECT id_fasilitas FROM penginapan_fasilitas WHERE id_penginapan='$id'");
while ($r = mysqli_fetch_assoc($q)) {
    $fasilitasDipilih[] = $r['id_fasilitas'];
}

/* TIPE KAMAR */
$tipeKamar = [];
$qKamar = mysqli_query($conn, "SELECT * FROM tipe_kamar WHERE id_penginapan='$id'");
while ($r = mysqli_fetch_assoc($qKamar)) {
    $tipeKamar[] = $r;
}

/* UPDATE  */
if (isset($_POST['update'])) {

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($conn);

    try {

        /*  UPDATE PENGINAPAN  */
        $tipe_penginapan = $_POST['tipe_penginapan'] ?? $data['tipe_penginapan'];
        $id_kabupaten    = $_POST['id_kabupaten'] ?? $data['id_kabupaten'];
        $id_kecamatan    = $_POST['id_kecamatan'] ?? $data['id_kecamatan'];
        $tentang_kami    = $_POST['tentang_kami'] ?? $data['tentang_kami'];

        mysqli_query($conn, "
            UPDATE penginapan SET
                nama_penginapan = '".mysqli_real_escape_string($conn,$_POST['nama_penginapan'])."',
                tipe_penginapan = '".mysqli_real_escape_string($conn,$tipe_penginapan)."',
                id_kabupaten    = '".intval($id_kabupaten)."',
                id_kecamatan    = '".intval($id_kecamatan)."',
                alamat          = '".mysqli_real_escape_string($conn,$_POST['lokasi'])."',
                deskripsi       = '".mysqli_real_escape_string($conn,$_POST['deskripsi'])."',
                tentang_kami    = '".mysqli_real_escape_string($conn,$tentang_kami)."'
            WHERE id_penginapan='$id'
        ");


        /*  FASILITAS  */
        mysqli_query($conn, "DELETE FROM penginapan_fasilitas WHERE id_penginapan='$id'");
        if (!empty($_POST['fasilitas'])) {
            foreach ($_POST['fasilitas'] as $f) {
                mysqli_query($conn,"
                    INSERT INTO penginapan_fasilitas (id_penginapan,id_fasilitas)
                    VALUES ('$id','".intval($f)."')
                ");
            }
        }

        /*  TIPE KAMAR  */
        mysqli_query($conn, "DELETE FROM tipe_kamar WHERE id_penginapan='$id'");

        if (!empty($_POST['tipe_kamar'])) {
            foreach ($_POST['tipe_kamar'] as $i => $namaTipe) {
                mysqli_query($conn,"
                    INSERT INTO tipe_kamar
                    (id_penginapan,nama_tipe,harga_per_malam,kapasitas_orang,jumlah_kamar)
                    VALUES
                    (
                        '$id',
                        '".mysqli_real_escape_string($conn,$namaTipe)."',
                        '".intval($_POST['harga'][$i])."',
                        '".intval($_POST['kapasitas'][$i])."',
                        '".intval($_POST['total_unit'][$i])."'
                    )
                ");
            }
        }

        /*  UPDATE HARGA MULAI  */
        mysqli_query($conn,"
            UPDATE penginapan SET harga_mulai = (
                SELECT MIN(harga_per_malam)
                FROM tipe_kamar
                WHERE id_penginapan='$id'
            )
            WHERE id_penginapan='$id'
        ");

        /*  GAMBAR  */
        if (!empty($_FILES['gambar']['name'])) {

            $folder = "../../assets/img/penginapan/";
            $ext    = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $file   = "penginapan_{$id}_" . time() . "." . $ext;

            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $folder.$file)) {

                mysqli_query($conn,"
                    INSERT INTO gambar_penginapan (id_penginapan, path_gambar)
                    VALUES ('$id','$file')
                ");
            }
        }

        mysqli_commit($conn);

        // ================= LOG AKTIVITAS ADMIN =================
        if (isset($_SESSION['admin_id'])) { // ganti 'admin_id' sesuai session login
            $adminId = intval($_SESSION['admin_id']);
            $aksi = "Update penginapan ID $id (".$_POST['nama_penginapan'].")";
            mysqli_query($conn, "
                INSERT INTO log_aktivitas_admin (admin_id, aksi, target_id)
                VALUES ($adminId, '".mysqli_real_escape_string($conn, $aksi)."', $id)
            ");
        }
        // =======================================================

        header("Location: inventori.php?update=success");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        die("GAGAL UPDATE: ".$e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Penginapan</title>

<style>
* {
    box-sizing: border-box;
    font-family: "Segoe UI", sans-serif;
}

body {
    background: #fff7ed;
    padding: 30px;
}

.container {
    max-width: 900px;
    margin: auto;
    background: #ffffff;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,.08);
}

h2 {
    margin-bottom: 20px;
    color: #111827;
}

label {
    font-weight: 600;
    margin-top: 15px;
    display: block;
}

input, textarea, select {
    width: 100%;
    padding: 12px;
    margin-top: 6px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    outline: none;
}

input:focus, textarea:focus {
    border-color: #fbbf24;
}

textarea {
    resize: vertical;
}

.kamar-box {
    background: #f9fafb;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 15px;
    border: 1px solid #e5e7eb;
}

hr {
    border: none;
    border-top: 1px dashed #d1d5db;
    margin: 20px 0;
}

button {
    margin-top: 20px;
    padding: 14px 30px;
    background: #fbbf24;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
}

button:hover {
    background: #f59e0b;
}

.button-group .btn-cancel {
    flex: 0;                 
    padding: 10px 20px;
    font-size: 14px;
    background: #f3f4f6;     
    color: #111827;          
    border: 1px solid #d1d5db; 
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
    text-decoration: none;
    transition: all 0.2s;
}

.button-group .btn-cancel:hover {
    background: #e5e7eb;      
    box-shadow: 0 4px 6px rgba(0,0,0,0.15);
}


img.preview {
    max-width: 200px;
    margin-top: 10px;
    border-radius: 12px;
    display: block;
}
</style>
</head>
<body>

<div class="container">
    <h2>Edit Penginapan</h2>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_penginapan" value="<?= $id ?>">

        <!-- DATA UTAMA -->
        <div class="form-group">
            <label>Nama Penginapan</label>
            <input type="text" name="nama_penginapan"
                   value="<?= htmlspecialchars($data['nama_penginapan']) ?>">
        </div>

        <div class="form-group">
            <label>Alamat</label>
            <textarea name="lokasi"><?= htmlspecialchars($data['alamat']) ?></textarea>
        </div>

        <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="deskripsi"><?= htmlspecialchars($data['deskripsi']) ?></textarea>
        </div>

        <!-- FASILITAS -->
        <h3>Fasilitas</h3>
        <div class="form-group">
            <select name="fasilitas[]" multiple>
                <?php while ($f = mysqli_fetch_assoc($qFasilitas)) : ?>
                    <option value="<?= $f['id_fasilitas'] ?>"
                        <?= in_array($f['id_fasilitas'], $fasilitasDipilih) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['nama_fasilitas']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <small>Tahan Ctrl untuk pilih lebih dari satu</small>
        </div>

        <!-- TIPE KAMAR -->
        <h3>Tipe Kamar</h3>

        <?php foreach ($tipeKamar as $k): ?>
        <div class="kamar-box">
            <div class="form-group">
                <label>Nama Tipe</label>
                <input name="tipe_kamar[]" value="<?= $k['nama_tipe'] ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Harga</label>
                    <input name="harga[]" value="<?= $k['harga_per_malam'] ?>">
                </div>

                <div class="form-group">
                    <label>Kapasitas</label>
                    <input name="kapasitas[]" value="<?= $k['kapasitas_orang'] ?>">
                </div>

                <div class="form-group">
                    <label>Total Unit</label>
                    <input name="total_unit[]" value="<?= $k['jumlah_kamar'] ?>">
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- GAMBAR -->
        <h3>Gambar Penginapan</h3>

        <div class="preview-wrapper">
            <?php
            $qImg = mysqli_query($conn,"
                SELECT path_gambar
                FROM gambar_penginapan
                WHERE id_penginapan='$id'
            ");
            while ($img = mysqli_fetch_assoc($qImg)):
            ?>
                <img src="../../assets/img/penginapan/<?= htmlspecialchars($img['path_gambar']) ?>" class="preview">
            <?php endwhile; ?>
        </div>

        <div class="form-group">
            <input type="file" name="gambar">
        </div>

        <!-- BUTTON -->
        <div class="button-group">
            <button name="update" type="submit">Update Data</button>
            <a href="inventori.php" class="btn-cancel">Batal</a>
        </div>
    </form>
</div>


</body>
</html>


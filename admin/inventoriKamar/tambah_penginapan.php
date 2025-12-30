<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 'admin') {
    die("Akses ditolak");
}

$qFasilitas = mysqli_query($conn, "SELECT * FROM fasilitas");
$qTipeKamar = mysqli_query($conn, "SELECT DISTINCT nama_tipe FROM tipe_kamar ORDER BY nama_tipe");
$qKabupaten = mysqli_query($conn, "SELECT * FROM kabupaten ORDER BY nama_kabupaten");

if (isset($_POST['simpan'])) {

    mysqli_begin_transaction($conn);

    try {
        // DATA PENGINAPAN 
        $tipe   = mysqli_real_escape_string($conn, $_POST['tipe_penginapan']);
        $nama   = mysqli_real_escape_string($conn, $_POST['nama_penginapan']);
        $kab    = $_POST['id_kabupaten'];
        $kec    = $_POST['id_kecamatan'];
        $alamat = mysqli_real_escape_string($conn, $_POST['lokasi']);
        $desk   = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $tentang= mysqli_real_escape_string($conn, $_POST['tentang_kami'] ?? '');

        $sql = "INSERT INTO penginapan 
        (nama_penginapan, tipe_penginapan, id_kabupaten, id_kecamatan, alamat, deskripsi, tentang_kami, status, created_at)
        VALUES ('$nama','$tipe','$kab','$kec','$alamat','$desk','$tentang','aktif',NOW())";

        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Gagal simpan penginapan");
        }

        $id_penginapan = mysqli_insert_id($conn);

        //  KONTAK 
        if (!empty($_POST['kontak'])) {
            mysqli_query($conn, "INSERT INTO kontak_penginapan VALUES (NULL,'$id_penginapan','telepon','$_POST[kontak]')");
        }
        if (!empty($_POST['no_wa'])) {
            mysqli_query($conn, "INSERT INTO kontak_penginapan VALUES (NULL,'$id_penginapan','whatsapp','$_POST[no_wa]')");
        }
        if (!empty($_POST['email'])) {
            mysqli_query($conn, "INSERT INTO kontak_penginapan VALUES (NULL,'$id_penginapan','email','$_POST[email]')");
        }

        //  TIPE KAMAR
        foreach ($_POST['tipe_kamar'] as $tk) {
            mysqli_query($conn, "INSERT INTO tipe_kamar 
            (id_penginapan,nama_tipe,harga_per_malam,kapasitas_orang,jumlah_kamar,deskripsi)
            VALUES ('$id_penginapan','$tk','$_POST[harga]','$_POST[kapasitas]','$_POST[total_unit]','Kamar $tk')");
        }

        //  FASILITAS 
        foreach ($_POST['fasilitas'] as $f) {
            mysqli_query($conn, "INSERT INTO penginapan_fasilitas VALUES ('$id_penginapan','$f')");
        }

        // GAMBAR PENGINAPAN (hanya gambar penginapan, bukan tipe kamar)
        $dir = "../../uploads/penginapan/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        foreach ($_FILES['gambar_penginapan']['tmp_name'] as $i => $tmp) {
            if ($_FILES['gambar_penginapan']['error'][$i] === 0) {
                $file = uniqid().".".pathinfo($_FILES['gambar_penginapan']['name'][$i], PATHINFO_EXTENSION);
                move_uploaded_file($tmp, $dir.$file);

                mysqli_query($conn, "INSERT INTO gambar_penginapan 
                (id_penginapan,path_gambar,is_thumbnail,created_at)
                VALUES ('$id_penginapan','uploads/penginapan/$file','".($i==0?1:0)."',NOW())");
            }
        }

        // UPDATE HARGA 
        mysqli_query($conn, "
        UPDATE penginapan 
        SET harga_mulai = (SELECT MIN(harga_per_malam) FROM tipe_kamar WHERE id_penginapan='$id_penginapan')
        WHERE id_penginapan='$id_penginapan'
        ");

        mysqli_commit($conn);

        // LOG AKTIVITAS ADMIN - DENGAN AUTO-DETECT KOLOM
        if (isset($_SESSION['user_id'])) {
            $admin_id = intval($_SESSION['user_id']);
            $aksi = mysqli_real_escape_string($conn, "Tambah Penginapan");
            $deskripsi = mysqli_real_escape_string($conn, "Admin menambahkan penginapan baru: $nama (ID: $id_penginapan)");
            
            // Cek struktur tabel log_aktivitas_admin
            $check_table = mysqli_query($conn, "DESCRIBE log_aktivitas_admin");
            
            if ($check_table) {
                $columns = [];
                while ($col = mysqli_fetch_assoc($check_table)) {
                    $columns[] = $col['Field'];
                }
                
                // Deteksi nama kolom untuk admin_id
                $admin_column = null;
                $possible_admin_columns = ['id_admin', 'admin_id', 'user_id', 'id_user'];
                foreach ($possible_admin_columns as $col) {
                    if (in_array($col, $columns)) {
                        $admin_column = $col;
                        break;
                    }
                }
                
                // Deteksi nama kolom untuk timestamp
                $time_column = null;
                $possible_time_columns = ['created_at', 'waktu', 'tanggal', 'timestamp', 'date_created'];
                foreach ($possible_time_columns as $col) {
                    if (in_array($col, $columns)) {
                        $time_column = $col;
                        break;
                    }
                }
                
                // Buat query sesuai kolom yang tersedia
                if ($admin_column && in_array('aksi', $columns)) {
                    // Jika ada kolom deskripsi, gunakan
                    if (in_array('deskripsi', $columns)) {
                        if ($time_column) {
                            $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, deskripsi, $time_column) 
                                         VALUES ('$admin_id', '$aksi', '$deskripsi', NOW())";
                        } else {
                            $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, deskripsi) 
                                         VALUES ('$admin_id', '$aksi', '$deskripsi')";
                        }
                    } 
                    // Jika tidak ada kolom deskripsi tapi ada target_id
                    else if (in_array('target_id', $columns)) {
                        if ($time_column) {
                            $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, target_id, $time_column) 
                                         VALUES ('$admin_id', '$aksi', '$id_penginapan', NOW())";
                        } else {
                            $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, target_id) 
                                         VALUES ('$admin_id', '$aksi', '$id_penginapan')";
                        }
                    }
                    // Hanya admin_column dan aksi
                    else {
                        if ($time_column) {
                            $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, $time_column) 
                                         VALUES ('$admin_id', '$aksi', NOW())";
                        } else {
                            $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi) 
                                         VALUES ('$admin_id', '$aksi')";
                        }
                    }
                    
                    // Execute log query
                    mysqli_query($conn, $log_query);
                }
            }
        }

        $_SESSION['success'] = "Penginapan berhasil ditambahkan";
        header("Location: inventori.php?tipe=".strtolower($tipe));
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        die("GAGAL: ".$e->getMessage());
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Penginapan</title>
 <link rel="icon" type="image/jpeg" href="../../assets/img/logonw.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
* {
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: #fff7ed;
    margin: 0;
    padding: 20px;
}

.modal {
    max-width: 900px;
    margin: auto;
    background: #fff;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

h3 {
    margin-bottom: 25px;
    font-weight: 600;
    font-size: 28px;
    color: #1a1a1a;
}

.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.alert-info {
    background: #cfe2ff;
    color: #084298;
    border: 2px solid #9ec5fe;
}

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border: 2px solid #fbbf24;
}

/* GRID FORM */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}

.form-full {
    grid-column: 1 / -1;
}

label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    display: block;
    margin-bottom: 8px;
}

input, textarea, select {
    width: 100%;
    padding: 12px 15px;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    font-size: 14px;
    transition: all 0.3s;
}

input:focus, textarea:focus, select:focus {
    outline: none;
    border-color: #fbbf24;
    box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
}

textarea {
    resize: vertical;
    min-height: 120px;
}

select[multiple] {
    height: 160px;
    padding: 10px;
}

select[multiple] option {
    padding: 8px 12px;
    border-radius: 6px;
    margin-bottom: 5px;
}

select[multiple] option:checked {
    background: #fef3c7;
    color: #92400e;
}

input[type="file"] {
    padding: 10px;
    cursor: pointer;
}

.actions {
    display: flex;
    gap: 12px;
    margin-top: 25px;
}

.btn {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    border: 0;
    padding: 14px 30px;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    color: white;
    font-size: 15px;
    transition: all 0.3s;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(251, 191, 36, 0.4);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

a {
    text-decoration: none;
    color: #6b7280;
    font-size: 15px;
    align-self: center;
    font-weight: 500;
    transition: all 0.3s;
}

a:hover {
    color: #1a1a1a;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .modal {
        padding: 20px;
    }
}
</style>
</head>

<body>

<div class="modal">
<h3>Tambah Penginapan Baru</h3>

<div class="alert alert-warning">
    <strong>📌 Petunjuk:</strong> Pastikan semua field bertanda * (wajib) sudah diisi. Upload minimal 1 gambar penginapan.
</div>

<form method="POST" enctype="multipart/form-data" id="formPenginapan">

<div class="form-grid">

<div class="form-group form-full">
    <label>Tipe Penginapan *</label>
    <select name="tipe_penginapan" required>
        <option value="">-- Pilih Tipe --</option>
        <option value="Homestay">Homestay</option>
        <option value="Hotel">Hotel</option>
        <option value="Villa">Villa</option>
    </select>
</div>

<div class="form-group form-full">
    <label>Nama Penginapan *</label>
    <input name="nama_penginapan" placeholder="Contoh: Hotel Sunrise Yogyakarta" required>
</div>

<div class="form-group">
    <label>Kabupaten *</label>
    <select name="id_kabupaten" id="kabupaten" required>
        <option value="">-- Pilih Kabupaten --</option>
        <?php while($kb = mysqli_fetch_assoc($qKabupaten)) { ?>
            <option value="<?= $kb['id_kabupaten'] ?>"><?= $kb['nama_kabupaten'] ?></option>
        <?php } ?>
    </select>
</div>

<div class="form-group">
    <label>Kecamatan *</label>
    <select name="id_kecamatan" id="kecamatan" required>
        <option value="">-- Pilih Kecamatan --</option>
    </select>
</div>

<div class="form-group form-full">
    <label>Alamat Lengkap *</label>
    <textarea name="lokasi" placeholder="Jl. Malioboro No. 123, Yogyakarta" required></textarea>
</div>

<div class="form-group">
    <label>Tipe Kamar *</label>
    <select name="tipe_kamar[]" multiple required>
        <?php 
        mysqli_data_seek($qTipeKamar, 0);
        while($t = mysqli_fetch_assoc($qTipeKamar)) { ?>
            <option value="<?= $t['nama_tipe'] ?>"><?= $t['nama_tipe'] ?></option>
        <?php } ?>
        <option value="Standard">Standard</option>
        <option value="Deluxe">Deluxe</option>
        <option value="Suite">Suite</option>
    </select>
    <small style="color: #6b7280; font-size: 12px;">Tahan Ctrl untuk pilih beberapa</small>
</div>

<div class="form-group">
    <label>Kapasitas Orang *</label>
    <input name="kapasitas" type="number" placeholder="2" min="1" required>
</div>

<div class="form-group">
    <label>Total Unit Kamar *</label>
    <input name="total_unit" type="number" placeholder="10" min="1" required>
</div>

<div class="form-group">
    <label>Harga per Malam (Rp) *</label>
    <input name="harga" type="number" placeholder="500000" min="0" required>
</div>

<div class="form-group form-full">
    <label>Deskripsi Penginapan *</label>
    <textarea name="deskripsi" placeholder="Jelaskan keunggulan dan daya tarik penginapan Anda..." required></textarea>
</div>

<div class="form-group form-full">
    <label>Fasilitas *</label>
    <select name="fasilitas[]" multiple required>
        <?php 
        mysqli_data_seek($qFasilitas, 0);
        while($f = mysqli_fetch_assoc($qFasilitas)) { ?>
            <option value="<?= $f['id_fasilitas'] ?>"><?= $f['nama_fasilitas'] ?></option>
        <?php } ?>
    </select>
    <small style="color: #6b7280; font-size: 12px;">Tahan Ctrl untuk pilih beberapa</small>
</div>

<div class="form-group">
    <label>Nomor Kontak</label>
    <input name="kontak" type="tel" placeholder="0274-123456">
</div>

<div class="form-group">
    <label>Nomor WhatsApp</label>
    <input name="no_wa" type="tel" placeholder="08123456789">
</div>

<div class="form-group form-full">
    <label>Email Penginapan</label>
    <input name="email" type="email" placeholder="info@penginapan.com">
</div>

<div class="form-group form-full">
    <label>Tentang Kami</label>
    <textarea name="tentang_kami" placeholder="Ceritakan lebih banyak tentang penginapan Anda..."></textarea>
</div>

<div class="form-group form-full">
    <label>Gambar Penginapan * (Maks 5 foto)</label>
    <input type="file" name="gambar_penginapan[]" multiple accept="image/*" required>
    <small style="color: #6b7280; font-size: 12px;">Foto pertama akan jadi thumbnail</small>
</div>

</div>

<div class="actions">
    <button name="simpan" type="submit" class="btn" id="btnSubmit">
        <i class="fa-solid fa-floppy-disk"></i>
        Simpan Penginapan
    </button>

    <a href="inventori.php">← Batal</a>
</div>

</form>
</div>

<script>
// AJAX untuk load kecamatan berdasarkan kabupaten
document.getElementById('kabupaten').addEventListener('change', function() {
    const idKabupaten = this.value;
    const kecamatanSelect = document.getElementById('kecamatan');
    
    if (!idKabupaten) {
        kecamatanSelect.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
        return;
    }
    
    kecamatanSelect.innerHTML = '<option value="">Loading...</option>';
    
    fetch(`get_kecamatan.php?id_kabupaten=${idKabupaten}`)
        .then(res => res.text())
        .then(data => {
            kecamatanSelect.innerHTML = '<option value="">-- Pilih Kecamatan --</option>' + data;
        })
        .catch(err => {
            console.error('Error loading kecamatan:', err);
            kecamatanSelect.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
        });
});
</script>

</body>
</html>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* PROTEKSI ADMIN */
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 'admin') {
    header("Location: ../../autentikasi/login.php");
    exit;
}

/*  VALIDASI ID */
$id = $_POST['id_penginapan'] ?? $_GET['id'] ?? null;
if (!$id)
    die("ID penginapan hilang");

/* DATA MASTER  */
$qFasilitas = mysqli_query($conn, "SELECT * FROM fasilitas");
$qKabupaten = mysqli_query($conn, "SELECT * FROM kabupaten ORDER BY nama_kabupaten");

/* DATA PENGINAPAN */
$qData = mysqli_query($conn, "SELECT * FROM penginapan WHERE id_penginapan='$id'");
$data = mysqli_fetch_assoc($qData);
if (!$data)
    die("Data penginapan tidak ditemukan");

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

/* KONTAK */
$kontak_data = [];
$qKontak = mysqli_query($conn, "SELECT * FROM kontak_penginapan WHERE id_penginapan='$id'");
while ($k = mysqli_fetch_assoc($qKontak)) {
    $kontak_data[$k['jenis_kontak']] = $k['isi_kontak'];
}

/* UPDATE  */
if (isset($_POST['update'])) {

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($conn);

    try {

        /*  UPDATE PENGINAPAN  */
        $tipe_penginapan = mysqli_real_escape_string($conn, $_POST['tipe_penginapan'] ?? $data['tipe_penginapan']);
        $nama_penginapan = mysqli_real_escape_string($conn, $_POST['nama_penginapan']);
        $id_kabupaten = intval($_POST['id_kabupaten'] ?? $data['id_kabupaten']);
        $id_kecamatan = intval($_POST['id_kecamatan'] ?? $data['id_kecamatan']);
        $alamat = mysqli_real_escape_string($conn, $_POST['lokasi']);
        $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
        $tentang_kami = mysqli_real_escape_string($conn, $_POST['tentang_kami'] ?? '');

        mysqli_query($conn, "
            UPDATE penginapan SET
                nama_penginapan = '$nama_penginapan',
                tipe_penginapan = '$tipe_penginapan',
                id_kabupaten    = '$id_kabupaten',
                id_kecamatan    = '$id_kecamatan',
                alamat          = '$alamat',
                deskripsi       = '$deskripsi',
                tentang_kami    = '$tentang_kami'
            WHERE id_penginapan='$id'
        ");


        /*  FASILITAS  */
        mysqli_query($conn, "DELETE FROM penginapan_fasilitas WHERE id_penginapan='$id'");
        if (!empty($_POST['fasilitas'])) {
            foreach ($_POST['fasilitas'] as $f) {
                mysqli_query($conn, "
                    INSERT INTO penginapan_fasilitas (id_penginapan,id_fasilitas)
                    VALUES ('$id','" . intval($f) . "')
                ");
            }
        }

        /*  KONTAK  */
        mysqli_query($conn, "DELETE FROM kontak_penginapan WHERE id_penginapan='$id'");
        if (!empty($_POST['kontak'])) {
            mysqli_query($conn, "INSERT INTO kontak_penginapan VALUES (NULL,'$id','telepon','" . mysqli_real_escape_string($conn, $_POST['kontak']) . "')");
        }
        if (!empty($_POST['no_wa'])) {
            mysqli_query($conn, "INSERT INTO kontak_penginapan VALUES (NULL,'$id','whatsapp','" . mysqli_real_escape_string($conn, $_POST['no_wa']) . "')");
        }
        if (!empty($_POST['email'])) {
            mysqli_query($conn, "INSERT INTO kontak_penginapan VALUES (NULL,'$id','email','" . mysqli_real_escape_string($conn, $_POST['email']) . "')");
        }

        /*  TIPE KAMAR  */

        // 1. Ambil tipe kamar existing
        $existing_tipe = [];
        $qExisting = mysqli_query($conn, "SELECT id_tipe_kamar FROM tipe_kamar WHERE id_penginapan='$id'");
        while ($row = mysqli_fetch_assoc($qExisting)) {
            $existing_tipe[] = $row['id_tipe_kamar'];
        }

        // 2. Hapus tipe kamar lama dari database
        if (!empty($existing_tipe)) {
            $ids = implode(',', $existing_tipe);

            $qSafeDelete = mysqli_query($conn, "
        SELECT tk.id_tipe_kamar
        FROM tipe_kamar tk
        LEFT JOIN booking b ON tk.id_tipe_kamar = b.id_tipe_kamar 
            AND b.status_reservasi IN ('dipesan', 'check-in')
        WHERE tk.id_tipe_kamar IN ($ids)
        GROUP BY tk.id_tipe_kamar
        HAVING COUNT(b.id_booking) = 0
    ");

            while ($safe = mysqli_fetch_assoc($qSafeDelete)) {
                mysqli_query($conn, "DELETE FROM tipe_kamar WHERE id_tipe_kamar='" . $safe['id_tipe_kamar'] . "'");
            }
        }

        // 3. INSERT tipe kamar baru dari form
        if (!empty($_POST['tipe_kamar'])) {
            foreach ($_POST['tipe_kamar'] as $i => $namaTipe) {
                $harga = isset($_POST['harga'][$i]) ? intval($_POST['harga'][$i]) : 0;
                $kapasitas = isset($_POST['kapasitas'][$i]) ? intval($_POST['kapasitas'][$i]) : 1;
                $total_unit = isset($_POST['total_unit'][$i]) ? intval($_POST['total_unit'][$i]) : 1;

                $checkExist = mysqli_query($conn, "
            SELECT id_tipe_kamar FROM tipe_kamar 
            WHERE id_penginapan='$id' 
            AND nama_tipe='" . mysqli_real_escape_string($conn, $namaTipe) . "'
        ");

                if (mysqli_num_rows($checkExist) > 0) {
                    $existData = mysqli_fetch_assoc($checkExist);
                    mysqli_query($conn, "
                UPDATE tipe_kamar SET
                    harga_per_malam = '$harga',
                    kapasitas_orang = '$kapasitas',
                    jumlah_kamar = '$total_unit',
                    deskripsi = 'Kamar " . mysqli_real_escape_string($conn, $namaTipe) . "'
                WHERE id_tipe_kamar = '" . $existData['id_tipe_kamar'] . "'
            ");
                } else {
                    mysqli_query($conn, "
                INSERT INTO tipe_kamar
                (id_penginapan,nama_tipe,harga_per_malam,kapasitas_orang,jumlah_kamar,deskripsi)
                VALUES
                (
                    '$id',
                    '" . mysqli_real_escape_string($conn, $namaTipe) . "',
                    '$harga',
                    '$kapasitas',
                    '$total_unit',
                    'Kamar " . mysqli_real_escape_string($conn, $namaTipe) . "'
                )
            ");
                }
            }
        }


        /*  UPDATE HARGA MULAI  */
        mysqli_query($conn, "
            UPDATE penginapan SET harga_mulai = (
                SELECT MIN(harga_per_malam)
                FROM tipe_kamar
                WHERE id_penginapan='$id'
            )
            WHERE id_penginapan='$id'
        ");

        /*  GAMBAR PENGINAPAN  */
        if (!empty($_FILES['gambar_penginapan']['name'][0])) {
            $folder = "../../uploads/penginapan/";
            if (!is_dir($folder))
                mkdir($folder, 0777, true);

            foreach ($_FILES['gambar_penginapan']['tmp_name'] as $i => $tmp) {
                if ($_FILES['gambar_penginapan']['error'][$i] === 0) {
                    $ext = pathinfo($_FILES['gambar_penginapan']['name'][$i], PATHINFO_EXTENSION);
                    $file = uniqid() . "." . $ext;

                    if (move_uploaded_file($tmp, $folder . $file)) {
                        $checkThumbnail = mysqli_query($conn, "SELECT COUNT(*) as total FROM gambar_penginapan WHERE id_penginapan='$id' AND is_thumbnail=1");
                        $thumbCount = mysqli_fetch_assoc($checkThumbnail)['total'];
                        $isThumbnail = ($i == 0 && $thumbCount == 0) ? 1 : 0;

                        mysqli_query($conn, "
                            INSERT INTO gambar_penginapan (id_penginapan, path_gambar, is_thumbnail, created_at)
                            VALUES ('$id','uploads/penginapan/$file',$isThumbnail,NOW())
                        ");
                    }
                }
            }
        }

        mysqli_commit($conn);

        // LOG AKTIVITAS ADMIN
        if (isset($_SESSION['user_id'])) {
            $admin_id = intval($_SESSION['user_id']);
            $aksi = mysqli_real_escape_string($conn, "Update Penginapan");
            $deskripsi = mysqli_real_escape_string($conn, "Admin mengupdate penginapan ID $id ($nama_penginapan)");

            // Cek struktur
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
                    } else if (in_array('target_id', $columns)) {
                        if ($time_column) {
                            $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, target_id, $time_column) 
                                         VALUES ('$admin_id', '$aksi', '$id', NOW())";
                        } else {
                            $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, target_id) 
                                         VALUES ('$admin_id', '$aksi', '$id')";
                        }
                    } else {
                        if ($time_column) {
                            $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, $time_column) 
                                         VALUES ('$admin_id', '$aksi', NOW())";
                        } else {
                            $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi) 
                                         VALUES ('$admin_id', '$aksi')";
                        }
                    }

                    // Execute log query
                    $log_result = mysqli_query($conn, $log_query);

                    // Debug jika gagal
                    if (!$log_result) {
                        error_log("Log aktivitas gagal: " . mysqli_error($conn));
                    }
                }
            }
        }

        header("Location: inventori.php?success=update&tipe=" . strtolower($tipe_penginapan));
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        die("GAGAL UPDATE: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Penginapan - <?= htmlspecialchars($data['nama_penginapan']) ?></title>
    <link rel="icon" type="image/jpeg" href="../../assets/img/logonw.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            background: #fff7ed;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .1);
        }

        h2 {
            margin-bottom: 10px;
            color: #111827;
            font-size: 28px;
            font-weight: 700;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 14px;
        }

        h3 {
            margin-top: 30px;
            margin-bottom: 15px;
            color: #374151;
            font-size: 20px;
            font-weight: 600;
            padding-bottom: 10px;
            border-bottom: 2px solid #fbbf24;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-full {
            grid-column: 1 / -1;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #374151;
            font-size: 14px;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            outline: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: #fbbf24;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        select[multiple] {
            min-height: 150px;
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

        small {
            display: block;
            margin-top: 5px;
            color: #6b7280;
            font-size: 12px;
        }

        .kamar-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 15px;
            border: 2px solid #e5e7eb;
            position: relative;
        }

        .kamar-box:hover {
            border-color: #fbbf24;
        }

        .kamar-box-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .kamar-box-title {
            font-weight: 600;
            color: #111827;
            font-size: 16px;
        }

        .btn-remove-kamar {
            background: #fee2e2;
            color: #991b1b;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-remove-kamar:hover {
            background: #fecaca;
        }

        .kamar-inputs {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .btn-add-kamar {
            background: #fef3c7;
            color: #92400e;
            border: 2px dashed #fbbf24;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 14px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
            margin-top: 10px;
        }

        .btn-add-kamar:hover {
            background: #fde68a;
        }

        .gallery-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .img-preview-item {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .img-preview-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }

        .img-thumbnail-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #fbbf24;
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .btn-delete-img {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s;
            font-size: 14px;
        }

        .img-preview-item:hover .btn-delete-img {
            opacity: 1;
        }

        .btn-delete-img:hover {
            background: rgba(220, 38, 38, 1);
            transform: scale(1.1);
        }

        input[type="file"] {
            padding: 10px;
            cursor: pointer;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e5e7eb;
        }

        button[type="submit"] {
            flex: 1;
            padding: 15px 30px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            color: white;
            transition: all 0.3s;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(251, 191, 36, 0.4);
        }

        .btn-cancel {
            padding: 15px 30px;
            background: #f3f4f6;
            color: #111827;
            border: 2px solid #d1d5db;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-cancel:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .kamar-inputs {
                grid-template-columns: 1fr;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Edit Penginapan</h2>
        <p class="subtitle">Update informasi penginapan: <?= htmlspecialchars($data['nama_penginapan']) ?></p>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_penginapan" value="<?= $id ?>">

            <!-- DATA UTAMA -->
            <h3><i class="fa-solid fa-info-circle"></i> Informasi Dasar</h3>

            <div class="form-grid">
                <div class="form-group form-full">
                    <label>Nama Penginapan *</label>
                    <input type="text" name="nama_penginapan" value="<?= htmlspecialchars($data['nama_penginapan']) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Tipe Penginapan *</label>
                    <select name="tipe_penginapan" required>
                        <option value="homestay" <?= strtolower($data['tipe_penginapan']) === 'homestay' ? 'selected' : '' ?>>Homestay</option>
                        <option value="hotel" <?= strtolower($data['tipe_penginapan']) === 'hotel' ? 'selected' : '' ?>>
                            Hotel</option>
                        <option value="villa" <?= strtolower($data['tipe_penginapan']) === 'villa' ? 'selected' : '' ?>>
                            Villa</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Kabupaten *</label>
                    <select name="id_kabupaten" id="kabupaten" required>
                        <option value="">-- Pilih Kabupaten --</option>
                        <?php
                        mysqli_data_seek($qKabupaten, 0);
                        while ($kb = mysqli_fetch_assoc($qKabupaten)) { ?>
                            <option value="<?= $kb['id_kabupaten'] ?>" <?= $kb['id_kabupaten'] == $data['id_kabupaten'] ? 'selected' : '' ?>>
                                <?= $kb['nama_kabupaten'] ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Kecamatan *</label>
                    <select name="id_kecamatan" id="kecamatan" required>
                        <option value="<?= $data['id_kecamatan'] ?>">Loading...</option>
                    </select>
                </div>

                <div class="form-group form-full">
                    <label>Alamat Lengkap *</label>
                    <textarea name="lokasi" required><?= htmlspecialchars($data['alamat']) ?></textarea>
                </div>

                <div class="form-group form-full">
                    <label>Deskripsi Penginapan *</label>
                    <textarea name="deskripsi" required><?= htmlspecialchars($data['deskripsi']) ?></textarea>
                </div>

                <div class="form-group form-full">
                    <label>Tentang Kami</label>
                    <textarea name="tentang_kami"><?= htmlspecialchars($data['tentang_kami'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- KONTAK -->
            <h3><i class="fa-solid fa-phone"></i> Informasi Kontak</h3>

            <div class="form-grid">
                <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input type="tel" name="kontak" value="<?= htmlspecialchars($kontak_data['telepon'] ?? '') ?>"
                        placeholder="0274-123456">
                </div>

                <div class="form-group">
                    <label>Nomor WhatsApp</label>
                    <input type="tel" name="no_wa" value="<?= htmlspecialchars($kontak_data['whatsapp'] ?? '') ?>"
                        placeholder="08123456789">
                </div>

                <div class="form-group form-full">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($kontak_data['email'] ?? '') ?>"
                        placeholder="info@penginapan.com">
                </div>
            </div>

            <!-- FASILITAS -->
            <h3><i class="fa-solid fa-star"></i> Fasilitas</h3>
            <div class="form-group">
                <select name="fasilitas[]" multiple required>
                    <?php
                    mysqli_data_seek($qFasilitas, 0);
                    while ($f = mysqli_fetch_assoc($qFasilitas)): ?>
                        <option value="<?= $f['id_fasilitas'] ?>" <?= in_array($f['id_fasilitas'], $fasilitasDipilih) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['nama_fasilitas']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <small>Tahan Ctrl/Cmd untuk pilih lebih dari satu fasilitas</small>
            </div>

            <!-- TIPE KAMAR -->
            <h3><i class="fa-solid fa-bed"></i> Tipe Kamar</h3>

            <div id="kamar-container">
                <?php foreach ($tipeKamar as $k): ?>
                    <div class="kamar-box">
                        <div class="kamar-box-header">
                            <div class="kamar-box-title"><?= htmlspecialchars($k['nama_tipe']) ?></div>
                            <button type="button" class="btn-remove-kamar"
                                onclick="this.parentElement.parentElement.remove()">
                                <i class="fa-solid fa-trash"></i> Hapus
                            </button>
                        </div>

                        <div class="kamar-inputs">
                            <div class="form-group">
                                <label>Nama Tipe *</label>
                                <input name="tipe_kamar[]" value="<?= htmlspecialchars($k['nama_tipe']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Harga/Malam (Rp) *</label>
                                <input name="harga[]" type="number" value="<?= $k['harga_per_malam'] ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Kapasitas (Orang) *</label>
                                <input name="kapasitas[]" type="number" value="<?= $k['kapasitas_orang'] ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Total Unit *</label>
                                <input name="total_unit[]" type="number" value="<?= $k['jumlah_kamar'] ?>" required>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn-add-kamar" onclick="tambahKamar()">
                <i class="fa-solid fa-plus"></i> Tambah Tipe Kamar
            </button>

            <!-- GAMBAR PENGINAPAN -->
            <h3><i class="fa-solid fa-images"></i> Galeri Foto Penginapan</h3>

            <div class="gallery-preview" id="gallery-preview">
                <?php
                $qImg = mysqli_query($conn, "SELECT * FROM gambar_penginapan WHERE id_penginapan='$id' ORDER BY is_thumbnail DESC");
                while ($img = mysqli_fetch_assoc($qImg)):
                    ?>
                    <div class="img-preview-item" data-gambar-id="<?= $img['id_gambar'] ?>">
                        <img src="../../<?= htmlspecialchars($img['path_gambar']) ?>" alt="Preview"
                            onerror="this.src='../../uploads/penginapan/default.jpg'">
                        <?php if ($img['is_thumbnail']): ?>
                            <div class="img-thumbnail-badge">Thumbnail</div>
                        <?php endif; ?>
                        <button type="button" class="btn-delete-img" onclick="hapusGambar(<?= $img['id_gambar'] ?>, this)"
                            title="Hapus gambar">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="form-group">
                <label>Tambah Foto Baru (Opsional)</label>
                <input type="file" name="gambar_penginapan[]" multiple accept="image/*">
                <small>Upload foto baru jika diperlukan. Foto pertama yang diupload akan menjadi thumbnail baru.</small>
            </div>

            <!-- BUTTON -->
            <div class="button-group">
                <button name="update" type="submit">
                    <i class="fa-solid fa-save"></i> Simpan Perubahan
                </button>
                <a href="inventori.php" class="btn-cancel">
                    <i class="fa-solid fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>

    <script>
        // Load kecamatan
        document.addEventListener('DOMContentLoaded', function () {
            const kabupaten = document.getElementById('kabupaten');
            const kecamatan = document.getElementById('kecamatan');
            const selectedKecamatan = '<?= $data['id_kecamatan'] ?>';

            if (kabupaten.value) {
                loadKecamatan(kabupaten.value, selectedKecamatan);
            }
        });

        // Event listener untuk perubahan kabupaten
        document.getElementById('kabupaten').addEventListener('change', function () {
            loadKecamatan(this.value, null);
        });

        function loadKecamatan(idKabupaten, selectedId = null) {
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
                    if (selectedId) {
                        kecamatanSelect.value = selectedId;
                    }
                })
                .catch(err => {
                    console.error('Error loading kecamatan:', err);
                    kecamatanSelect.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
                });
        }

        // Tambah tipe kamar baru
        function tambahKamar() {
            const container = document.getElementById('kamar-container');
            const newKamar = document.createElement('div');
            newKamar.className = 'kamar-box';
            newKamar.innerHTML = `
        <div class="kamar-box-header">
            <div class="kamar-box-title">Tipe Kamar Baru</div>
            <button type="button" class="btn-remove-kamar" onclick="this.parentElement.parentElement.remove()">
                <i class="fa-solid fa-trash"></i> Hapus
            </button>
        </div>
        <div class="kamar-inputs">
            <div class="form-group">
                <label>Nama Tipe *</label>
                <input name="tipe_kamar[]" placeholder="Contoh: Deluxe" required>
            </div>
            <div class="form-group">
                <label>Harga/Malam (Rp) *</label>
                <input name="harga[]" type="number" placeholder="500000" required>
            </div>
            <div class="form-group">
                <label>Kapasitas (Orang) *</label>
                <input name="kapasitas[]" type="number" placeholder="2" required>
            </div>
            <div class="form-group">
                <label>Total Unit *</label>
                <input name="total_unit[]" type="number" placeholder="5" required>
            </div>
        </div>
    `;
            container.appendChild(newKamar);
        }

        // HAPUS GAMBAR DENGAN SWEETALERT2
        function hapusGambar(idGambar, btnElement) {
            Swal.fire({
                title: 'Hapus Gambar?',
                text: "Gambar yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="fa-solid fa-trash"></i> Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Tampilkan loading
                    Swal.fire({
                        title: 'Menghapus gambar...',
                        html: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Kirim request AJAX untuk hapus gambar
                    fetch('hapus_gambar.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id_gambar=' + idGambar
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const imgItem = btnElement.closest('.img-preview-item');
                                imgItem.style.transition = 'all 0.3s ease';
                                imgItem.style.opacity = '0';
                                imgItem.style.transform = 'scale(0.8)';

                                setTimeout(() => {
                                    imgItem.remove();

                                    const gallery = document.getElementById('gallery-preview');
                                    if (gallery.children.length === 0) {
                                        gallery.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #9ca3af; padding: 40px;">Belum ada gambar. Silakan upload gambar baru.</p>';
                                    }

                                    // Tampilkan success message
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Berhasil!',
                                        text: 'Gambar berhasil dihapus',
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                }, 300);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal!',
                                    text: 'Gagal menghapus gambar: ' + (data.message || 'Unknown error'),
                                    confirmButtonColor: '#ef4444'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Terjadi kesalahan saat menghapus gambar',
                                confirmButtonColor: '#ef4444'
                            });
                        });
                }
            });
        }
    </script>

</body>

</html>
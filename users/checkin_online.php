<?php
require_once '../config.php';

// Cek apakah pengguna sudah login dan bukan guest
if (!isset($_SESSION['user_id']) || (isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'guest')) {
    // Redirect ke halaman login
    header("Location: ../autentikasi/login.php");
    exit;
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$success_message = '';
$error_message = '';

// STEP 1: Verifikasi Booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verifikasi'])) {
    $kode_booking = mysqli_real_escape_string($conn, trim($_POST['kode_booking']));
    $email_pemesan = mysqli_real_escape_string($conn, trim($_POST['email_pemesan']));

    // Query untuk verifikasi booking 
    $sql = "SELECT 
                b.*,
                p.nama_penginapan,
                tk.nama_tipe,
                u.nama,
                u.email
            FROM booking b
            LEFT JOIN penginapan p ON b.id_penginapan = p.id_penginapan
            LEFT JOIN tipe_kamar tk ON b.id_tipe_kamar = tk.id_tipe_kamar
            LEFT JOIN users u ON b.id_user = u.id_user
            WHERE b.kode_booking = '$kode_booking' 
            AND u.email = '$email_pemesan'
            AND b.status_reservasi = 'dipesan'";

    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $booking = mysqli_fetch_assoc($result);

        // Simpan data booking ke session
        $_SESSION['checkin_booking'] = $booking;

        // Redirect ke step 2
        header("Location: checkin_online.php?step=2");
        exit;
    } else {

        // Cek apakah kode booking ada
        $check_kode = mysqli_query($conn, "SELECT kode_booking, status_reservasi FROM booking WHERE kode_booking = '$kode_booking'");

        if (mysqli_num_rows($check_kode) > 0) {
            $row = mysqli_fetch_assoc($check_kode);
            $status = $row['status_reservasi'];

            if ($status == 'check-in') {
                $error_message = "Booking ini sudah check-in sebelumnya.";
            } elseif ($status == 'selesai') {
                $error_message = "Booking ini sudah selesai.";
            } elseif ($status == 'dibatalkan') {
                $error_message = "Booking ini telah dibatalkan.";
            } elseif ($status == 'menunggu_pembatalan') {
                $error_message = "Booking ini sedang dalam proses pembatalan.";
            } else {
                $error_message = "Email pemesan tidak sesuai dengan data booking, atau booking belum dikonfirmasi. Status saat ini: " . ucfirst($status);
            }
        } else {
            $error_message = "ID Booking tidak ditemukan. Pastikan ID Booking sudah benar.";
        }
    }
}

// STEP 2: Proses Check-in
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkin'])) {
    // Cek apakah ada data booking di session
    if (!isset($_SESSION['checkin_booking'])) {
        header("Location: checkin_online.php?step=1");
        exit;
    }

    $booking = $_SESSION['checkin_booking'];
    $id_booking = $booking['id_booking'];

    // Handle upload KTP
    $foto_ktp = '';
    if (isset($_FILES['foto_ktp']) && $_FILES['foto_ktp']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['foto_ktp']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = 'ktp_' . $id_booking . '_' . time() . '.' . $ext;
            $upload_path = '../uploads/ktp/' . $new_filename;

            // Buat folder jika belum ada
            if (!file_exists('../uploads/ktp/')) {
                mkdir('../uploads/ktp/', 0777, true);
            }

            if (move_uploaded_file($_FILES['foto_ktp']['tmp_name'], $upload_path)) {
                $foto_ktp = $new_filename;
            }
        }
    }

    // Insert ke tabel checkin
    $insert_checkin = "INSERT INTO checkin (
                            id_booking,
                            email_pemesan,
                            foto_ktp,
                            status_checkin,
                            waktu_checkin
                        ) VALUES (
                            $id_booking,
                            '{$booking['email']}',
                            '$foto_ktp',
                            'valid',
                            NOW()
                        )";

    if (mysqli_query($conn, $insert_checkin)) {
        // Update status booking menjadi check-in
        $update_booking = "UPDATE booking SET status_reservasi = 'check-in' WHERE id_booking = $id_booking";
        mysqli_query($conn, $update_booking);

        // Hapus session checkin_booking
        unset($_SESSION['checkin_booking']);

        // Redirect ke riwayat reservasi dengan pesan sukses
        $_SESSION['checkin_success'] = "Check-in berhasil! Status reservasi Anda telah diperbarui.";
        header("Location: riwayat_reservasi.php");
        exit;
    } else {
        $error_message = "Gagal menyimpan data check-in: " . mysqli_error($conn);
    }
}

// Include header
$page_title = 'Check-in Online';
require_once 'header.php';
?>

<style>
    /* ========= MAIN WRAPPER ========= */
    .checkin-wrapper {
        min-height: 80vh;
        background: #f5f5f0;
        padding: 60px 20px;
    }

    .checkin-container {
        max-width: 700px;
        margin: 0 auto;
    }

    .checkin-card {
        background: white;
        border-radius: 20px;
        padding: 50px;
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.1);
        border: 3px solid #f5a742;
    }

    .checkin-title {
        font-size: 32px;
        font-weight: bold;
        color: #8da6daff;
        text-align: center;
        margin-bottom: 40px;
    }

    /* ========= FORM ========= */
    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        font-size: 16px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 10px;
        display: block;
    }

    .form-input {
        width: 100%;
        padding: 16px 20px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 15px;
        transition: all 0.3s;
        font-family: inherit;
        background: #fffef5;
    }

    .form-input:focus {
        outline: none;
        border-color: #8da6daff;
        box-shadow: 0 0 0 4px rgba(141, 166, 218, 0.1);
        background: white;
    }

    .form-input::placeholder {
        color: #999;
    }

    .btn-verify {
        width: 100%;
        padding: 16px;
        background: #8da6daff;
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 30px;
    }

    .btn-verify:hover {
        background: #5073b8ff;
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(141, 166, 218, 0.4);
    }

    /* ========= BOOKING DATA BOX ========= */
    .booking-data-box {
        background: #e8f0fe;
        border: 2px solid #8da6daff;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
    }

    .booking-data-title {
        font-size: 18px;
        font-weight: 700;
        color: #5073b8ff;
        margin-bottom: 15px;
    }

    .booking-data-item {
        font-size: 15px;
        color: #1a1a1a;
        margin-bottom: 8px;
        line-height: 1.6;
    }

    .booking-data-item strong {
        font-weight: 600;
    }

    /* ========= UPLOAD SECTION ========= */
    .upload-section {
        margin-top: 30px;
    }

    .upload-title {
        font-size: 18px;
        font-weight: 700;
        color: #5073b8ff;
        margin-bottom: 15px;
    }

    .file-input-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
    }

    .file-input {
        display: none;
    }

    .file-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 16px 24px;
        background: white;
        border: 2px dashed #8da6daff;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 15px;
        font-weight: 600;
        color: #5073b8ff;
    }

    .file-label:hover {
        background: #e8f0fe;
        border-style: solid;
    }

    .file-name {
        margin-top: 10px;
        font-size: 14px;
        color: #666;
        text-align: center;
    }

    .file-note {
        font-size: 13px;
        color: #999;
        margin-top: 8px;
        text-align: center;
    }

    .btn-checkin {
        width: 100%;
        padding: 18px;
        background: #fde047;
        color: #1a1a1a;
        border: none;
        border-radius: 12px;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 30px;
    }

    .btn-checkin:hover {
        background: #fbbf24;
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(251, 191, 36, 0.4);
    }

    /* ========= SUCCESS PAGE ========= */
    .success-icon {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 30px;
        font-size: 50px;
        color: white;
        animation: scaleIn 0.5s ease;
    }

    @keyframes scaleIn {
        from {
            transform: scale(0);
        }

        to {
            transform: scale(1);
        }
    }

    .success-title {
        font-size: 28px;
        font-weight: bold;
        color: #10b981;
        text-align: center;
        margin-bottom: 15px;
    }

    .success-text {
        font-size: 16px;
        color: #666;
        text-align: center;
        margin-bottom: 30px;
        line-height: 1.6;
    }

    .btn-riwayat {
        width: 100%;
        padding: 16px;
        background: #8da6daff;
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn-riwayat:hover {
        background: #5073b8ff;
        transform: translateY(-2px);
    }

    /* ========= ALERT ========= */
    .alert {
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 25px;
        font-size: 15px;
        font-weight: 500;
    }

    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 2px solid #fecaca;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 2px solid #a7f3d0;
    }
</style>

<div class="checkin-wrapper">
    <div class="checkin-container">
        <div class="checkin-card">
            <h1 class="checkin-title">Check-in Online Cepat</h1>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="bi bi-exclamation-circle"></i>
                    <?= $error_message ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <!-- STEP 1: VERIFIKASI BOOKING -->
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">ID Booking/Reservasi</label>
                        <input type="text" name="kode_booking" class="form-input" placeholder="Contoh: JS-123456" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Pemesan</label>
                        <input type="email" name="email_pemesan" class="form-input"
                            placeholder="Contoh: yogyastay@gmail.com" required>
                    </div>

                    <button type="submit" name="verifikasi" class="btn-verify">
                        <i class="bi bi-check-circle"></i>
                        <span>Verifikasi Booking</span>
                    </button>
                </form>

            <?php elseif ($step == 2 && isset($_SESSION['checkin_booking'])): ?>
                <!-- STEP 2: DATA BOOKING & UPLOAD KTP -->
                <?php
                $booking = $_SESSION['checkin_booking'];
                $checkin_date = date('d F Y', strtotime($booking['tanggal_checkin']));
                ?>

                <div class="booking-data-box">
                    <div class="booking-data-title">Data Booking Ditemukan:</div>
                    <div class="booking-data-item"><strong>Nama Pemesan:</strong> <?= htmlspecialchars($booking['nama']) ?>
                    </div>
                    <div class="booking-data-item"><strong>Tanggal Check-in:</strong> <?= $checkin_date ?></div>
                    <div class="booking-data-item"><strong>Lokasi:</strong>
                        <?= htmlspecialchars($booking['nama_penginapan']) ?></div>
                    <div class="booking-data-item"><strong>Tipe Kamar:</strong>
                        <?= htmlspecialchars($booking['nama_tipe'] ?? 'Standard Room') ?></div>
                </div>

                <form method="POST" enctype="multipart/form-data">
                    <div class="upload-section">
                        <div class="upload-title">Lengkapi Data Verifikasi:</div>

                        <div class="file-input-wrapper">
                            <input type="file" name="foto_ktp" id="foto_ktp" class="file-input" accept=".jpg,.jpeg,.png"
                                required>
                            <label for="foto_ktp" class="file-label">
                                <i class="bi bi-file-earmark-arrow-up"></i>
                                <span id="file-label-text">Pilih File KTP/Passport</span>
                            </label>
                            <div class="file-name" id="file-name">Tidak ada file yang dipilih</div>
                            <div class="file-note">Unggah scan KTP/Passport Anda (jpg, jpeg, png - Max 2MB)</div>
                        </div>
                    </div>

                    <button type="submit" name="checkin" class="btn-checkin">
                        <i class="bi bi-check-circle-fill"></i>
                        Selesaikan Check-in
                    </button>
                </form>

            <?php else: ?>
                <!-- REDIRECT JIKA AKSES TIDAK VALID -->
                <?php
                header("Location: checkin_online.php?step=1");
                exit;
                ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Handle file input
    document.getElementById('foto_ktp')?.addEventListener('change', function (e) {
        const fileName = e.target.files[0]?.name || 'Tidak ada file yang dipilih';
        document.getElementById('file-name').textContent = fileName;

        if (e.target.files[0]) {
            document.getElementById('file-label-text').textContent = 'File Dipilih ✓';
        } else {
            document.getElementById('file-label-text').textContent = 'Pilih File KTP/Passport';
        }
    });
</script>

<?php
// Include footer
require_once 'footer.php';
?>
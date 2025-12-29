<?php
require_once '../config.php';

// Cek koneksi database
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Ambil parameter dari URL
$id_penginapan = isset($_GET['id_penginapan']) ? intval($_GET['id_penginapan']) : 0;
$id_tipe_kamar = isset($_GET['id_tipe_kamar']) ? intval($_GET['id_tipe_kamar']) : 0;
$checkin = isset($_GET['checkin']) && !empty($_GET['checkin']) ? $_GET['checkin'] : date('Y-m-d');
$checkout = isset($_GET['checkout']) && !empty($_GET['checkout']) ? $_GET['checkout'] : date('Y-m-d', strtotime('+1 day'));
$jumlah_kamar = isset($_GET['jumlah_kamar']) ? intval($_GET['jumlah_kamar']) : 1;

// Validasi ID penginapan
if ($id_penginapan <= 0) {
    header("Location: penginapan.php");
    exit;
}

// Ambil data penginapan
$sql_penginapan = "SELECT * FROM penginapan WHERE id_penginapan = $id_penginapan";
$result_penginapan = mysqli_query($conn, $sql_penginapan);
$penginapan = mysqli_fetch_assoc($result_penginapan);

// Validasi penginapan
if (!$penginapan) {
    header("Location: penginapan.php");
    exit;
}

// Ambil data tipe kamar jika ada
$tipe_kamar = null;
if ($id_tipe_kamar > 0) {
    $sql_kamar = "SELECT * FROM tipe_kamar WHERE id_tipe_kamar = $id_tipe_kamar";
    $result_kamar = mysqli_query($conn, $sql_kamar);
    $tipe_kamar = mysqli_fetch_assoc($result_kamar);
}

// Hitung jumlah malam
$jumlah_malam = 1;
if (!empty($checkin) && !empty($checkout)) {
    try {
        $date1 = new DateTime($checkin);
        $date2 = new DateTime($checkout);
        $interval = $date1->diff($date2);
        $jumlah_malam = $interval->days > 0 ? $interval->days : 1;
    } catch (Exception $e) {
        $jumlah_malam = 1;
    }
}

// Hitung total harga
$harga_per_malam = 0;
if ($tipe_kamar && isset($tipe_kamar['harga'])) {
    $harga_per_malam = $tipe_kamar['harga'];
} elseif (isset($penginapan['harga_mulai'])) {
    $harga_per_malam = $penginapan['harga_mulai'];
}
$total_harga = $harga_per_malam * $jumlah_malam * $jumlah_kamar;

// Tentukan step (default step 1)
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// Simpan data step 1 ke session
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['step1'])) {
    $_SESSION['booking_data'] = [
        'nama_lengkap' => $_POST['nama_lengkap'],
        'email' => $_POST['email'],
        'telepon' => $_POST['telepon'],
        'jumlah_kamar' => $_POST['jumlah_kamar']
    ];
    header("Location: booking.php?id_penginapan=$id_penginapan&id_tipe_kamar=$id_tipe_kamar&checkin=$checkin&checkout=$checkout&jumlah_kamar={$_POST['jumlah_kamar']}&step=2");
    exit;
}

// Proses pembayaran (step 2 -> insert database -> redirect success)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['konfirmasi_pembayaran'])) {
    // Cek apakah user sudah login
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../autentikasi/login.php");
        exit;
    }
    
    // Cek apakah ada data booking di session
    if (!isset($_SESSION['booking_data'])) {
        header("Location: penginapan.php");
        exit;
    }
    
    // Ambil data dari POST dan SESSION
    $id_user = $_SESSION['user_id'];
    $id_penginapan_post = intval($_POST['id_penginapan']);
    $id_tipe_kamar_post = intval($_POST['id_tipe_kamar']);
    $tanggal_checkin = mysqli_real_escape_string($conn, $_POST['checkin']);
    $tanggal_checkout = mysqli_real_escape_string($conn, $_POST['checkout']);
    $jumlah_kamar_post = intval($_POST['jumlah_kamar']);
    $metode_pembayaran = mysqli_real_escape_string($conn, $_POST['metode_pembayaran']);
    
    // Data dari session step 1
    $booking_data = $_SESSION['booking_data'];
    $nama_lengkap = mysqli_real_escape_string($conn, $booking_data['nama_lengkap']);
    $email = mysqli_real_escape_string($conn, $booking_data['email']);
    $telepon = mysqli_real_escape_string($conn, $booking_data['telepon']);
    
    // Generate kode booking (format: JS-XXXXXX)
    $kode_booking = 'JS-' . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Cek apakah kode booking sudah ada
    $check_kode = mysqli_query($conn, "SELECT kode_booking FROM booking WHERE kode_booking = '$kode_booking'");
    while (mysqli_num_rows($check_kode) > 0) {
        $kode_booking = 'JS-' . str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $check_kode = mysqli_query($conn, "SELECT kode_booking FROM booking WHERE kode_booking = '$kode_booking'");
    }
    
    // Jumlah orang (default jumlah kamar * 2)
    $jumlah_orang = $jumlah_kamar_post * 2;
    
    // Insert ke tabel booking
    $insert_booking = "INSERT INTO booking (
                            kode_booking,
                            id_user,
                            id_penginapan,
                            id_tipe_kamar,
                            tanggal_checkin,
                            tanggal_checkout,
                            jumlah_kamar,
                            jumlah_orang,
                            total_harga,
                            status_reservasi,
                            created_at
                        ) VALUES (
                            '$kode_booking',
                            $id_user,
                            $id_penginapan_post,
                            $id_tipe_kamar_post,
                            '$tanggal_checkin',
                            '$tanggal_checkout',
                            $jumlah_kamar_post,
                            $jumlah_orang,
                            $total_harga,
                            'confirmed',
                            NOW()
                        )";
    
    if (mysqli_query($conn, $insert_booking)) {
        // Hapus session booking_data
        unset($_SESSION['booking_data']);
        
        // Redirect ke booking_success
        header("Location: booking_success.php?kode=$kode_booking");
        exit;
    } else {
        die("Error menyimpan booking: " . mysqli_error($conn));
    }
}

// Include header
require_once 'header.php';
?>

<style>
    /* ========= BOOKING CONTAINER ========= */
    .booking-wrapper {
        min-height: 60vh;
        background: #f5f5f0;
        padding: 40px 20px;
    }

    .booking-container {
        max-width: 600px;
        margin: 0 auto;
    }

    .booking-card {
        background: white;
        border-radius: 20px;
        padding: 50px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .booking-title {
        font-size: 30px;
        font-weight: bold;
        color: #1a1a1a;
        text-align: center;
        margin-bottom: 30px;
    }

    /* ========= STEPPER ========= */
    .stepper {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        margin-bottom: 50px;
    }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e5e7eb;
        color: #999;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: bold;
        transition: all 0.3s;
    }

    .step-circle.active {
        background: #fde047;
        color: #1a1a1a;
    }

    .step-circle.completed {
        background: #10b981;
        color: white;
    }

    .step-label {
        font-size: 14px;
        color: #666;
        font-weight: 600;
    }

    .step-label.active {
        color: #1a1a1a;
    }

    .step-line {
        width: 120px;
        height: 4px;
        background: #e5e7eb;
        border-radius: 2px;
        margin-top: -20px;
    }

    .step-line.active {
        background: #fde047;
    }

    /* ========= FORM ========= */
    .booking-form {
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-label {
        font-size: 15px;
        font-weight: 600;
        color: #333;
    }

    .form-input,
    .form-select {
        padding: 14px 18px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 15px;
        transition: all 0.3s;
        font-family: inherit;
    }

    .form-input:focus,
    .form-select:focus {
        outline: none;
        border-color: #fde047;
        box-shadow: 0 0 0 4px rgba(253, 224, 71, 0.1);
    }

    .form-input::placeholder {
        color: #999;
    }

    .form-input[disabled] {
        background: #d6d6d6ff;
        color: #666;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .input-with-icon {
        position: relative;
    }

    .input-icon {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 20px;
        color: #999;
        pointer-events: none;
    }

    /* ========= PRICE BOX ========= */
    .price-box {
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
    }

    .price-title {
        font-size: 18px;
        font-weight: bold;
        color: #1a1a1a;
        margin-bottom: 5px;
    }

    .price-amount {
        font-size: 32px;
        font-weight: bold;
        color: #ef4444;
    }

    .price-note {
        font-size: 14px;
        color: #666;
        margin-top: 8px;
    }

    /* ========= PAYMENT DROPDOWN ========= */
    .payment-section {
        margin-top: 30px;
    }

    .payment-title {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 12px;
    }

    .payment-dropdown {
        border: 2px solid #e5e7eb;
        border-radius: 14px;
        background: white;
        overflow: hidden;
    }

    .payment-header {
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        font-size: 15px;
        font-weight: 600;
    }

    .payment-header:hover {
        background: #fef9e7;
    }

    .payment-arrow {
        transition: transform 0.3s ease;
    }

    .payment-arrow.rotate {
        transform: rotate(180deg);
    }

    .payment-list {
        display: none;
        border-top: 2px solid #e5e7eb;
    }

    .payment-list.active {
        display: block;
    }

    .payment-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 20px;
        cursor: pointer;
        font-size: 14px;
    }

    .payment-item:hover {
        background: #f9fafb;
    }

    .payment-item input {
        accent-color: #fde047;
    }

    /* ========= BUTTONS ========= */
    .button-group {
        display: flex;
        gap: 15px;
        margin-top: 40px;
    }

    .btn {
        flex: 1;
        padding: 16px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-secondary {
        background: #8da6daff;
        color: white;
    }

    .btn-secondary:hover {
        background: #5073b8ff;
        transform: translateY(-2px);
    }

    .btn-primary {
        background: #8da6daff;
        color: white;
    }

    .btn-primary:hover {
        background: #354c7bff;
        transform: translateY(-2px);
    }

    .btn-primary.full {
        flex: none;
        width: 100%;
    }

    /* ========= RESPONSIVE ========= */
    @media (max-width: 768px) {
        .booking-card {
            padding: 30px 20px;
        }

        .booking-title {
            font-size: 24px;
        }

        .stepper {
            gap: 10px;
        }

        .step-circle {
            width: 50px;
            height: 50px;
            font-size: 20px;
        }

        .step-line {
            width: 60px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .button-group {
            flex-direction: column;
        }
    }
</style>

<div class="booking-wrapper">
    <div class="booking-container">
        <div class="booking-card">
            <h1 class="booking-title">Form Pemesanan Hotel</h1>

            <!-- Stepper -->
            <div class="stepper">
                <div class="step-item">
                    <div class="step-circle <?= $step == 1 ? 'active' : ($step > 1 ? 'completed' : '') ?>">
                        <?= $step > 1 ? '✓' : '1' ?>
                    </div>
                    <div class="step-label <?= $step == 1 ? 'active' : '' ?>">Data Kontak</div>
                </div>

                <div class="step-line <?= $step > 1 ? 'active' : '' ?>"></div>

                <div class="step-item">
                    <div class="step-circle <?= $step == 2 ? 'active' : '' ?>">2</div>
                    <div class="step-label <?= $step == 2 ? 'active' : '' ?>">Pembayaran</div>
                </div>
            </div>

            <?php if ($step == 1) { ?>
                <!-- STEP 1: DATA KONTAK -->
                <form method="POST" class="booking-form">
                    <input type="hidden" name="step1" value="1">

                    <div class="form-group">
                        <label class="form-label">Hotel</label>
                        <input type="text" class="form-input"
                            value="<?= htmlspecialchars($penginapan['nama_penginapan']) ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Jenis Kamar</label>
                        <input type="text" class="form-input"
                            value="<?= $tipe_kamar ? htmlspecialchars($tipe_kamar['nama_tipe']) : 'Standard Room' ?>"
                            disabled>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-input" placeholder="Masukkan nama lengkap Anda"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Aktif</label>
                        <input type="email" name="email" class="form-input" placeholder="contoh@email.com" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nomor Telpon</label>
                            <input type="tel" name="telepon" class="form-input" placeholder="08xxxxxxxxxx" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jumlah Kamar</label>
                            <div class="input-with-icon">
                                <input type="number" name="jumlah_kamar" class="form-input" value="<?= $jumlah_kamar ?>"
                                    min="1" required>
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">
                            Lanjutkan
                            <span>→</span>
                        </button>
                    </div>
                </form>

            <?php } else if ($step == 2) { ?>
                <!-- STEP 2: PEMBAYARAN -->
                <form method="POST" class="booking-form">
                    <input type="hidden" name="konfirmasi_pembayaran" value="1">
                    <input type="hidden" name="id_penginapan" value="<?= $id_penginapan ?>">
                    <input type="hidden" name="id_tipe_kamar" value="<?= $id_tipe_kamar ?>">
                    <input type="hidden" name="checkin" value="<?= $checkin ?>">
                    <input type="hidden" name="checkout" value="<?= $checkout ?>">
                    <input type="hidden" name="jumlah_kamar" value="<?= $jumlah_kamar ?>">

                    <div class="price-box">
                        <div class="price-title">Total Tagihan: <span class="price-amount">Rp
                            <?= number_format($total_harga, 0, ',', '.') ?></span></div>
                        <div class="price-note">Periksa data Anda sebelum pembayaran</div>
                    </div>

                    <div class="payment-section">
                        <div class="payment-title">Metode Pembayaran</div>

                        <div class="payment-dropdown">
                            <div class="payment-header" onclick="togglePaymentDropdown()">
                                <span id="payment-selected">Transfer Bank</span>
                                <span class="payment-arrow" id="payment-arrow">▼</span>
                            </div>

                            <div class="payment-list" id="payment-list">
                                <label class="payment-item">
                                    <input type="radio" name="metode_pembayaran" value="Transfer Bank" checked>
                                    <span>Transfer Bank</span>
                                </label>

                                <label class="payment-item">
                                    <input type="radio" name="metode_pembayaran" value="Kartu Kredit/Debit">
                                    <span>Kartu Kredit / Debit</span>
                                </label>

                                <label class="payment-item">
                                    <input type="radio" name="metode_pembayaran" value="E-Wallet">
                                    <span>E-Wallet</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="button" class="btn btn-secondary"
                            onclick="window.location.href='booking.php?id_penginapan=<?= $id_penginapan ?>&id_tipe_kamar=<?= $id_tipe_kamar ?>&checkin=<?= $checkin ?>&checkout=<?= $checkout ?>&jumlah_kamar=<?= $jumlah_kamar ?>&step=1'">
                            <span>←</span>
                            Kembali
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Konfirmasi
                            <span>→</span>
                        </button>
                    </div>
                </form>
            <?php } ?>

        </div>
    </div>
</div>

<script>
    function togglePaymentDropdown() {
        const list = document.getElementById('payment-list');
        const arrow = document.getElementById('payment-arrow');

        list.classList.toggle('active');
        arrow.classList.toggle('rotate');
    }

    // Update teks saat memilih metode
    document.querySelectorAll('input[name="metode_pembayaran"]').forEach(item => {
        item.addEventListener('change', function () {
            document.getElementById('payment-selected').textContent =
                this.nextElementSibling.textContent;

            document.getElementById('payment-list').classList.remove('active');
            document.getElementById('payment-arrow').classList.remove('rotate');
        });
    });

    // Tutup dropdown jika klik di luar
    document.addEventListener('click', function (e) {
        const dropdown = document.querySelector('.payment-dropdown');
        if (dropdown && !dropdown.contains(e.target)) {
            document.getElementById('payment-list')?.classList.remove('active');
            document.getElementById('payment-arrow')?.classList.remove('rotate');
        }
    });
</script>

<?php
// Include footer
require_once 'footer.php';
?>
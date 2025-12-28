<?php
require_once '../config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../autentikasi/login.php");
    exit;
}

$kode_booking = isset($_GET['kode']) ? mysqli_real_escape_string($conn, $_GET['kode']) : '';

// Ambil data booking dari database
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
        WHERE b.kode_booking = '$kode_booking' AND b.id_user = {$_SESSION['user_id']}";

$result = mysqli_query($conn, $sql);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    header("Location: penginapan.php");
    exit;
}

// Include header
require_once 'header.php';
?>

<style>
.success-wrapper {
    min-height: 70vh;
    background: #f5f5f0;
    padding: 60px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.success-card {
    max-width: 600px;
    background: white;
    border-radius: 20px;
    padding: 50px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    text-align: center;
}

.success-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 50px;
    margin: 0 auto 30px;
    animation: scaleIn 0.5s ease-out;
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
    font-size: 32px;
    font-weight: bold;
    color: #10b981;
    margin-bottom: 15px;
}

.success-text {
    font-size: 16px;
    color: #666;
    margin-bottom: 30px;
    line-height: 1.6;
}

.booking-info {
    background: #f9fafb;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    text-align: left;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #e5e7eb;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.info-value {
    font-size: 14px;
    color: #1a1a1a;
    font-weight: 700;
}

.kode-booking {
    font-size: 24px;
    color: #f5a742;
    font-weight: bold;
    margin: 20px 0;
}

.button-group {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn {
    padding: 14px 30px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #8da6daff;
    color: white;
    border: none;
}

.btn-primary:hover {
    background: #5073b8ff;
    transform: translateY(-2px);
}

.btn-secondary {
    background: white;
    color: #8da6daff;
    border: 2px solid #8da6daff;
}

.btn-secondary:hover {
    background: #e8eef9;
}
</style>

<div class="success-wrapper">
    <div class="success-card">
        <div class="success-icon">✓</div>
        
        <h1 class="success-title">Booking Berhasil!</h1>
        <p class="success-text">
            Terima kasih! Reservasi Anda telah berhasil dibuat.<br>
            Silakan simpan kode booking Anda untuk keperluan check-in.
        </p>
        
        <div class="kode-booking"><?= htmlspecialchars($booking['kode_booking']) ?></div>
        
        <div class="booking-info">
            <div class="info-row">
                <span class="info-label">Hotel</span>
                <span class="info-value"><?= htmlspecialchars($booking['nama_penginapan']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Tipe Kamar</span>
                <span class="info-value"><?= htmlspecialchars($booking['nama_tipe'] ?? 'Standard Room') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Check-in</span>
                <span class="info-value"><?= date('d M Y', strtotime($booking['tanggal_checkin'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Check-out</span>
                <span class="info-value"><?= date('d M Y', strtotime($booking['tanggal_checkout'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Jumlah Kamar</span>
                <span class="info-value"><?= $booking['jumlah_kamar'] ?> Kamar</span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Pembayaran</span>
                <span class="info-value">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status</span>
                <span class="info-value" style="color: #f59e0b;">Dikonfirmasi - Menunggu Check-in</span>
            </div>
        </div>
        
        <div class="button-group">
            <a href="riwayat_reservasi.php" class="btn btn-primary">Lihat Riwayat Reservasi</a>
            <a href="penginapan.php" class="btn btn-secondary">Cari Penginapan Lagi</a>
        </div>
    </div>
</div>

<?php
// Include footer
require_once 'footer.php';
?>
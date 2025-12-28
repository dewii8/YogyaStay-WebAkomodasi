<?php
require_once '../config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../autentikasi/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$id_booking = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validasi booking
if ($id_booking <= 0) {
    $_SESSION['error_message'] = "ID Booking tidak valid.";
    header("Location: riwayat_reservasi.php");
    exit;
}

// Ambil data booking
$sql_booking = "SELECT 
                    b.*,
                    p.nama_penginapan,
                    tk.nama_tipe,
                    u.nama,
                    u.email
                FROM booking b
                LEFT JOIN penginapan p ON b.id_penginapan = p.id_penginapan
                LEFT JOIN tipe_kamar tk ON b.id_tipe_kamar = tk.id_tipe_kamar
                LEFT JOIN users u ON b.id_user = u.id_user
                WHERE b.id_booking = $id_booking 
                AND b.id_user = $user_id
                AND b.status_reservasi IN ('confirmed', 'check-in')";

$result_booking = mysqli_query($conn, $sql_booking);
$booking = mysqli_fetch_assoc($result_booking);

// Validasi booking
if (!$booking) {
    $_SESSION['error_message'] = "Booking tidak ditemukan atau tidak dapat dibatalkan.";
    header("Location: riwayat_reservasi.php");
    exit;
}

// Include header
require_once 'header.php';
?>

<style>
.pembatalan-wrapper {
    min-height: 80vh;
    background: #f5f5f0;
    padding: 60px 20px;
}

.pembatalan-container {
    max-width: 800px;
    margin: 0 auto;
}

.pembatalan-card {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 5px 30px rgba(0,0,0,0.1);
}

.pembatalan-title {
    font-size: 32px;
    font-weight: bold;
    color: #ef4444;
    text-align: center;
    margin-bottom: 10px;
}

.pembatalan-subtitle {
    font-size: 16px;
    color: #666;
    text-align: center;
    margin-bottom: 40px;
}

.warning-box {
    background: #fef3c7;
    border: 2px solid #fbbf24;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
}

.warning-title {
    font-size: 18px;
    font-weight: 700;
    color: #92400e;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.warning-text {
    font-size: 14px;
    color: #78350f;
    line-height: 1.6;
}

.warning-text ul {
    margin: 10px 0 0 20px;
}

.booking-info-box {
    background: #e8f0fe;
    border: 2px solid #8da6daff;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
}

.booking-info-title {
    font-size: 18px;
    font-weight: 700;
    color: #5073b8ff;
    margin-bottom: 15px;
}

.booking-info-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #d6e4f7;
    font-size: 15px;
}

.booking-info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: #666;
    font-weight: 500;
}

.info-value {
    color: #1a1a1a;
    font-weight: 700;
}

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

.form-label .required {
    color: #ef4444;
}

.form-textarea {
    width: 100%;
    min-height: 150px;
    padding: 16px 20px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 15px;
    font-family: inherit;
    resize: vertical;
    transition: all 0.3s;
}

.form-textarea:focus {
    outline: none;
    border-color: #ef4444;
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
}

.form-textarea::placeholder {
    color: #999;
}

.char-count {
    font-size: 13px;
    color: #999;
    text-align: right;
    margin-top: 5px;
}

.refund-info {
    background: #d1fae5;
    border: 2px solid #10b981;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
}

.refund-title {
    font-size: 16px;
    font-weight: 700;
    color: #065f46;
    margin-bottom: 15px;
}

.refund-calculation {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.refund-row {
    display: flex;
    justify-content: space-between;
    font-size: 15px;
}

.refund-row.total {
    border-top: 2px solid #10b981;
    padding-top: 15px;
    margin-top: 10px;
}

.refund-label {
    color: #065f46;
}

.refund-value {
    font-weight: 700;
    color: #065f46;
}

.refund-row.total .refund-value {
    font-size: 22px;
    color: #10b981;
}

.button-group {
    display: flex;
    gap: 15px;
    margin-top: 30px;
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
    text-decoration: none;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-cancel {
    background: #e5e7eb;
    color: #666;
}

.btn-cancel:hover {
    background: #d1d5db;
    transform: translateY(-2px);
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-2px);
}

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
    font-weight: 600;
}

.alert-error::before {
    content: "⚠️ ";
    font-size: 18px;
}

@media (max-width: 768px) {
    .pembatalan-card {
        padding: 25px;
    }
    
    .button-group {
        flex-direction: column;
    }
}
</style>

<div class="pembatalan-wrapper">
    <div class="pembatalan-container">
        <div class="pembatalan-card">
            <h1 class="pembatalan-title">Pembatalan Reservasi</h1>
            <p class="pembatalan-subtitle">Silakan isi form di bawah untuk membatalkan reservasi Anda</p>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error"><?= $_SESSION['error_message'] ?></div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <div class="warning-box">
                <div class="warning-title">
                    <span>⚠️</span>
                    <span>Perhatian!</span>
                </div>
                <div class="warning-text">
                    Kebijakan pembatalan:
                    <ul>
                        <li>Biaya pembatalan sebesar <strong>10%</strong> dari total pembayaran</li>
                        <li>Proses refund memakan waktu <strong>3-7 hari kerja</strong></li>
                        <li>Pembatalan tidak dapat diubah setelah diajukan</li>
                    </ul>
                </div>
            </div>
            
            <div class="booking-info-box">
                <div class="booking-info-title">Detail Reservasi</div>
                <div class="booking-info-row">
                    <span class="info-label">Kode Booking</span>
                    <span class="info-value"><?= htmlspecialchars($booking['kode_booking']) ?></span>
                </div>
                <div class="booking-info-row">
                    <span class="info-label">Hotel</span>
                    <span class="info-value"><?= htmlspecialchars($booking['nama_penginapan']) ?></span>
                </div>
                <div class="booking-info-row">
                    <span class="info-label">Tipe Kamar</span>
                    <span class="info-value"><?= htmlspecialchars($booking['nama_tipe'] ?? 'Standard Room') ?></span>
                </div>
                <div class="booking-info-row">
                    <span class="info-label">Check-in</span>
                    <span class="info-value"><?= date('d M Y', strtotime($booking['tanggal_checkin'])) ?></span>
                </div>
                <div class="booking-info-row">
                    <span class="info-label">Check-out</span>
                    <span class="info-value"><?= date('d M Y', strtotime($booking['tanggal_checkout'])) ?></span>
                </div>
                <div class="booking-info-row">
                    <span class="info-label">Total Pembayaran</span>
                    <span class="info-value">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></span>
                </div>
            </div>
            
            <?php
            $biaya_pembatalan = $booking['total_harga'] * 0.10;
            $total_refund = $booking['total_harga'] - $biaya_pembatalan;
            ?>
            
            <div class="refund-info">
                <div class="refund-title">Perkiraan Refund</div>
                <div class="refund-calculation">
                    <div class="refund-row">
                        <span class="refund-label">Total Pembayaran</span>
                        <span class="refund-value">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></span>
                    </div>
                    <div class="refund-row">
                        <span class="refund-label">Biaya Pembatalan (10%)</span>
                        <span class="refund-value" style="color: #ef4444;">-Rp <?= number_format($biaya_pembatalan, 0, ',', '.') ?></span>
                    </div>
                    <div class="refund-row total">
                        <span class="refund-label">Total Refund</span>
                        <span class="refund-value">Rp <?= number_format($total_refund, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="handle_pembatalan.php" id="form-pembatalan">
                <input type="hidden" name="id_booking" value="<?= $id_booking ?>">
                <div class="form-group">
                    <label class="form-label">
                        Alasan Pembatalan <span class="required">*</span>
                    </label>
                    <textarea name="alasan_pembatalan" id="alasan_pembatalan" class="form-textarea" 
                              placeholder="Jelaskan alasan Anda membatalkan reservasi (min. 20 karakter)" 
                              maxlength="500" required></textarea>
                    <div class="char-count">
                        <span id="char-count">0</span>/500 karakter
                    </div>
                </div>
                
                <div class="button-group">
                    <a href="riwayat_reservasi.php" class="btn btn-cancel">
                        <span>←</span>
                        <span>Batal</span>
                    </a>
                    <button type="submit" name="submit_pembatalan" class="btn btn-danger" id="btn-submit">
                        <span>✓</span>
                        <span>Ajukan Pembatalan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Character counter
const textarea = document.getElementById('alasan_pembatalan');
const charCount = document.getElementById('char-count');

textarea.addEventListener('input', function() {
    charCount.textContent = this.value.length;
});

// Form validation
document.getElementById('form-pembatalan').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const alasan = textarea.value.trim();
    
    if (alasan.length < 20) {
        Swal.fire({
            icon: 'error',
            title: 'Alasan Terlalu Singkat',
            text: 'Alasan pembatalan minimal 20 karakter',
            confirmButtonColor: '#ef4444'
        });
        return;
    }
    
    Swal.fire({
        icon: 'warning',
        title: 'Konfirmasi Pembatalan',
        html: `
            <p>Anda yakin ingin membatalkan reservasi ini?</p>
            <p style="color: #ef4444; font-weight: bold; margin-top: 10px;">
                Biaya pembatalan: Rp <?= number_format($biaya_pembatalan, 0, ',', '.') ?>
            </p>
            <p style="color: #10b981; font-weight: bold;">
                Total refund: Rp <?= number_format($total_refund, 0, ',', '.') ?>
            </p>
        `,
        showCancelButton: true,
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Tidak',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});
</script>

<?php
require_once 'footer.php';
?>
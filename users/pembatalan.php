<?php
require_once '../config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../autentikasi/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil ID booking dari parameter
$id_booking = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_booking == 0) {
    header('Location: riwayat_reservasi.php');
    exit();
}

// Ambil detail reservasi
$query = "SELECT b.*, p.nama_penginapan, tk.nama_tipe, u.nama as nama_pemesan
          FROM booking b 
          LEFT JOIN penginapan p ON b.id_penginapan = p.id_penginapan 
          LEFT JOIN tipe_kamar tk ON b.id_tipe_kamar = tk.id_tipe_kamar
          LEFT JOIN users u ON b.id_user = u.id_user
          WHERE b.id_booking = ? AND b.id_user = ? AND b.status_reservasi != 'dibatalkan'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $id_booking, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);

if (!$booking) {
    header('Location: riwayat_reservasi.php');
    exit();
}

// Cek apakah sudah check-in atau selesai
if ($booking['status_reservasi'] == 'check-in' || $booking['status_reservasi'] == 'selesai') {
    $_SESSION['error'] = "Tidak dapat membatalkan reservasi yang sudah check-in";
    header('Location: riwayat_reservasi.php');
    exit();
}

// Hitung biaya pembatalan (10%)
$biaya_pembatalan = $booking['total_harga'] * 0.10;
$total_refund = $booking['total_harga'] - $biaya_pembatalan;

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $alasan = trim($_POST['alasan']);
    
    if (empty($alasan)) {
        $error = "Alasan pembatalan harus diisi (minimal 20 karakter)";
    } elseif (strlen($alasan) < 20) {
        $error = "Alasan pembatalan minimal 20 karakter";
    } else {
        // Cek apakah tabel pembatalan ada
        $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'pembatalan'");
        if (mysqli_num_rows($check_table) == 0) {
            $error = "Tabel pembatalan belum dibuat. Silakan jalankan database_pembatalan.sql terlebih dahulu.";
        } else {
            // Insert ke tabel pembatalan
            $query = "INSERT INTO pembatalan (id_booking, id_user, alasan, biaya_pembatalan, total_refund, tanggal_pengajuan, status_pembatalan) 
                      VALUES (?, ?, ?, ?, ?, NOW(), 'diproses')";
            $stmt = mysqli_prepare($conn, $query);
            
            if ($stmt === false) {
                $error = "Error prepare statement: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param($stmt, "iisdd", $id_booking, $user_id, $alasan, $biaya_pembatalan, $total_refund);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Update status booking
                    $update = "UPDATE booking SET status_reservasi = 'menunggu_pembatalan' WHERE id_booking = ?";
                    $stmt_update = mysqli_prepare($conn, $update);
                    
                    if ($stmt_update) {
                        mysqli_stmt_bind_param($stmt_update, "i", $id_booking);
                        mysqli_stmt_execute($stmt_update);
                        mysqli_stmt_close($stmt_update);
                    }
                    
                    mysqli_stmt_close($stmt);
                    
                    $_SESSION['success'] = "Pengajuan pembatalan berhasil dikirim";
                    $_SESSION['show_success_alert'] = true;
                    header('Location: status_pembatalan.php');
                    exit();
                } else {
                    $error = "Gagal mengajukan pembatalan: " . mysqli_stmt_error($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

// Fungsi format tanggal
function formatTanggalIndo($tanggal) {
    if (empty($tanggal)) return '-';
    $bulan = array(
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    );
    $split = explode('-', $tanggal);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

$page_title = 'Pembatalan Reservasi';
require_once 'header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* ========= MAIN LAYOUT ========= */
.pembatalan-wrapper {
    min-height: 80vh;
    background: #f5f5f0;
    padding: 40px 20px;
}

.pembatalan-container {
    max-width: 700px;
    margin: 0 auto;
}

/* ========= CARD STYLES ========= */
.pembatalan-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.card-header-custom {
    background: linear-gradient(135deg, #8da6daff 0%, #5073b8ff 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.card-header-custom h1 {
    font-size: 28px;
    margin-bottom: 8px;
    font-weight: bold;
}

.card-header-custom p {
    font-size: 14px;
    opacity: 0.9;
    margin: 0;
}

.card-body-custom {
    padding: 35px;
}

/* ========= WARNING BOX ========= */
.warning-box {
    background: #fef3c7;
    border-left: 4px solid #f5a742;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
}

.warning-box h3 {
    color: #92400e;
    font-size: 16px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
}

.warning-box p {
    margin-bottom: 10px;
    color: #92400e;
    font-size: 14px;
    font-weight: 600;
}

.warning-box ul {
    margin-left: 20px;
    color: #92400e;
    font-size: 14px;
    line-height: 1.8;
}

.warning-box ul li {
    margin-bottom: 5px;
}

/* ========= DETAIL BOX ========= */
.detail-box {
    background: #e8eef9;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
}

.detail-box h3 {
    color: #5073b8ff;
    font-size: 16px;
    margin-bottom: 18px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #c9d6ed;
    font-size: 14px;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item label {
    color: #555;
    font-weight: 600;
}

.detail-item span {
    color: #1a1a1a;
    font-weight: 700;
    text-align: right;
}

/* ========= REFUND BOX ========= */
.refund-box {
    background: #d1fae5;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
}

.refund-box h3 {
    color: #065f46;
    font-size: 16px;
    margin-bottom: 18px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}

.refund-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    font-size: 14px;
}

.refund-item label {
    color: #065f46;
    font-weight: 600;
}

.refund-item span {
    font-weight: 700;
    color: #065f46;
}

.refund-item.negative span {
    color: #dc2626;
}

.refund-item.total {
    border-top: 2px solid #10b981;
    padding-top: 15px;
    margin-top: 10px;
}

.refund-item.total label {
    font-size: 15px;
    font-weight: 700;
}

.refund-item.total span {
    font-size: 22px;
    color: #10b981;
}

/* ========= FORM STYLES ========= */
.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    color: #1a1a1a;
    font-weight: 700;
    font-size: 14px;
}

.form-group label .required {
    color: #dc2626;
}

.form-group textarea {
    width: 100%;
    padding: 15px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-size: 14px;
    resize: vertical;
    min-height: 130px;
    transition: border-color 0.3s;
}

.form-group textarea:focus {
    outline: none;
    border-color: #8da6daff;
}

.char-count {
    text-align: right;
    font-size: 12px;
    color: #888;
    margin-top: 8px;
}

/* ========= BUTTON STYLES ========= */
.button-group {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn-custom {
    flex: 1;
    padding: 14px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-cancel-action {
    background: #f5f5f5;
    color: #333;
    border: 2px solid #e5e7eb;
}

.btn-cancel-action:hover {
    background: #e5e7eb;
}

.btn-submit {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
}

/* ========= CUSTOM SWAL STYLES ========= */
.swal2-popup {
    border-radius: 20px;
    padding: 30px;
}

.swal2-title {
    font-size: 24px;
    font-weight: 700;
}

.swal2-html-container {
    font-size: 15px;
    line-height: 1.6;
}

.swal2-confirm {
    border-radius: 10px;
    padding: 12px 30px;
    font-weight: 600;
}

.swal2-cancel {
    border-radius: 10px;
    padding: 12px 30px;
    font-weight: 600;
}

/* ========= RESPONSIVE ========= */
@media (max-width: 768px) {
    .pembatalan-container {
        padding: 0 15px;
    }
    
    .card-body-custom {
        padding: 25px 20px;
    }
    
    .button-group {
        flex-direction: column;
    }
}
</style>

<div class="pembatalan-wrapper">
    <div class="pembatalan-container">
        
        <div class="pembatalan-card">
            <div class="card-header-custom">
                <h1>Pembatalan Reservasi</h1>
                <p>Silakan isi form di bawah untuk membatalkan reservasi Anda</p>
            </div>
            
            <div class="card-body-custom">
                
                <div class="warning-box">
                    <h3>
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Perhatian!
                    </h3>
                    <p>Kebijakan pembatalan:</p>
                    <ul>
                        <li>Biaya pembatalan sebesar <strong>10%</strong> dari total pembayaran</li>
                        <li>Proses refund memakan waktu <strong>3-7 hari kerja</strong></li>
                        <li>Pembatalan tidak dapat <strong>diundur setelahnya</strong></li>
                    </ul>
                </div>
                
                <div class="detail-box">
                    <h3>
                        <i class="bi bi-info-circle-fill"></i>
                        Detail Reservasi
                    </h3>
                    <div class="detail-item">
                        <label>Kode Booking</label>
                        <span><?= htmlspecialchars($booking['kode_booking']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Penginapan</label>
                        <span><?= htmlspecialchars($booking['nama_penginapan']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Tipe Kamar</label>
                        <span><?= htmlspecialchars($booking['nama_tipe']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Check-in</label>
                        <span><?= formatTanggalIndo($booking['tanggal_checkin']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Check-out</label>
                        <span><?= formatTanggalIndo($booking['tanggal_checkout']) ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Total Pembayaran</label>
                        <span>Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></span>
                    </div>
                </div>
                
                <div class="refund-box">
                    <h3>
                        <i class="bi bi-cash-stack"></i>
                        Perkiraan Refund
                    </h3>
                    <div class="refund-item">
                        <label>Total Pembayaran</label>
                        <span>Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></span>
                    </div>
                    <div class="refund-item negative">
                        <label>Biaya Pembatalan (10%)</label>
                        <span>-Rp <?= number_format($biaya_pembatalan, 0, ',', '.') ?></span>
                    </div>
                    <div class="refund-item total">
                        <label>Total Refund</label>
                        <span>Rp <?= number_format($total_refund, 0, ',', '.') ?></span>
                    </div>
                </div>
                
                <form method="POST" id="cancelForm">
                    <div class="form-group">
                        <label>
                            Alasan Pembatalan <span class="required">*</span>
                        </label>
                        <textarea 
                            name="alasan" 
                            id="alasan" 
                            placeholder="Jelaskan alasan Anda membatalkan reservasi (minimal 20 karakter)"
                            maxlength="500"
                            required
                        ><?= isset($_POST['alasan']) ? htmlspecialchars($_POST['alasan']) : '' ?></textarea>
                        <div class="char-count">
                            <span id="charCount">0</span>/500 karakter
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn-custom btn-cancel-action" id="btnKembali">
                            <i class="bi bi-arrow-left"></i>
                            Kembali
                        </button>
                        <button type="button" class="btn-custom btn-submit" id="btnSubmit">
                            <i class="bi bi-send-fill"></i>
                            Ajukan Pembatalan
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
</div>

<script>
// Data untuk SweetAlert
const bookingData = {
    kodeBooking: '<?= htmlspecialchars($booking['kode_booking']) ?>',
    namaPenginapan: '<?= htmlspecialchars($booking['nama_penginapan']) ?>',
    totalHarga: <?= $booking['total_harga'] ?>,
    biayaPembatalan: <?= $biaya_pembatalan ?>,
    totalRefund: <?= $total_refund ?>
};

// Character counter
const textarea = document.getElementById('alasan');
const charCount = document.getElementById('charCount');

textarea.addEventListener('input', function() {
    charCount.textContent = this.value.length;
});

// Initial count
charCount.textContent = textarea.value.length;

// Flag untuk mengecek apakah user klik tombol Kembali
let isNavigatingAway = false;

// Tombol Kembali dengan konfirmasi SweetAlert2
document.getElementById('btnKembali').addEventListener('click', function() {
    const alasan = textarea.value.trim();
    
    // Jika ada teks yang sudah diisi, tampilkan konfirmasi
    if (alasan.length > 0) {
        Swal.fire({
            title: 'Kembali ke Halaman Riwayat?',
            html: `
                <p style="margin-bottom: 15px;">Data yang sudah Anda isi akan hilang.</p>
                <p style="color: #666; font-size: 14px;">Apakah Anda yakin ingin kembali?</p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#8da6daff',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="bi bi-check-circle"></i> Ya, Kembali',
            cancelButtonText: '<i class="bi bi-x-circle"></i> Tidak',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                isNavigatingAway = true; // Set flag supaya beforeunload tidak trigger
                window.location.href = 'riwayat_reservasi.php';
            }
        });
    } else {
        // Jika tidak ada data, langsung kembali
        isNavigatingAway = true;
        window.location.href = 'riwayat_reservasi.php';
    }
});

// Tombol Submit dengan konfirmasi SweetAlert2
document.getElementById('btnSubmit').addEventListener('click', function() {
    const alasan = textarea.value.trim();
    
    // Validasi panjang alasan
    if (alasan.length === 0) {
        Swal.fire({
            title: 'Alasan Tidak Boleh Kosong!',
            text: 'Silakan isi alasan pembatalan terlebih dahulu.',
            icon: 'warning',
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'OK'
        }).then(() => {
            textarea.focus();
        });
        return;
    }
    
    if (alasan.length < 20) {
        Swal.fire({
            title: 'Alasan Terlalu Singkat!',
            html: `
                <p style="margin-bottom: 10px;">Alasan pembatalan minimal <strong>20 karakter</strong>.</p>
                <p style="color: #666; font-size: 14px;">Saat ini: <strong>${alasan.length}</strong> karakter</p>
            `,
            icon: 'warning',
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'OK'
        }).then(() => {
            textarea.focus();
        });
        return;
    }
    
    // Konfirmasi pembatalan dengan detail lengkap
    Swal.fire({
        title: 'Konfirmasi Pembatalan Reservasi',
        html: `
            <div style="text-align: left; padding: 15px;">
                <div style="background: #f9fafb; padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                    <p style="margin-bottom: 8px;"><strong>Kode Booking:</strong> ${bookingData.kodeBooking}</p>
                    <p style="margin-bottom: 0;"><strong>Penginapan:</strong> ${bookingData.namaPenginapan}</p>
                </div>
                
                <div style="background: #fee2e2; padding: 15px; border-radius: 10px; border-left: 4px solid #ef4444; margin-bottom: 15px;">
                    <p style="color: #991b1b; margin-bottom: 8px; font-weight: 600;">
                        <i class="bi bi-exclamation-triangle-fill"></i> Perincian Biaya
                    </p>
                    <p style="margin-bottom: 6px; color: #666;">Total Pembayaran: <strong>Rp ${bookingData.totalHarga.toLocaleString('id-ID')}</strong></p>
                    <p style="margin-bottom: 6px; color: #ef4444;">Biaya Pembatalan (10%): <strong>-Rp ${bookingData.biayaPembatalan.toLocaleString('id-ID')}</strong></p>
                    <hr style="margin: 10px 0; border-color: #fca5a5;">
                    <p style="margin-bottom: 0; color: #059669; font-size: 16px;">
                        <strong>Dana Dikembalikan: Rp ${bookingData.totalRefund.toLocaleString('id-ID')}</strong>
                    </p>
                </div>
                
                <div style="background: #fef3c7; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                    <p style="color: #92400e; font-size: 13px; margin-bottom: 5px; font-weight: 600;">
                        <i class="bi bi-info-circle-fill"></i> Alasan Pembatalan:
                    </p>
                    <p style="color: #666; font-size: 13px; font-style: italic; margin: 0;">
                        "${alasan}"
                    </p>
                </div>
                
                <p style="color: #666; font-size: 12px; text-align: center; margin-top: 15px;">
                    <i class="bi bi-clock-history"></i> Refund akan diproses dalam 3-7 hari kerja
                </p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="bi bi-check-circle"></i> Ya, Ajukan Pembatalan',
        cancelButtonText: '<i class="bi bi-x-circle"></i> Batal',
        reverseButtons: true,
        width: '600px',
        customClass: {
            popup: 'swal-custom-popup'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            isNavigatingAway = true; // Set flag supaya beforeunload tidak trigger
            
            // Show loading
            Swal.fire({
                title: 'Mengirim Pengajuan...',
                html: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit form
            document.getElementById('cancelForm').submit();
        }
    });
});

<?php if (isset($error)): ?>
// Tampilkan error dengan SweetAlert2
Swal.fire({
    title: 'Gagal Mengajukan Pembatalan!',
    html: `
        <div style="text-align: left; padding: 10px;">
            <p style="color: #666; margin-bottom: 15px;"><?= addslashes($error) ?></p>
            <hr style="margin: 15px 0; border-color: #fee2e2;">
            <p style="color: #999; font-size: 13px; text-align: center;">
                <i class="bi bi-info-circle"></i> Silakan coba lagi atau hubungi customer service
            </p>
        </div>
    `,
    icon: 'error',
    confirmButtonColor: '#ef4444',
    confirmButtonText: 'Tutup'
});
<?php endif; ?>

// Prevent accidental page leave (HANYA jika TIDAK navigasi via tombol)
window.addEventListener('beforeunload', function(e) {
    // Cek flag - jika user klik tombol, jangan tampilkan warning
    if (isNavigatingAway) {
        return undefined;
    }
    
    const alasan = textarea.value.trim();
    if (alasan.length > 0) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});
</script>

<?php
require_once 'footer.php';
?>
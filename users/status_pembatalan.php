<?php
require_once '../config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../autentikasi/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil data user
$user_query = "SELECT nama, email FROM users WHERE id_user = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

$user_name = $user_data['nama'] ?? 'User';
$user_email = $user_data['email'] ?? 'user@gmail.com';

// Filter status
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'semua';

// Ambil data pembatalan user
$query = "SELECT p.*, b.kode_booking, b.tanggal_checkin, b.tanggal_checkout, b.jumlah_kamar, b.jumlah_orang,
          pen.nama_penginapan, tk.nama_tipe, u.nama as nama_pemesan
          FROM pembatalan p
          JOIN booking b ON p.id_booking = b.id_booking
          JOIN penginapan pen ON b.id_penginapan = pen.id_penginapan
          LEFT JOIN tipe_kamar tk ON b.id_tipe_kamar = tk.id_tipe_kamar
          JOIN users u ON p.id_user = u.id_user
          WHERE p.id_user = $user_id";

if ($filter != 'semua') {
    $query .= " AND p.status_pembatalan = '$filter'";
}

$query .= " ORDER BY p.tanggal_pengajuan DESC";

$result = mysqli_query($conn, $query);
$pembatalan_list = mysqli_fetch_all($result, MYSQLI_ASSOC);

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

$page_title = 'Status Pembatalan';
require_once 'header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* ========= BASE STYLES ========= */
* {
    box-sizing: border-box;
}

.profile-wrapper {
    min-height: 80vh;
    background: #f5f5f0;
    padding: 40px 20px;
}

.profile-container {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
}

/* ========= SIDEBAR PROFILE ========= */
.profile-sidebar {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    height: fit-content;
    position: sticky;
    top: 20px;
}

.profile-header {
    text-align: center;
    padding-bottom: 25px;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 25px;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8da6daff 0%, #5073b8ff 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
    margin: 0 auto 15px;
}

.profile-name {
    font-size: 20px;
    font-weight: bold;
    color: #1a1a1a;
    margin-bottom: 5px;
    word-break: break-word;
}

.profile-email {
    font-size: 13px;
    color: #666;
    word-break: break-all;
}

.profile-menu {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    color: #666;
    font-size: 15px;
    font-weight: 500;
}

.menu-item:hover {
    background: #f9fafb;
    color: #8da6daff;
}

.menu-item.active {
    background: #e8eef9;
    color: #5073b8ff;
    font-weight: 600;
}

.btn-logout {
    margin-top: 20px;
    width: 100%;
    padding: 12px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-logout:hover {
    background: #dc2626;
    transform: translateY(-2px);
}

/* ========= MAIN CONTENT ========= */
.reservasi-content {
    background: white;
    border-radius: 20px;
    padding: 35px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 3px solid #8da6daff;
}

.content-title {
    font-size: 28px;
    font-weight: bold;
    color: #1a1a1a;
    margin: 0;
}

.content-subtitle {
    font-size: 14px;
    color: #666;
    margin-top: 5px;
}

.filter-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 10px 20px;
    border: 2px solid #e5e7eb;
    background: white;
    border-radius: 10px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    color: #666;
    transition: all 0.3s;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}

.filter-btn:hover {
    border-color: #8da6daff;
    color: #8da6daff;
}

.filter-btn.active {
    background: #8da6daff;
    border-color: #8da6daff;
    color: white;
}

/* ========= CANCELLATION CARDS ========= */
.reservation-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.reservation-card {
    border: 2px solid #e5e7eb;
    border-radius: 15px;
    padding: 25px;
    transition: all 0.3s;
    position: relative;
}

.reservation-card:hover {
    border-color: #f5a742;
    box-shadow: 0 5px 20px rgba(245, 167, 66, 0.2);
}

.reservation-card.status-diproses {
    border-color: #3b82f6;
}

.reservation-card.status-disetujui {
    border-color: #10b981;
}

.reservation-card.status-ditolak {
    border-color: #ef4444;
}

.card-header {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    padding-right: 140px;
}

.hotel-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #f5a742 0%, #f8c471 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    flex-shrink: 0;
}

.card-info {
    flex: 1;
    min-width: 0;
}

.room-name {
    font-size: 20px;
    font-weight: bold;
    color: #1a1a1a;
    margin-bottom: 4px;
    word-wrap: break-word;
}

.hotel-name {
    font-size: 16px;
    font-weight: 600;
    color: #f5a742;
    margin-bottom: 4px;
    word-wrap: break-word;
}

.pemesan-info {
    font-size: 13px;
    color: #666;
    word-wrap: break-word;
}

.status-badge {
    position: absolute;
    top: 25px;
    right: 25px;
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 6px;
}

.status-badge.diproses {
    background: #dbeafe;
    color: #1e40af;
}

.status-badge.disetujui {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.ditolak {
    background: #fee2e2;
    color: #991b1b;
}

/* ========= CANCELLATION NOTICE ========= */
.cancellation-notice {
    background: #fee2e2;
    border-left: 4px solid #ef4444;
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.cancellation-notice h4 {
    color: #991b1b;
    font-size: 14px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 700;
}

.cancellation-notice p {
    color: #dc2626;
    font-size: 13px;
    margin-bottom: 5px;
    word-wrap: break-word;
}

.cancellation-notice strong {
    font-weight: 700;
}

.card-details {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    padding: 20px;
    background: #f9fafb;
    border-radius: 12px;
    margin-bottom: 20px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.detail-icon-box {
    display: flex;
    align-items: center;
    gap: 8px;
}

.detail-icon {
    font-size: 20px;
}

.detail-label {
    font-size: 12px;
    color: #666;
    font-weight: 600;
}

.detail-value {
    font-size: 15px;
    color: #1a1a1a;
    font-weight: 700;
    word-wrap: break-word;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.booking-id {
    font-size: 13px;
    color: #666;
}

.booking-id strong {
    color: #1a1a1a;
    font-weight: 700;
}

.refund-info {
    text-align: right;
}

.refund-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}

.refund-amount {
    font-size: 18px;
    font-weight: 700;
    color: #10b981;
}

.cancellation-fee {
    font-size: 12px;
    color: #ef4444;
    margin-top: 2px;
}

.refund-status {
    background: #fef3c7;
    padding: 12px;
    border-radius: 8px;
    margin-top: 15px;
    font-size: 13px;
    color: #92400e;
    text-align: center;
    font-weight: 600;
}

.refund-status.success {
    background: #d1fae5;
    color: #065f46;
}

/* ========= EMPTY STATE ========= */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 80px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-title {
    font-size: 24px;
    font-weight: bold;
    color: #1a1a1a;
    margin-bottom: 10px;
}

.empty-text {
    font-size: 16px;
    color: #666;
}

/* Alert */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    font-size: 15px;
    font-weight: 500;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 2px solid #a7f3d0;
}

/* ========= RESPONSIVE DESIGN ========= */

/* Tablet Landscape & Desktop Kecil (992px - 1200px) */
@media (max-width: 1200px) {
    .profile-container {
        grid-template-columns: 280px 1fr;
        gap: 25px;
    }
    
    .card-details {
        gap: 15px;
    }
    
    .content-title {
        font-size: 26px;
    }
}

/* Tablet Portrait (768px - 991px) */
@media (max-width: 991px) {
    .profile-wrapper {
        padding: 30px 15px;
    }
    
    .profile-container {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .profile-sidebar {
        position: static;
        padding: 25px;
    }
    
    .reservasi-content {
        padding: 25px;
    }
    
    .content-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .filter-buttons {
        width: 100%;
        justify-content: flex-start;
    }
    
    .card-details {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        padding: 15px;
    }
    
    .card-header {
        padding-right: 0;
        flex-direction: column;
        gap: 15px;
    }
    
    .status-badge {
        position: static;
        align-self: flex-start;
        margin-bottom: 10px;
    }
    
    .hotel-icon {
        width: 50px;
        height: 50px;
        font-size: 24px;
    }
    
    .room-name {
        font-size: 18px;
    }
    
    .hotel-name {
        font-size: 15px;
    }
}

/* Mobile Landscape (576px - 767px) */
@media (max-width: 767px) {
    .profile-wrapper {
        padding: 20px 10px;
    }
    
    .profile-sidebar {
        padding: 20px;
    }
    
    .profile-avatar {
        width: 70px;
        height: 70px;
        font-size: 28px;
    }
    
    .profile-name {
        font-size: 18px;
    }
    
    .menu-item {
        padding: 12px 14px;
        font-size: 14px;
    }
    
    .reservasi-content {
        padding: 20px;
    }
    
    .content-title {
        font-size: 22px;
    }
    
    .content-subtitle {
        font-size: 13px;
    }
    
    .filter-btn {
        padding: 8px 16px;
        font-size: 13px;
    }
    
    .reservation-card {
        padding: 20px;
    }
    
    .card-details {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .detail-item {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
    
    .detail-icon-box {
        flex: 1;
    }
    
    .detail-value {
        text-align: right;
    }
    
    .card-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .refund-info {
        text-align: left;
        width: 100%;
    }
    
    .empty-icon {
        font-size: 60px;
    }
    
    .empty-title {
        font-size: 20px;
    }
    
    .empty-text {
        font-size: 14px;
    }
}

/* Mobile Portrait (< 576px) */
@media (max-width: 575px) {
    .profile-wrapper {
        padding: 15px 10px;
    }
    
    .profile-sidebar {
        padding: 15px;
        border-radius: 15px;
    }
    
    .profile-header {
        padding-bottom: 15px;
        margin-bottom: 15px;
    }
    
    .profile-avatar {
        width: 60px;
        height: 60px;
        font-size: 24px;
    }
    
    .profile-name {
        font-size: 16px;
    }
    
    .profile-email {
        font-size: 12px;
    }
    
    .menu-item {
        padding: 10px 12px;
        font-size: 13px;
        gap: 10px;
    }
    
    .btn-logout {
        padding: 10px;
        font-size: 13px;
    }
    
    .reservasi-content {
        padding: 15px;
        border-radius: 15px;
    }
    
    .content-header {
        margin-bottom: 20px;
        padding-bottom: 15px;
    }
    
    .content-title {
        font-size: 20px;
    }
    
    .content-subtitle {
        font-size: 12px;
    }
    
    .filter-buttons {
        gap: 8px;
    }
    
    .filter-btn {
        padding: 8px 12px;
        font-size: 12px;
        gap: 4px;
    }
    
    .filter-btn span {
        display: none;
    }
    
    .filter-btn i {
        margin: 0;
    }
    
    .reservation-card {
        padding: 15px;
        border-radius: 12px;
    }
    
    .status-badge {
        padding: 6px 12px;
        font-size: 11px;
        gap: 4px;
    }
    
    .hotel-icon {
        width: 45px;
        height: 45px;
        font-size: 20px;
    }
    
    .room-name {
        font-size: 16px;
    }
    
    .hotel-name {
        font-size: 14px;
    }
    
    .pemesan-info {
        font-size: 12px;
    }
    
    .cancellation-notice {
        padding: 12px 15px;
    }
    
    .cancellation-notice h4 {
        font-size: 13px;
    }
    
    .cancellation-notice p {
        font-size: 12px;
    }
    
    .card-details {
        padding: 12px;
        gap: 10px;
    }
    
    .detail-icon {
        font-size: 18px;
    }
    
    .detail-label {
        font-size: 11px;
    }
    
    .detail-value {
        font-size: 13px;
    }
    
    .card-footer {
        padding-top: 15px;
        gap: 12px;
    }
    
    .booking-id {
        font-size: 12px;
    }
    
    .refund-label {
        font-size: 11px;
    }
    
    .refund-amount {
        font-size: 16px;
    }
    
    .cancellation-fee {
        font-size: 11px;
    }
    
    .refund-status {
        padding: 10px;
        font-size: 12px;
    }
    
    .alert {
        padding: 12px 15px;
        font-size: 13px;
    }
    
    .empty-state {
        padding: 40px 15px;
    }
    
    .empty-icon {
        font-size: 50px;
    }
    
    .empty-title {
        font-size: 18px;
    }
    
    .empty-text {
        font-size: 13px;
    }
}

/* Extra Small Mobile (< 400px) */
@media (max-width: 399px) {
    .content-title {
        font-size: 18px;
    }
    
    .filter-btn {
        padding: 6px 10px;
        font-size: 11px;
    }
    
    .room-name {
        font-size: 15px;
    }
    
    .hotel-name {
        font-size: 13px;
    }
    
    .detail-value {
        font-size: 12px;
    }
    
    .refund-amount {
        font-size: 15px;
    }
}
</style>

<div class="profile-wrapper">
    <div class="profile-container">
        
        <!-- Sidebar Profile -->
        <div class="profile-sidebar">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user_name, 0, 2)) ?>
                </div>
                <div class="profile-name"><?= htmlspecialchars($user_name) ?></div>
                <div class="profile-email"><?= htmlspecialchars($user_email) ?></div>
            </div>
            
            <div class="profile-menu">
                <a href="riwayat_reservasi.php" class="menu-item">
                    <i class="bi bi-clock-history"></i>
                    <span>Riwayat Reservasi</span>
                </a>
                <a href="profil.php" class="menu-item">
                    <i class="bi bi-pencil-square"></i>
                    <span>Edit Data Pribadi</span>
                </a>
                <a href="status_pembatalan.php" class="menu-item active">
                    <i class="bi bi-x-circle"></i>
                    <span>Status Pembatalan</span>
                </a>
            </div>
            
            <button class="btn-logout" onclick="confirmLogout()">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar</span>
            </button>
        </div>
        
        <!-- Main Content -->
        <div class="reservasi-content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success'] ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <div class="content-header">
                <div>
                    <h2 class="content-title">Status Permohonan Pembatalan</h2>
                    <p class="content-subtitle">Total <?= count($pembatalan_list) ?> pembatalan ditemukan</p>
                </div>
                <div class="filter-buttons">
                    <a href="?filter=semua" class="filter-btn <?= $filter == 'semua' ? 'active' : '' ?>">
                        <i class="bi bi-list-ul"></i>
                        <span>Semua</span>
                    </a>
                    <a href="?filter=diproses" class="filter-btn <?= $filter == 'diproses' ? 'active' : '' ?>">
                        <i class="bi bi-clock"></i>
                        <span>Diproses</span>
                    </a>
                    <a href="?filter=ditolak" class="filter-btn <?= $filter == 'ditolak' ? 'active' : '' ?>">
                        <i class="bi bi-x-circle"></i>
                        <span>Ditolak</span>
                    </a>
                    <a href="?filter=disetujui" class="filter-btn <?= $filter == 'disetujui' ? 'active' : '' ?>">
                        <i class="bi bi-check-circle"></i>
                        <span>Selesai</span>
                    </a>
                </div>
            </div>
            
            <div class="reservation-list">
                <?php if (empty($pembatalan_list)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                        <h3 class="empty-title">Tidak Ada Pembatalan</h3>
                        <p class="empty-text">Anda belum memiliki riwayat pembatalan reservasi</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pembatalan_list as $pembatalan): ?>
                        <div class="reservation-card status-<?= $pembatalan['status_pembatalan'] ?>">
                            <span class="status-badge <?= $pembatalan['status_pembatalan'] ?>">
                                <?php if ($pembatalan['status_pembatalan'] == 'diproses'): ?>
                                    <i class="bi bi-clock"></i> Diproses
                                <?php elseif ($pembatalan['status_pembatalan'] == 'disetujui'): ?>
                                    <i class="bi bi-check-circle"></i> Disetujui
                                <?php else: ?>
                                    <i class="bi bi-x-circle"></i> Ditolak
                                <?php endif; ?>
                            </span>
                            
                            <div class="card-header">
                                <div class="hotel-icon"><i class="bi bi-building"></i></div>
                                <div class="card-info">
                                    <div class="room-name"><?= htmlspecialchars($pembatalan['nama_tipe']) ?></div>
                                    <div class="hotel-name"><?= htmlspecialchars($pembatalan['nama_penginapan']) ?></div>
                                    <div class="pemesan-info">Pemesan: <?= htmlspecialchars($pembatalan['nama_pemesan']) ?></div>
                                </div>
                            </div>
                            
                            <?php if ($pembatalan['status_pembatalan'] == 'ditolak'): ?>
                                <div class="cancellation-notice">
                                    <h4>
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                        Tanggal Pembatalan
                                    </h4>
                                    <p><?= date('d F Y', strtotime($pembatalan['tanggal_diproses'])) ?></p>
                                    <p style="margin-top: 8px;"><strong>Alasan Pembatalan:</strong></p>
                                    <p><?= htmlspecialchars($pembatalan['alasan_admin'] ?? $pembatalan['alasan']) ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-details">
                                <div class="detail-item">
                                    <div class="detail-icon-box">
                                        <i class="bi bi-calendar-check detail-icon"></i>
                                        <span class="detail-label">Check-In</span>
                                    </div>
                                    <div class="detail-value"><?= formatTanggalIndo($pembatalan['tanggal_checkin']) ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-icon-box">
                                        <i class="bi bi-calendar-x detail-icon"></i>
                                        <span class="detail-label">Check-Out</span>
                                    </div>
                                    <div class="detail-value"><?= formatTanggalIndo($pembatalan['tanggal_checkout']) ?></div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-icon-box">
                                        <i class="bi bi-door-closed detail-icon"></i>
                                        <span class="detail-label">Jumlah Kamar</span>
                                    </div>
                                    <div class="detail-value"><?= $pembatalan['jumlah_kamar'] ?> Kamar</div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-icon-box">
                                        <i class="bi bi-people detail-icon"></i>
                                        <span class="detail-label">Jumlah Tamu</span>
                                    </div>
                                    <div class="detail-value"><?= $pembatalan['jumlah_orang'] ?> Orang</div>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <div class="booking-id">
                                    # ID: <strong><?= htmlspecialchars($pembatalan['kode_booking']) ?></strong>
                                </div>
                                
                                <div class="refund-info">
                                    <p class="refund-label">
                                        <?= $pembatalan['status_pembatalan'] == 'disetujui' ? 'Total Refund' : 'Biaya Pembatalan' ?>
                                    </p>
                                    <?php if ($pembatalan['status_pembatalan'] == 'ditolak'): ?>
                                        <div class="refund-amount" style="color: #ef4444;">
                                            -Rp <?= number_format($pembatalan['biaya_pembatalan'], 0, ',', '.') ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="refund-amount">
                                            Rp <?= number_format($pembatalan['total_refund'], 0, ',', '.') ?>
                                        </div>
                                        <?php if ($pembatalan['status_pembatalan'] == 'diproses'): ?>
                                            <p class="cancellation-fee">Biaya: -Rp <?= number_format($pembatalan['biaya_pembatalan'], 0, ',', '.') ?></p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($pembatalan['status_pembatalan'] == 'diproses'): ?>
                                <div class="refund-status">
                                    <i class="bi bi-clock-history"></i> Refund sedang diproses
                                </div>
                            <?php elseif ($pembatalan['status_pembatalan'] == 'disetujui'): ?>
                                <div class="refund-status success">
                                    <i class="bi bi-check-circle"></i> Refund telah berhasil diproses pada <?= date('d M Y', strtotime($pembatalan['tanggal_diproses'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Konfirmasi logout dengan SweetAlert2
function confirmLogout() {
    Swal.fire({
        title: 'Keluar dari Akun?',
        text: 'Apakah kamu yakin ingin keluar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#5073b8ff',
        confirmButtonText: 'Ya, Keluar',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Berhasil Keluar!',
                text: 'Sampai jumpa lagi',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = '../autentikasi/logout.php';
            });
        }
    });
}
</script>

<?php
require_once 'footer.php';
?>
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
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'semua';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 5;
$offset = ($page - 1) * $items_per_page;

// Build WHERE clause
$where_status = "";
if ($filter_status != 'semua') {
    if ($filter_status == 'aktif') {
        $where_status = "AND b.status_reservasi IN ('confirmed', 'check-in')";
    } elseif ($filter_status == 'selesai') {
        $where_status = "AND b.status_reservasi = 'selesai'";
    } elseif ($filter_status == 'dibatalkan') {
        $where_status = "AND b.status_reservasi IN ('dibatalkan', 'menunggu_pembatalan')";
    }
}

// Count total reservasi
$count_sql = "SELECT COUNT(*) as total 
              FROM booking b
              WHERE b.id_user = $user_id $where_status";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total_results = $count_row['total'];
$total_pages = ceil($total_results / $items_per_page);

// Query booking dengan kolom dapat_batalkan
$sql = "SELECT 
            b.id_booking,
            b.kode_booking,
            b.tanggal_checkin,
            b.tanggal_checkout,
            b.jumlah_kamar,
            b.jumlah_orang,
            b.total_harga,
            b.status_reservasi,
            b.created_at,
            p.nama_penginapan,
            tk.nama_tipe,
            u.nama as nama_pemesan,
            CASE 
                WHEN b.status_reservasi IN ('check-in', 'selesai') THEN 'sudah_checkin'
                WHEN b.status_reservasi = 'menunggu_pembatalan' THEN 'menunggu_pembatalan'
                WHEN b.status_reservasi = 'dibatalkan' THEN 'dibatalkan'
                ELSE 'dapat_dibatalkan'
            END as dapat_batalkan
        FROM booking b
        LEFT JOIN penginapan p ON b.id_penginapan = p.id_penginapan
        LEFT JOIN tipe_kamar tk ON b.id_tipe_kamar = tk.id_tipe_kamar
        LEFT JOIN users u ON b.id_user = u.id_user
        WHERE b.id_user = $user_id $where_status
        ORDER BY b.created_at DESC
        LIMIT $items_per_page OFFSET $offset";

$result = mysqli_query($conn, $sql);

// Fungsi format tanggal Indonesia
function formatTanggalIndonesia($tanggal) {
    if (empty($tanggal)) return '-';
    
    $bulan = array(
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    );
    
    $split = explode('-', $tanggal);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

$page_title = 'Riwayat Reservasi';
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

/* ========= RESERVATION CARDS ========= */
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

.status-selesai {
    background: #d1fae5;
    color: #065f46;
}

.status-aktif {
    background: #fef3c7;
    color: #92400e;
}

.status-checkin {
    background: #dbeafe;
    color: #1e40af;
}

.status-dibatalkan {
    background: #fee2e2;
    color: #991b1b;
}

.status-menunggu {
    background: #dbeafe;
    color: #1e40af;
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

.total-price-box {
    text-align: right;
}

.total-label {
    font-size: 13px;
    color: #666;
    margin-bottom: 3px;
}

.total-price {
    font-size: 22px;
    font-weight: bold;
    color: #f5a742;
}

/* ========= BUTTON ACTIONS PEMBATALAN ========= */
.booking-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e5e7eb;
}

.btn-batal-booking {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-batal-booking:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-lihat-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #8da6daff 0%, #5073b8ff 100%);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-lihat-status:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(141, 166, 218, 0.3);
}

/* Status Info Box */
.status-info {
    background: #fef3c7;
    padding: 12px 15px;
    border-radius: 10px;
    border-left: 4px solid #f5a742;
    margin-top: 15px;
    font-size: 13px;
    color: #92400e;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.status-info.cancelled {
    background: #fee2e2;
    border-left-color: #ef4444;
    color: #991b1b;
}

.status-info.pending-cancellation {
    background: #dbeafe;
    border-left-color: #3b82f6;
    color: #1e40af;
}

.status-info a {
    color: inherit;
    font-weight: 700;
    text-decoration: underline;
}

.status-info a:hover {
    text-decoration: none;
}

/* ========= PAGINATION ========= */
.pagination-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 30px;
}

.pagination {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}

.page-item {
    list-style: none;
}

.page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    color: #333;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
}

.page-link:hover {
    background: #fef9e7;
    border-color: #f5a742;
    color: #f5a742;
}

.page-item.active .page-link {
    background: #f5a742;
    border-color: #f5a742;
    color: white;
}

.page-item.disabled .page-link {
    opacity: 0.3;
    cursor: not-allowed;
    pointer-events: none;
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
    margin-bottom: 25px;
}

.btn-browse {
    padding: 12px 30px;
    background: #8da6daff;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
}

.btn-browse:hover {
    background: #5073b8ff;
    transform: translateY(-2px);
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
    
    .total-price-box {
        text-align: left;
        width: 100%;
    }
    
    .total-price {
        font-size: 20px;
    }
    
    .booking-actions {
        flex-direction: column;
    }
    
    .btn-batal-booking,
    .btn-lihat-status {
        width: 100%;
        justify-content: center;
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
    
    .total-label {
        font-size: 12px;
    }
    
    .total-price {
        font-size: 18px;
    }
    
    .booking-actions {
        padding-top: 12px;
        margin-top: 12px;
    }
    
    .btn-batal-booking,
    .btn-lihat-status {
        padding: 10px 16px;
        font-size: 12px;
    }
    
    .status-info {
        padding: 10px 12px;
        font-size: 12px;
    }
    
    .pagination {
        gap: 6px;
    }
    
    .page-link {
        min-width: 35px;
        height: 35px;
        font-size: 13px;
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
    
    .btn-browse {
        padding: 10px 24px;
        font-size: 14px;
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
    
    .total-price {
        font-size: 16px;
    }
    
    .page-link {
        min-width: 32px;
        height: 32px;
        font-size: 12px;
        padding: 0 8px;
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
                <a href="riwayat_reservasi.php" class="menu-item active">
                    <i class="bi bi-clock-history"></i>
                    <span>Riwayat Reservasi</span>
                </a>
                <a href="profil.php" class="menu-item">
                    <i class="bi bi-pencil-square"></i>
                    <span>Edit Data Pribadi</span>
                </a>
                <a href="status_pembatalan.php" class="menu-item">
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
            <?php if (isset($_SESSION['checkin_success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['checkin_success'] ?>
                </div>
                <?php unset($_SESSION['checkin_success']); ?>
            <?php endif; ?>
            
            <div class="content-header">
                <div>
                    <h2 class="content-title">Riwayat Reservasi</h2>
                    <p class="content-subtitle">Total <?= $total_results ?> reservasi ditemukan</p>
                </div>
                <div class="filter-buttons">
                    <a href="?status=semua" class="filter-btn <?= $filter_status == 'semua' ? 'active' : '' ?>">
                        <i class="bi bi-list-ul"></i>
                        <span>Semua</span>
                    </a>
                    <a href="?status=aktif" class="filter-btn <?= $filter_status == 'aktif' ? 'active' : '' ?>">
                        <i class="bi bi-clipboard-check"></i>
                        <span>Aktif</span>
                    </a>
                    <a href="?status=selesai" class="filter-btn <?= $filter_status == 'selesai' ? 'active' : '' ?>">
                        <i class="bi bi-check-circle"></i>
                        <span>Selesai</span>
                    </a>
                    <a href="?status=dibatalkan" class="filter-btn <?= $filter_status == 'dibatalkan' ? 'active' : '' ?>">
                        <i class="bi bi-x-circle"></i>
                        <span>Dibatalkan</span>
                    </a>
                </div>
            </div>
            
            <div class="reservation-list">
                <?php 
                if ($result && mysqli_num_rows($result) > 0) {
                    while($booking = mysqli_fetch_assoc($result)) {
                        $checkin = formatTanggalIndonesia($booking['tanggal_checkin']);
                        $checkout = formatTanggalIndonesia($booking['tanggal_checkout']);
                        $nama_tipe = $booking['nama_tipe'] ? $booking['nama_tipe'] : 'Standard Room';
                        
                        // Status badge
                        $status_class = '';
                        $status_text = '';
                        $status_icon = '';
                        
                        switch($booking['status_reservasi']) {
                            case 'selesai':
                                $status_class = 'status-selesai';
                                $status_text = 'Selesai';
                                $status_icon = '<i class="bi bi-check-circle"></i>';
                                break;
                            case 'confirmed':
                                $status_class = 'status-aktif';
                                $status_text = 'Dikonfirmasi';
                                $status_icon = '<i class="bi bi-clipboard-check"></i>';
                                break;
                            case 'check-in':
                                $status_class = 'status-checkin';
                                $status_text = 'Sedang Menginap';
                                $status_icon = '<i class="bi bi-house-door"></i>';
                                break;
                            case 'dibatalkan':
                                $status_class = 'status-dibatalkan';
                                $status_text = 'Dibatalkan';
                                $status_icon = '<i class="bi bi-x-circle"></i>';
                                break;
                            case 'menunggu_pembatalan':
                                $status_class = 'status-menunggu';
                                $status_text = 'Menunggu Pembatalan';
                                $status_icon = '<i class="bi bi-clock-history"></i>';
                                break;
                            default:
                                $status_class = 'status-aktif';
                                $status_text = 'Dikonfirmasi';
                                $status_icon = '<i class="bi bi-clipboard-check"></i>';
                        }
                ?>
                <div class="reservation-card">
                    <span class="status-badge <?= $status_class ?>"><?= $status_icon ?> <?= $status_text ?></span>
                    
                    <div class="card-header">
                        <div class="hotel-icon"><i class="bi bi-building"></i></div>
                        <div class="card-info">
                            <div class="room-name"><?= htmlspecialchars($nama_tipe) ?></div>
                            <div class="hotel-name"><?= htmlspecialchars($booking['nama_penginapan']) ?></div>
                            <div class="pemesan-info">Pemesan: <?= htmlspecialchars($booking['nama_pemesan']) ?></div>
                        </div>
                    </div>
                    
                    <div class="card-details">
                        <div class="detail-item">
                            <div class="detail-icon-box">
                                <i class="bi bi-calendar-check detail-icon"></i>
                                <span class="detail-label">Check-In</span>
                            </div>
                            <div class="detail-value"><?= $checkin ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon-box">
                                <i class="bi bi-calendar-x detail-icon"></i>
                                <span class="detail-label">Check-Out</span>
                            </div>
                            <div class="detail-value"><?= $checkout ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon-box">
                                <i class="bi bi-door-closed detail-icon"></i>
                                <span class="detail-label">Jumlah Kamar</span>
                            </div>
                            <div class="detail-value"><?= $booking['jumlah_kamar'] ?> Kamar</div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon-box">
                                <i class="bi bi-people detail-icon"></i>
                                <span class="detail-label">Jumlah Tamu</span>
                            </div>
                            <div class="detail-value"><?= $booking['jumlah_orang'] ?> Orang</div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <div class="booking-id">
                            # ID: <strong><?= htmlspecialchars($booking['kode_booking']) ?></strong>
                        </div>
                        <div class="total-price-box">
                            <div class="total-label">Total Pembayaran</div>
                            <div class="total-price">Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                    
                    <?php if ($booking['dapat_batalkan'] == 'dapat_dibatalkan'): ?>
                        <div class="booking-actions">
                            <a href="pembatalan.php?id=<?= $booking['id_booking'] ?>" class="btn-batal-booking">
                                <i class="bi bi-x-circle"></i>
                                Batalkan Reservasi
                            </a>
                        </div>
                    <?php elseif ($booking['dapat_batalkan'] == 'menunggu_pembatalan'): ?>
                        <div class="status-info pending-cancellation">
                            <i class="bi bi-clock-history"></i>
                            Pembatalan sedang diproses. 
                            <a href="status_pembatalan.php">Lihat Status</a>
                        </div>
                    <?php elseif ($booking['dapat_batalkan'] == 'dibatalkan'): ?>
                        <div class="status-info cancelled">
                            <i class="bi bi-x-circle"></i>
                            Booking telah dibatalkan. 
                            <a href="status_pembatalan.php">Lihat Detail</a>
                        </div>
                    <?php elseif ($booking['dapat_batalkan'] == 'sudah_checkin'): ?>
                        <div class="status-info">
                            <i class="bi bi-info-circle"></i>
                            Anda sudah check-in. Pembatalan tidak dapat dilakukan.
                        </div>
                    <?php endif; ?>
                </div>
                <?php 
                    }
                } else {
                ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                    <h3 class="empty-title">Belum Ada Reservasi</h3>
                    <p class="empty-text">Anda belum memiliki riwayat reservasi. Mulai cari penginapan favoritmu!</p>
                    <a href="penginapan.php" class="btn-browse">Cari Penginapan</a>
                </div>
                <?php } ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1) { ?>
            <div class="pagination-container">
                <ul class="pagination">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?status=<?= $filter_status ?>&page=<?= $page - 1 ?>">←</a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?status=<?= $filter_status ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php } ?>
                    
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?status=<?= $filter_status ?>&page=<?= $page + 1 ?>">→</a>
                    </li>
                </ul>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<script>
// Konfirmasi sebelum batalkan reservasi dengan SweetAlert2
document.querySelectorAll('.btn-batal-booking').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Ambil URL tujuan
        const cancelUrl = this.getAttribute('href');
        
        // Ambil data dari card terdekat
        const card = this.closest('.reservation-card');
        const kodeBooking = card.querySelector('.booking-id strong').textContent.trim();
        const namaPenginapan = card.querySelector('.hotel-name').textContent.trim();
        const totalHargaText = card.querySelector('.total-price').textContent.trim();
        const totalHarga = parseFloat(totalHargaText.replace(/[^\d]/g, ''));
        const checkinText = card.querySelectorAll('.detail-value')[0].textContent.trim();
        const checkoutText = card.querySelectorAll('.detail-value')[1].textContent.trim();
        
        // Hitung biaya pembatalan (10%)
        const biayaPembatalan = totalHarga * 0.10;
        const totalRefund = totalHarga - biayaPembatalan;
        
        // Tampilkan konfirmasi SweetAlert
        Swal.fire({
            title: 'Batalkan Reservasi Ini?',
            html: `
                <div style="text-align: left; padding: 15px;">
                    <div style="background: #f9fafb; padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                        <p style="margin-bottom: 8px; font-size: 14px;">
                            <strong>Kode Booking:</strong> ${kodeBooking}
                        </p>
                        <p style="margin-bottom: 8px; font-size: 14px;">
                            <strong>Penginapan:</strong> ${namaPenginapan}
                        </p>
                        <p style="margin-bottom: 8px; font-size: 14px;">
                            <strong>Check-in:</strong> ${checkinText}
                        </p>
                        <p style="margin-bottom: 0; font-size: 14px;">
                            <strong>Check-out:</strong> ${checkoutText}
                        </p>
                    </div>
                    
                    <div style="background: #fef3c7; padding: 15px; border-radius: 10px; border-left: 4px solid #f59e0b; margin-bottom: 15px;">
                        <p style="color: #92400e; margin-bottom: 10px; font-weight: 600; font-size: 14px;">
                            <i class="bi bi-exclamation-triangle-fill"></i> Perkiraan Biaya
                        </p>
                        <p style="margin-bottom: 6px; color: #666; font-size: 13px;">
                            Total Pembayaran: <strong>Rp ${totalHarga.toLocaleString('id-ID')}</strong>
                        </p>
                        <p style="margin-bottom: 6px; color: #dc2626; font-size: 13px;">
                            Biaya Pembatalan (10%): <strong>-Rp ${biayaPembatalan.toLocaleString('id-ID')}</strong>
                        </p>
                        <hr style="margin: 10px 0; border-color: #fde68a;">
                        <p style="margin-bottom: 0; color: #059669; font-size: 15px;">
                            <strong>Dana Dikembalikan: Rp ${totalRefund.toLocaleString('id-ID')}</strong>
                        </p>
                    </div>
                    
                    <div style="background: #fee2e2; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                        <p style="color: #991b1b; font-size: 13px; margin: 0; text-align: center;">
                            <i class="bi bi-info-circle-fill"></i> Anda akan diminta mengisi alasan pembatalan pada halaman selanjutnya
                        </p>
                    </div>
                    
                    <div style="background: #e0f2fe; padding: 12px; border-radius: 8px; margin-bottom: 10px;">
                        <p style="color: #075985; font-size: 12px; margin: 0; font-weight: 600;">
                            <i class="bi bi-shield-check"></i> Kebijakan Pembatalan:
                        </p>
                        <ul style="margin: 8px 0 0 20px; padding: 0; color: #075985; font-size: 11px; line-height: 1.6;">
                            <li>Biaya pembatalan 10% dari total pembayaran</li>
                            <li>Proses refund 3-7 hari kerja</li>
                            <li>Pembatalan tidak dapat diundur</li>
                        </ul>
                    </div>
                    
                    <p style="color: #999; font-size: 12px; text-align: center; margin: 15px 0 0 0;">
                        <i class="bi bi-clock-history"></i> Refund akan diproses setelah persetujuan admin
                    </p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="bi bi-arrow-right-circle"></i> Lanjutkan Pembatalan',
            cancelButtonText: '<i class="bi bi-x-circle"></i> Batal',
            reverseButtons: true,
            width: '580px',
            customClass: {
                popup: 'swal-cancel-popup',
                confirmButton: 'swal-btn-confirm',
                cancelButton: 'swal-btn-cancel'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Langsung redirect ke halaman pembatalan
                window.location.href = cancelUrl;
            }
        });
    });
});

// Konfirmasi logout dengan SweetAlert2
function confirmLogout() {
    Swal.fire({
        title: 'Keluar dari Akun?',
        html: `
            <p style="margin-bottom: 15px; color: #666;">Apakah kamu yakin ingin keluar?</p>
            <p style="color: #999; font-size: 13px;">Kamu bisa login kembali kapan saja</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="bi bi-box-arrow-right"></i> Ya, Keluar',
        cancelButtonText: '<i class="bi bi-x-circle"></i> Batal',
        reverseButtons: true,
        customClass: {
            popup: 'swal-logout-popup'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Berhasil Keluar!',
                html: '<p style="color: #666;">Sampai jumpa lagi 👋</p>',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                willClose: () => {
                    window.location.href = '../autentikasi/logout.php';
                }
            });
        }
    });
}

// Style tambahan untuk SweetAlert
const style = document.createElement('style');
style.textContent = `
    .swal-cancel-popup,
    .swal-logout-popup {
        border-radius: 16px !important;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    }
    
    .swal-btn-confirm,
    .swal-btn-cancel {
        border-radius: 10px !important;
        padding: 12px 24px !important;
        font-weight: 600 !important;
        font-size: 14px !important;
    }
    
    .swal2-html-container {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .swal2-title {
        font-size: 24px !important;
        font-weight: 700 !important;
        padding: 20px 20px 10px !important;
    }
    
    .swal2-actions {
        padding: 10px 20px 20px !important;
    }
`;
document.head.appendChild(style);
</script>

<?php
require_once 'footer.php';
?>
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
$filter_status = isset($_GET['filter']) ? $_GET['filter'] : 'semua';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 5;
$offset = ($page - 1) * $items_per_page;

// Build WHERE clause
$where_filter = "AND b.id_user = $user_id";

if ($filter_status == 'diproses') {
    $where_filter .= " AND pb.status_pembatalan = 'diproses'";
} elseif ($filter_status == 'ditolak') {
    $where_filter .= " AND pb.status_pembatalan = 'ditolak'";
} elseif ($filter_status == 'selesai') {
    $where_filter .= " AND pb.status_pembatalan = 'disetujui'";
}

// Cek apakah tabel pembatalan ada
$check_table = "SHOW TABLES LIKE 'pembatalan'";
$check_result = mysqli_query($conn, $check_table);
$table_exists = $check_result && mysqli_num_rows($check_result) > 0;

$total_results = 0;
$total_pages = 1;
$result = null;

if ($table_exists) {
    // Count total
    $count_sql = "SELECT COUNT(*) as total 
                  FROM pembatalan pb
                  LEFT JOIN booking b ON pb.id_booking = b.id_booking
                  WHERE 1=1 $where_filter";
    $count_result = mysqli_query($conn, $count_sql);
    
    if ($count_result) {
        $count_row = mysqli_fetch_assoc($count_result);
        $total_results = $count_row['total'];
        $total_pages = ceil($total_results / $items_per_page);
    }
    
    // Query pembatalan
    $sql = "SELECT 
                pb.*,
                b.kode_booking,
                b.tanggal_checkin,
                b.tanggal_checkout,
                b.jumlah_kamar,
                b.jumlah_orang,
                b.total_harga,
                b.status_reservasi,
                p.nama_penginapan,
                tk.nama_tipe,
                u.nama as nama_pemesan
            FROM pembatalan pb
            LEFT JOIN booking b ON pb.id_booking = b.id_booking
            LEFT JOIN penginapan p ON b.id_penginapan = p.id_penginapan
            LEFT JOIN tipe_kamar tk ON b.id_tipe_kamar = tk.id_tipe_kamar
            LEFT JOIN users u ON b.id_user = u.id_user
            WHERE 1=1 $where_filter
            ORDER BY pb.tanggal_pengajuan DESC
            LIMIT $items_per_page OFFSET $offset";
    
    $result = mysqli_query($conn, $sql);
}

require_once 'header.php';
?>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 0;
}

.profile-wrapper {
    min-height: 80vh;
    background: #f5f5f0;
    padding: 40px 20px;
    margin-top: 0;
    clear: both;
    position: relative;
    z-index: 1;
}

.profile-container {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
    position: relative;
}

.page-title {
    font-size: 36px;
    font-weight: bold;
    color: #8da6daff;
    margin-bottom: 30px;
    grid-column: 1 / -1;
}

/* Sidebar */
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
}

.profile-email {
    font-size: 13px;
    color: #666;
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

.menu-icon {
    font-size: 20px;
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

/* Main Content */
.pembatalan-content {
    background: white;
    border-radius: 20px;
    padding: 35px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 3px solid #8da6daff;
}

.content-title {
    font-size: 28px;
    font-weight: bold;
    color: #1a1a1a;
}

.content-subtitle {
    font-size: 14px;
    color: #666;
    margin-top: 5px;
}

.filter-buttons {
    display: flex;
    gap: 10px;
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

/* Pembatalan Cards */
.pembatalan-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.pembatalan-card {
    border: 2px solid #e5e7eb;
    border-radius: 15px;
    padding: 25px;
    transition: all 0.3s;
    position: relative;
}

.pembatalan-card:hover {
    border-color: #ef4444;
    box-shadow: 0 5px 20px rgba(239, 68, 68, 0.2);
}

.card-header {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.hotel-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    flex-shrink: 0;
    color: white;
}

.card-info {
    flex: 1;
}

.room-name {
    font-size: 20px;
    font-weight: bold;
    color: #1a1a1a;
    margin-bottom: 4px;
}

.hotel-name {
    font-size: 16px;
    font-weight: 600;
    color: #f5a742;
    margin-bottom: 4px;
}

.pemesan-info {
    font-size: 13px;
    color: #666;
}

.status-badge {
    position: absolute;
    top: 25px;
    right: 25px;
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
}

.status-diproses {
    background: #fef3c7;
    color: #92400e;
}

.status-ditolak {
    background: #fee2e2;
    color: #991b1b;
}

.status-selesai {
    background: #d1fae5;
    color: #065f46;
}

/* Alasan Box */
.alasan-box {
    background: #fee2e2;
    border-left: 4px solid #ef4444;
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.alasan-title {
    font-size: 14px;
    font-weight: 700;
    color: #991b1b;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alasan-text {
    font-size: 13px;
    color: #7f1d1d;
    line-height: 1.5;
}

.tanggal-pembatalan {
    font-size: 14px;
    font-weight: 600;
    color: #991b1b;
}

/* Detail Grid */
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
}

/* Footer */
.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
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

.biaya-label {
    font-size: 13px;
    color: #ef4444;
    margin-bottom: 3px;
}

.refund-label {
    font-size: 13px;
    color: #666;
    margin-bottom: 3px;
}

.refund-amount {
    font-size: 22px;
    font-weight: bold;
    color: #10b981;
}

.refund-note {
    font-size: 12px;
    color: #999;
    font-style: italic;
    margin-top: 5px;
}

/* Empty State */
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

/* Pagination */
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
}

.page-item {
    list-style: none;
}

.page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
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

/* Responsive */
@media (max-width: 968px) {
    .profile-container {
        grid-template-columns: 1fr;
    }
    
    .profile-sidebar {
        position: static;
    }
    
    .card-details {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filter-buttons {
        flex-wrap: wrap;
    }
}
</style>

<div class="profile-wrapper">
    <div class="profile-container">
        <h1 class="page-title">Hallo, <?= htmlspecialchars($user_name) ?> !</h1>
        
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
                    <span class="menu-icon">🔄</span>
                    <span>Riwayat Reservasi</span>
                </a>
                <a href="profil.php" class="menu-item">
                    <span class="menu-icon">✏️</span>
                    <span>Edit Data Pribadi</span>
                </a>
                <a href="status_pembatalan.php" class="menu-item active">
                    <span class="menu-icon">✖️</span>
                    <span>Status Pembatalan</span>
                </a>
            </div>
            
            <button class="btn-logout" onclick="if(confirm('Yakin ingin keluar?')) window.location.href='../autentikasi/logout.php'">
                <span>🚪</span>
                <span>Keluar</span>
            </button>
        </div>
        
        <!-- Main Content -->
        <div class="pembatalan-content">
            <?php if (isset($_SESSION['batal_success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['batal_success'] ?>
                </div>
                <?php unset($_SESSION['batal_success']); ?>
            <?php endif; ?>
            
            <div class="content-header">
                <div>
                    <h2 class="content-title">Status Permohonan Pembatalan</h2>
                    <p class="content-subtitle">Total <?= $total_results ?> pembatalan ditemukan</p>
                </div>
                <div class="filter-buttons">
                    <a href="?filter=semua" class="filter-btn <?= $filter_status == 'semua' ? 'active' : '' ?>">
                        <span>🔍</span>
                        <span>Semua</span>
                    </a>
                    <a href="?filter=diproses" class="filter-btn <?= $filter_status == 'diproses' ? 'active' : '' ?>">
                        <span>⏳</span>
                        <span>Diproses</span>
                    </a>
                    <a href="?filter=ditolak" class="filter-btn <?= $filter_status == 'ditolak' ? 'active' : '' ?>">
                        <span>❌</span>
                        <span>Ditolak</span>
                    </a>
                    <a href="?filter=selesai" class="filter-btn <?= $filter_status == 'selesai' ? 'active' : '' ?>">
                        <span>✅</span>
                        <span>Selesai</span>
                    </a>
                </div>
            </div>
            
            <div class="pembatalan-list">
                <?php 
                if (!$table_exists) {
                ?>
                <div class="empty-state">
                    <div class="empty-icon">⚠️</div>
                    <h3 class="empty-title">Tabel Pembatalan Belum Dibuat</h3>
                    <p class="empty-text">
                        Silakan jalankan SQL untuk membuat tabel pembatalan
                    </p>
                </div>
                <?php
                } elseif ($result && mysqli_num_rows($result) > 0) {
                    while($batal = mysqli_fetch_assoc($result)) {
                        $tgl_batal = date('d F Y', strtotime($batal['tanggal_pengajuan']));
                        $checkin = date('d M Y', strtotime($batal['tanggal_checkin']));
                        $checkout = date('d M Y', strtotime($batal['tanggal_checkout']));
                        $nama_tipe = $batal['nama_tipe'] ? $batal['nama_tipe'] : 'Standard Room';
                        
                        $status_class = '';
                        $status_text = '';
                        switch($batal['status_pembatalan']) {
                            case 'diproses': 
                                $status_class = 'status-diproses'; 
                                $status_text = 'Diproses'; 
                                break;
                            case 'ditolak': 
                                $status_class = 'status-ditolak'; 
                                $status_text = 'Ditolak'; 
                                break;
                            case 'disetujui': 
                                $status_class = 'status-selesai'; 
                                $status_text = 'Selesai'; 
                                break;
                        }
                        
                        $biaya_pembatalan = $batal['total_harga'] * 0.10;
                        $total_refund = $batal['total_harga'] - $biaya_pembatalan;
                ?>
                <div class="pembatalan-card">
                    <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                    
                    <div class="card-header">
                        <div class="hotel-icon">✖</div>
                        <div class="card-info">
                            <div class="room-name"><?= htmlspecialchars($nama_tipe) ?></div>
                            <div class="hotel-name"><?= htmlspecialchars($batal['nama_penginapan']) ?></div>
                            <div class="pemesan-info">Pemesan: <?= htmlspecialchars($batal['nama_pemesan']) ?></div>
                        </div>
                    </div>
                    
                    <div class="alasan-box">
                        <div class="alasan-title">
                            <span>⚠</span>
                            <span>Tanggal Pembatalan</span>
                        </div>
                        <div class="tanggal-pembatalan"><?= $tgl_batal ?></div>
                        <div class="alasan-text" style="margin-top: 10px;">
                            <strong>Alasan Pembatalan:</strong><br>
                            <?= htmlspecialchars($batal['alasan_pembatalan']) ?>
                        </div>
                    </div>
                    
                    <div class="card-details">
                        <div class="detail-item">
                            <div class="detail-icon-box">
                                <span class="detail-icon">📅</span>
                                <span class="detail-label">Check-In</span>
                            </div>
                            <div class="detail-value"><?= $checkin ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon-box">
                                <span class="detail-icon">📅</span>
                                <span class="detail-label">Check-Out</span>
                            </div>
                            <div class="detail-value"><?= $checkout ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon-box">
                                <span class="detail-icon">🛏️</span>
                                <span class="detail-label">Jumlah Kamar</span>
                            </div>
                            <div class="detail-value"><?= $batal['jumlah_kamar'] ?> Kamar</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon-box">
                                <span class="detail-icon">👥</span>
                                <span class="detail-label">Jumlah Tamu</span>
                            </div>
                            <div class="detail-value"><?= $batal['jumlah_orang'] ?> Orang</div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <div class="booking-id">
                            # ID: <strong><?= htmlspecialchars($batal['kode_booking']) ?></strong>
                        </div>
                        <div class="refund-info">
                            <div class="biaya-label">Biaya Pembatalan: -Rp <?= number_format($biaya_pembatalan, 0, ',', '.') ?></div>
                            <div class="refund-label">Total Refund</div>
                            <div class="refund-amount">Rp <?= number_format($total_refund, 0, ',', '.') ?></div>
                            <div class="refund-note">
                                <?= $batal['status_pembatalan'] == 'diproses' ? 'Refund sedang diproses' : '' ?>
                                <?= $batal['status_pembatalan'] == 'disetujui' ? 'Refund telah disetujui' : '' ?>
                                <?= $batal['status_pembatalan'] == 'ditolak' ? 'Pembatalan ditolak' : '' ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                    }
                } else {
                ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3 class="empty-title">Belum Ada Pembatalan</h3>
                    <p class="empty-text">Anda belum pernah membatalkan reservasi.</p>
                </div>
                <?php } ?>
            </div>
            
            <?php if ($total_pages > 1) { ?>
            <div class="pagination-container">
                <ul class="pagination">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?filter=<?= $filter_status ?>&page=<?= $page - 1 ?>">←</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?filter=<?= $filter_status ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php } ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?filter=<?= $filter_status ?>&page=<?= $page + 1 ?>">→</a>
                    </li>
                </ul>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>
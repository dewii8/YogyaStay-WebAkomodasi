<?php
require_once '../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* PROTEKSI ADMIN */
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 'admin') {
    header("Location: ../../autentikasi/login.php");
    exit;
}

// PAGINATION
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// QUERY PEMBATALAN
$query = mysqli_query($conn, "
    SELECT 
        b.id_booking,
        b.kode_booking,
        b.tanggal_checkin,
        b.tanggal_checkout,
        b.status_reservasi,
        b.created_at,
        pen.nama_penginapan,
        u.nama AS nama_pelanggan,
        u.email AS email_pelanggan,
        p.total_bayar,
        p.status_pembayaran,
        pb.id_pembatalan,
        pb.alasan,
        pb.created_at AS tanggal_batal
    FROM booking b
    JOIN penginapan pen ON b.id_penginapan = pen.id_penginapan
    JOIN users u ON b.id_user = u.id_user
    LEFT JOIN pembayaran p ON b.id_booking = p.id_booking
    LEFT JOIN pembatalan pb ON b.id_booking = pb.id_booking
    WHERE b.status_reservasi = 'dibatalkan'
    ORDER BY pb.created_at DESC, b.created_at DESC
    LIMIT $per_page OFFSET $offset
");

// TOTAL DATA
$total_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM booking 
    WHERE status_reservasi = 'dibatalkan'
");
$total = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total / $per_page);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pembatalan Kamar - YogyaStay</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Poppins', sans-serif; background:#fff7ed; }

.main-content { margin-left:240px; padding:40px; transition:0.3s; }

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 30px;
    background: white;
    padding: 20px 30px;
    border-radius: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.hamburger {
    font-size: 26px;
    cursor: pointer;
    background: #fef3c7;
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: none;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

h2 { font-size:28px; color:#333; margin:0; }

.pembatalan-dashboard { background:#fff; border-radius:20px; padding:30px; box-shadow:0 6px 20px rgba(0,0,0,0.08); overflow-x:auto; }

table { width:100%; border-collapse:collapse; min-width:900px; }
th, td { padding:15px; border-bottom:1px solid #e0e0e0; text-align:left; }
th { background:#f1f3f6; font-weight:600; color:#555; font-size: 14px; }
tr:hover { background:#f9f9f9; transition:0.2s; }
td { font-size: 14px; }

.badge {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-success { background: #d1fae5; color: #065f46; }

.pagination { display:flex; flex-wrap:wrap; justify-content:center; margin-top:25px; gap: 5px; }
.pagination a { padding:10px 16px; border-radius:8px; background:#f8f9fa; color:#555; text-decoration:none; transition:0.3s; font-size:14px; font-weight: 600; }
.pagination a:hover { background:#fde68a; }
.pagination a.active { background:#f59e0b; color:#fff; }

.empty-state {
    text-align: center;
    padding: 60px 30px;
    color: #6b7280;
}

.empty-state i {
    font-size: 80px;
    margin-bottom: 20px;
    opacity: 0.3;
}

@media(max-width:768px){ 
    .main-content { margin-left:0; padding:20px; } 
    .hamburger { display: flex; }
    table { font-size: 12px; min-width: 800px; }
    th, td { padding: 10px 8px; }
}
</style>
</head>
<body>

<?php 
if (file_exists('../partials/sidebar.php')) {
    include '../partials/sidebar.php'; 
}
?>

<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <span class="hamburger" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </span>
            <h2>🚫 Pembatalan Kamar</h2>
        </div>
    </div>

    <section class="pembatalan-dashboard">
        <?php if(mysqli_num_rows($query) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Kode Booking</th>
                    <th>Penginapan</th>
                    <th>Pelanggan</th>
                    <th>Check-in</th>
                    <th>Total Bayar</th>
                    <th>Tanggal Batal</th>
                    <th>Alasan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = $offset + 1; while($row = mysqli_fetch_assoc($query)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><strong><?= htmlspecialchars($row['kode_booking']); ?></strong></td>
                    <td><?= htmlspecialchars($row['nama_penginapan']); ?></td>
                    <td>
                        <strong><?= htmlspecialchars($row['nama_pelanggan']); ?></strong><br>
                        <small style="color: #6b7280;"><?= htmlspecialchars($row['email_pelanggan']); ?></small>
                    </td>
                    <td style="white-space: nowrap;"><?= date('d M Y', strtotime($row['tanggal_checkin'])); ?></td>
                    <td>Rp <?= number_format($row['total_bayar'] ?? 0, 0, ',', '.'); ?></td>
                    <td style="white-space: nowrap;">
                        <?= $row['tanggal_batal'] ? date('d M Y', strtotime($row['tanggal_batal'])) : '-'; ?>
                    </td>
                    <td style="max-width: 250px;">
                        <?= htmlspecialchars($row['alasan'] ?? 'Tidak ada keterangan'); ?>
                    </td>
                    <td>
                        <span class="badge badge-danger">
                            Dibatalkan
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php
            if($page > 1) echo '<a href="?page=1">«</a>';
            if($page > 1) echo '<a href="?page='.($page-1).'">‹</a>';
            
            $range = 2;
            for($i=max(1,$page-$range); $i<=min($total_pages,$page+$range); $i++){
                echo '<a href="?page='.$i.'" class="'.($i==$page?'active':'').'">'.$i.'</a>';
            }
            
            if($page < $total_pages) echo '<a href="?page='.($page+1).'">›</a>';
            if($page < $total_pages) echo '<a href="?page='.$total_pages.'">»</a>';
            ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <h3>Tidak Ada Pembatalan</h3>
            <p>Saat ini tidak ada pembatalan kamar yang tercatat.</p>
        </div>
        <?php endif; ?>
    </section>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) {
        sidebar.classList.toggle('active');
        if(overlay) overlay.classList.toggle('show');
    }
}
</script>

</body>
</html>
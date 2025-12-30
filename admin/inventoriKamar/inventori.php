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

/* HANDLE DELETE */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $from_tipe = isset($_GET['from_tipe']) ? $_GET['from_tipe'] : 'hotel';
    
    // ✅ CEK BOOKING AKTIF (status: dipesan atau check-in)
    $check_booking = mysqli_query($conn, "
        SELECT COUNT(*) as total
        FROM booking b
        INNER JOIN tipe_kamar tk ON b.id_tipe_kamar = tk.id_tipe_kamar
        WHERE tk.id_penginapan = '$id'
        AND b.status_reservasi IN ('dipesan', 'check-in')
    ");
    
    if (!$check_booking) {
        die("Error query: " . mysqli_error($conn));
    }
    
    $booking_data = mysqli_fetch_assoc($check_booking);
    $total_booking = $booking_data['total'];
    
    // ❌ KALAU ADA BOOKING AKTIF - TOLAK HAPUS
    if ($total_booking > 0) {
        header("Location: inventori.php?tipe=$from_tipe&error=has_booking&total=$total_booking");
        exit;
    }
    
    // ✅ KALAU TIDAK ADA BOOKING AKTIF - BOLEH HAPUS
    
    // Ambil data penginapan dulu untuk log
    $query_data = mysqli_query($conn, "SELECT nama_penginapan FROM penginapan WHERE id_penginapan='$id'");
    $data_penginapan = mysqli_fetch_assoc($query_data);
    $nama_penginapan = $data_penginapan['nama_penginapan'] ?? "ID $id";
    
    // Hapus gambar dari server
    $qImg = mysqli_query($conn, "SELECT path_gambar FROM gambar_penginapan WHERE id_penginapan='$id'");
    while($img = mysqli_fetch_assoc($qImg)) {
        if(file_exists("../../".$img['path_gambar'])) {
            unlink("../../".$img['path_gambar']);
        }
    }
    
    // Hapus semua records
    mysqli_query($conn, "DELETE FROM gambar_penginapan WHERE id_penginapan='$id'");
    mysqli_query($conn, "DELETE FROM tipe_kamar WHERE id_penginapan='$id'");
    mysqli_query($conn, "DELETE FROM penginapan_fasilitas WHERE id_penginapan='$id'");
    mysqli_query($conn, "DELETE FROM kontak_penginapan WHERE id_penginapan='$id'");
    mysqli_query($conn, "DELETE FROM penginapan WHERE id_penginapan='$id'");

    // LOG AKTIVITAS ADMIN
    if (isset($_SESSION['user_id'])) {
        $admin_id = intval($_SESSION['user_id']);
        $aksi = mysqli_real_escape_string($conn, "Hapus Penginapan");
        $deskripsi = mysqli_real_escape_string($conn, "Admin menghapus penginapan: $nama_penginapan (ID: $id)");
        
        $check_table = mysqli_query($conn, "DESCRIBE log_aktivitas_admin");
        
        if ($check_table) {
            $columns = [];
            while ($col = mysqli_fetch_assoc($check_table)) {
                $columns[] = $col['Field'];
            }
            
            $admin_column = null;
            $possible_admin_columns = ['id_admin', 'admin_id', 'user_id', 'id_user'];
            foreach ($possible_admin_columns as $col) {
                if (in_array($col, $columns)) {
                    $admin_column = $col;
                    break;
                }
            }
            
            $time_column = null;
            $possible_time_columns = ['created_at', 'waktu', 'tanggal', 'timestamp', 'date_created'];
            foreach ($possible_time_columns as $col) {
                if (in_array($col, $columns)) {
                    $time_column = $col;
                    break;
                }
            }
            
            if ($admin_column && in_array('aksi', $columns)) {
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
                
                mysqli_query($conn, $log_query);
            }
        }
    }

    header("Location: inventori.php?tipe=$from_tipe&success=delete");
    exit;
}

/* PARAMETER */
$tipe = isset($_GET['tipe']) ? mysqli_real_escape_string($conn, strtolower($_GET['tipe'])) : 'hotel';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$start = ($page - 1) * $limit;

/* TOTAL DATA - FIX: Hitung semua penginapan tanpa filter tipe kamar */
$totalQuery = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM penginapan p
    WHERE LOWER(p.tipe_penginapan) = '$tipe'
");

if (!$totalQuery) {
    die("Error pada query total: " . mysqli_error($conn));
}

$totalRow = mysqli_fetch_assoc($totalQuery);
$totalData = $totalRow['total'] ?? 0;
$totalPage = ceil($totalData / $limit);

/* DATA PENGINAPAN - FIX: Tampilkan semua penginapan */
$query = mysqli_query($conn, "
    SELECT
        p.id_penginapan,
        p.nama_penginapan,
        p.tipe_penginapan,
        p.alamat,
        p.harga_mulai,
        p.status,
        p.created_at,
        kc.nama_kecamatan,
        kb.nama_kabupaten,
        COALESCE((SELECT COUNT(DISTINCT id_tipe_kamar) 
                  FROM tipe_kamar 
                  WHERE id_penginapan = p.id_penginapan), 0) as total_tipe_kamar,
        COALESCE((SELECT SUM(jumlah_kamar) 
                  FROM tipe_kamar 
                  WHERE id_penginapan = p.id_penginapan), 0) as total_unit_kamar
    FROM penginapan p
    LEFT JOIN kecamatan kc ON p.id_kecamatan = kc.id_kecamatan
    LEFT JOIN kabupaten kb ON p.id_kabupaten = kb.id_kabupaten
    WHERE LOWER(p.tipe_penginapan) = '$tipe'
    ORDER BY p.created_at DESC
    LIMIT $start, $limit
");

if (!$query) {
    die("Error pada query data: " . mysqli_error($conn));
}

$jumlahData = mysqli_num_rows($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Inventori Penginapan - <?= ucfirst($tipe) ?></title>
 <link rel="icon" type="image/jpeg" href="../../assets/img/logonw.png">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
* { 
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body { 
    font-family: 'Poppins', sans-serif; 
    background: #fff7ed;
    min-height: 100vh;
}

/* CONTENT */
.content {
    margin-left: 240px;
    padding: 30px;
    transition: margin-left 0.3s ease;
}

/* TOPBAR */
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

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
}

/* ALERT SUCCESS */
.alert {
    padding: 16px 24px;
    border-radius: 16px;
    margin-bottom: 25px;
    animation: slideInDown 0.4s ease;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 2px solid #6ee7b7;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 2px solid #fca5a5;
}

.alert-error strong {
    display: block;
    margin-bottom: 5px;
    font-size: 16px;
}

@keyframes slideInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* HEADER */
.header {
    background: white;
    padding: 25px 30px;
    border-radius: 20px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.header-info h3 {
    font-size: 20px;
    margin-bottom: 5px;
    color: #1a1a1a;
}

.header-info p {
    color: #64748b;
    font-size: 14px;
}

.btn-add {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    padding: 14px 28px;
    border-radius: 14px;
    text-decoration: none;
    color: white;
    font-weight: 700;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 6px 20px rgba(251, 191, 36, 0.4);
    font-size: 15px;
    white-space: nowrap;
}

.btn-add:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(251, 191, 36, 0.5);
}

/* TABS */
.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    background: white;
    padding: 10px;
    border-radius: 18px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow-x: auto;
}

.tabs a {
    flex: 1;
    min-width: 120px;
    text-decoration: none;
    font-weight: 600;
    color: #64748b;
    padding: 14px 24px;
    border-radius: 12px;
    transition: all 0.3s;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 15px;
    white-space: nowrap;
}

.tabs a:hover {
    background: #fef3c7;
    color: #92400e;
}

.tabs a.active {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);
}

/* TABLE */
.table-wrapper {
    background: white;
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow-x: auto;
    margin-bottom: 30px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

thead {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
}

thead th {
    padding: 16px 12px;
    text-align: left;
    font-weight: 700;
    color: #92400e;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 3px solid #fbbf24;
}

thead th:first-child {
    border-radius: 12px 0 0 0;
}

thead th:last-child {
    border-radius: 0 12px 0 0;
    text-align: center;
}

tbody tr {
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.3s ease;
}

tbody tr:hover {
    background: #fef3c7;
    transform: scale(1.01);
}

tbody tr:last-child {
    border-bottom: none;
}

tbody td {
    padding: 16px 12px;
    font-size: 14px;
    color: #374151;
}

.table-number {
    font-weight: 700;
    color: #64748b;
    width: 50px;
}

.table-name {
    font-weight: 600;
    color: #1a1a1a;
    max-width: 250px;
}

.table-location {
    color: #64748b;
    font-size: 13px;
}

.table-location i {
    color: #fbbf24;
    margin-right: 5px;
}

.table-stats {
    text-align: center;
}

.stat-badge {
    background: #fef3c7;
    padding: 6px 12px;
    border-radius: 8px;
    font-weight: 700;
    color: #92400e;
    font-size: 18px;
    display: inline-block;
}

.table-price {
    font-weight: 700;
    color: #f59e0b;
    font-size: 16px;
    white-space: nowrap;
}

.table-price small {
    font-size: 11px;
    color: #64748b;
    font-weight: 500;
}

.table-actions {
    text-align: center;
    white-space: nowrap;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s;
    margin: 0 3px;
}

.btn-view {
    background: #dbeafe;
    color: #1e40af;
}

.btn-view:hover {
    background: #bfdbfe;
    transform: scale(1.1);
}

.btn-edit {
    background: #fef3c7;
    color: #92400e;
}

.btn-edit:hover {
    background: #fde68a;
    transform: scale(1.1);
}

.btn-delete {
    background: #fee2e2;
    color: #991b1b;
}

.btn-delete:hover {
    background: #fecaca;
    transform: scale(1.1);
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 80px 30px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}

.empty-icon {
    font-size: 100px;
    margin-bottom: 20px;
    opacity: 0.4;
}

.empty-state h3 {
    color: #374151;
    margin-bottom: 10px;
    font-size: 24px;
}

.empty-state p {
    color: #9ca3af;
    margin-bottom: 30px;
}

/* PAGINATION */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin: 40px 0;
    flex-wrap: wrap;
}

.pagination a {
    padding: 12px 18px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    text-decoration: none;
    color: #374151;
    font-weight: 600;
    transition: all 0.3s;
    background: white;
}

.pagination a:hover {
    border-color: #fbbf24;
    background: #fef3c7;
    transform: translateY(-2px);
}

.pagination a.active {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    border-color: #fbbf24;
    color: white;
    box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);
}

.page-info {
    color: #64748b;
    font-size: 14px;
    font-weight: 500;
}

/* MODAL */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    animation: fadeIn 0.3s ease;
}

.modal-overlay.show {
    display: flex;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-box {
    background: white;
    width: 90%;
    max-width: 450px;
    padding: 35px;
    border-radius: 24px;
    text-align: center;
    animation: scaleIn 0.3s ease;
    box-shadow: 0 25px 60px rgba(0,0,0,0.4);
}

@keyframes scaleIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.modal-icon {
    font-size: 70px;
    margin-bottom: 20px;
    color: #ef4444;
}

.modal-box h3 {
    margin-bottom: 12px;
    color: #1a1a1a;
    font-size: 24px;
}

.modal-box p {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 30px;
    line-height: 1.6;
}

.modal-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.btn-modal {
    padding: 14px 24px;
    border-radius: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 15px;
}

.btn-cancel {
    background: #f3f4f6;
    color: #374151;
}

.btn-cancel:hover {
    background: #e5e7eb;
}

.btn-delete-confirm {
    background: #ef4444;
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-delete-confirm:hover {
    background: #dc2626;
    transform: scale(1.05);
}

/* RESPONSIVE */
@media (max-width: 992px) {
    .content { 
        margin-left: 0; 
        padding: 20px;
    }
}

@media (max-width: 768px) {
    .content {
        padding: 15px;
    }
    
    .page-title {
        font-size: 20px;
    }
    
    .header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .btn-add {
        width: 100%;
        justify-content: center;
    }
    
    .table-wrapper {
        padding: 15px;
    }
    
    thead th {
        padding: 12px 8px;
        font-size: 11px;
    }
    
    tbody td {
        padding: 12px 8px;
        font-size: 13px;
    }
    
    .table-name {
        max-width: 150px;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<?php 
if (file_exists('../partials/sidebar.php')) {
    include '../partials/sidebar.php'; 
}
?>

<div class="content">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <h1 class="page-title">Inventori Penginapan</h1>
        </div>
    </div>

   <!-- ALERT SUCCESS & ERROR -->
<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">
    <span style="font-size: 24px;">✅</span>
    <span>
        <?php if ($_GET['success'] == '1'): ?>
            Penginapan berhasil ditambahkan!
        <?php elseif ($_GET['success'] == 'delete'): ?>
            Penginapan berhasil dihapus!
        <?php elseif ($_GET['success'] == 'update'): ?>
            Penginapan berhasil diupdate!
        <?php endif; ?>
    </span>
</div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] == 'has_booking'): ?>
<div class="alert alert-error">
    <span style="font-size: 24px;">❌</span>
    <span>
        <strong>Tidak dapat menghapus penginapan!</strong><br>
        Terdapat <?= isset($_GET['total']) ? $_GET['total'] : '1' ?> booking aktif yang masih berjalan.
        Silakan tunggu hingga booking selesai atau batalkan booking terlebih dahulu.
    </span>
</div>
<?php endif; ?>


    <!-- HEADER -->
    <div class="header">
        <div class="header-info">
            <h3>Kelola <?= ucfirst($tipe) ?></h3>
            <p>Total <?= $totalData ?> penginapan terdaftar</p>
        </div>
        <a href="tambah_penginapan.php" class="btn-add">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Penginapan</span>
        </a>
    </div>

    <!-- TABS -->
    <div class="tabs">
        <a href="?tipe=homestay" class="<?= $tipe == 'homestay' ? 'active' : '' ?>">
            <i class="fa-solid fa-house"></i>
            <span>Homestay</span>
        </a>
        <a href="?tipe=hotel" class="<?= $tipe == 'hotel' ? 'active' : '' ?>">
            <i class="fa-solid fa-building"></i>
            <span>Hotel</span>
        </a>
        <a href="?tipe=villa" class="<?= $tipe == 'villa' ? 'active' : '' ?>">
            <i class="fa-solid fa-hotel"></i>
            <span>Villa</span>
        </a>
    </div>

    <!-- TABLE -->
    <?php if ($jumlahData > 0): ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Penginapan</th>
                    <th>Lokasi</th>
                    <th style="text-align: center;">Tipe Kamar</th>
                    <th style="text-align: center;">Total Unit</th>
                    <th>Harga Mulai</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = $start + 1;
                while($row = mysqli_fetch_assoc($query)): 
                    $total_tipe = $row['total_tipe_kamar'] ?? 0;
                    $total_unit = $row['total_unit_kamar'] ?? 0;
                ?>
                <tr>
                    <td class="table-number"><?= $no++ ?></td>
                    
                    <td class="table-name">
                        <?= htmlspecialchars($row['nama_penginapan']) ?>
                    </td>
                    
                    <td class="table-location">
                        <i class="fa-solid fa-location-dot"></i>
                        <?= htmlspecialchars($row['nama_kecamatan'] ?? 'Lokasi') ?>, 
                        <?= htmlspecialchars($row['nama_kabupaten'] ?? 'Yogyakarta') ?>
                    </td>
                    
                    <td class="table-stats">
                        <span class="stat-badge"><?= $total_tipe ?></span>
                    </td>
                    
                    <td class="table-stats">
                        <span class="stat-badge"><?= $total_unit ?></span>
                    </td>
                    
                    <td class="table-price">
                        Rp <?= number_format($row['harga_mulai'] ?? 0, 0, ',', '.') ?>
                        <small>/malam</small>
                    </td>
                    
                    <td class="table-actions">
                        <a href="detail_penginapan.php?id=<?= $row['id_penginapan'] ?>" 
                           class="btn-action btn-view" 
                           title="Detail">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        
                        <a href="edit_penginapan.php?id=<?= $row['id_penginapan'] ?>" 
                           class="btn-action btn-edit" 
                           title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        
                        <a href="javascript:void(0)" 
                           onclick="openDelete(<?= $row['id_penginapan'] ?>, '<?= strtolower($row['tipe_penginapan']) ?>')" 
                           class="btn-action btn-delete"
                           title="Hapus">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPage > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?tipe=<?= $tipe ?>&page=<?= $page - 1 ?>">
                <i class="fa-solid fa-chevron-left"></i> Prev
            </a>
        <?php endif; ?>
        
        <span class="page-info">
            Halaman <?= $page ?> dari <?= $totalPage ?>
        </span>
        
        <?php
        // Pagination logic
        $range = 2;
        for ($i = 1; $i <= $totalPage; $i++):
            if ($i == 1 || $i == $totalPage || ($i >= $page - $range && $i <= $page + $range)):
        ?>
            <a href="?tipe=<?= $tipe ?>&page=<?= $i ?>" 
               class="<?= $page == $i ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php 
            elseif ($i == $page - $range - 1 || $i == $page + $range + 1):
                echo '<span class="page-info">...</span>';
            endif;
        endfor;
        ?>
        
        <?php if ($page < $totalPage): ?>
            <a href="?tipe=<?= $tipe ?>&page=<?= $page + 1 ?>">
                Next <i class="fa-solid fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">
            <i class="fa-solid fa-box-open"></i>
        </div>

        <h3>Belum Ada <?= ucfirst($tipe) ?></h3>
        <p>Mulai tambahkan penginapan <?= $tipe ?> pertama Anda</p>
        <a href="tambah_penginapan.php" class="btn-add" style="display: inline-flex; margin-top: 15px;">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Penginapan</span>
        </a>
    </div>
    <?php endif; ?>

</div>

<!-- MODAL DELETE -->
<div id="deleteModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3>Hapus Penginapan?</h3>
        <p>Data penginapan, tipe kamar, fasilitas, dan semua gambar akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>
        <div class="modal-actions">
            <button onclick="closeDelete()" class="btn-modal btn-cancel">Batal</button>
            <a id="confirmDelete" class="btn-modal btn-delete-confirm">Hapus</a>
        </div>
    </div>
</div>

<script>
function openDelete(id, tipe) {
    document.getElementById('deleteModal').classList.add('show');
    document.getElementById('confirmDelete').href = '?delete=' + id + '&from_tipe=' + tipe;
}

function closeDelete() {
    document.getElementById('deleteModal').classList.remove('show');
}

// Modal close on outside click
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDelete();
    }
});

// Modal close on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDelete();
    }
});

// Auto-hide alert
const alert = document.querySelector('.alert');
if (alert) {
    setTimeout(() => {
        alert.style.animation = 'slideInDown 0.3s ease reverse';
        setTimeout(() => alert.remove(), 300);
    }, 4000);
}
</script>

</body>
</html>
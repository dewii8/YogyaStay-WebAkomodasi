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

    //  LOG AKTIVITAS ADMIN 
    if (isset($_SESSION['admin_id'])) { // ganti 'admin_id' sesuai session ID admin
        $adminId = intval($_SESSION['admin_id']);
        $aksi = "Hapus penginapan ID $id";
        mysqli_query($conn, "
            INSERT INTO log_aktivitas_admin (admin_id, aksi, target_id)
            VALUES ($adminId, '".mysqli_real_escape_string($conn,$aksi)."', $id)
        ");
    }
    // =======================================================

    $from_tipe = isset($_GET['from_tipe']) ? $_GET['from_tipe'] : 'hotel';
    header("Location: inventori.php?tipe=$from_tipe&success=delete");
    exit;
}

/* PARAMETER */
$tipe = isset($_GET['tipe']) ? mysqli_real_escape_string($conn, strtolower($_GET['tipe'])) : 'hotel';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6;
$start = ($page - 1) * $limit;

/* TOTAL DATA  */
$totalQuery = mysqli_query($conn, "
    SELECT COUNT(DISTINCT p.id_penginapan) AS total
    FROM penginapan p
    WHERE LOWER(p.tipe_penginapan) = '$tipe'
    AND (
        SELECT COUNT(*) 
        FROM tipe_kamar tk 
        WHERE tk.id_penginapan = p.id_penginapan
    ) > 0
");
$totalData = mysqli_fetch_assoc($totalQuery)['total'] ?? 0;
$totalPage = ceil($totalData / $limit);

/* DATA PENGINAPAN  */
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
        (SELECT path_gambar 
         FROM gambar_penginapan 
         WHERE id_penginapan = p.id_penginapan AND is_thumbnail = 1 
         LIMIT 1) as gambar_thumbnail,
        (SELECT COUNT(DISTINCT id_tipe_kamar) 
         FROM tipe_kamar 
         WHERE id_penginapan = p.id_penginapan) as total_tipe_kamar,
        (SELECT SUM(jumlah_kamar) 
         FROM tipe_kamar 
         WHERE id_penginapan = p.id_penginapan) as total_unit_kamar
    FROM penginapan p
    LEFT JOIN kecamatan kc ON p.id_kecamatan = kc.id_kecamatan
    LEFT JOIN kabupaten kb ON p.id_kabupaten = kb.id_kabupaten
    WHERE LOWER(p.tipe_penginapan) = '$tipe'
    HAVING total_tipe_kamar > 0
    ORDER BY p.created_at DESC
    LIMIT $start, $limit
");

if (!$query) {
    die("Error query: " . mysqli_error($conn));
}

$jumlahData = mysqli_num_rows($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Inventori Penginapan - <?= ucfirst($tipe) ?></title>
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
    transition: margin-left .3s ease;
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

.hamburger:hover {
    background: #fde68a;
    transform: rotate(90deg);
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
}

.tabs a {
    flex: 1;
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

/* GRID CARDS */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.4s ease;
    position: relative;
}

.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.card-image-wrapper {
    position: relative;
    height: 220px;
    overflow: hidden;
}

.card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.card:hover img {
    transform: scale(1.15);
}

.card-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    letter-spacing: 0.5px;
}

.badge-hotel { 
    background: rgba(59, 130, 246, 0.95); 
    color: white; 
}

.badge-villa { 
    background: rgba(34, 197, 94, 0.95); 
    color: white; 
}

.badge-homestay { 
    background: rgba(251, 191, 36, 0.95); 
    color: white; 
}

.card-body {
    padding: 20px;
}

.card-title {
    font-size: 19px;
    font-weight: 700;
    margin-bottom: 12px;
    color: #1a1a1a;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 50px;
}

.card-info {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 15px;
}

.info-row {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #64748b;
}

.info-icon {
    font-size: 18px;
    min-width: 20px;
}

.info-highlight {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    padding: 12px 15px;
    border-radius: 12px;
    margin: 15px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 2px solid #fbbf24;
}

.info-highlight-item {
    text-align: center;
    flex: 1;
}

.info-highlight-label {
    font-size: 11px;
    color: #92400e;
    font-weight: 700;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-highlight-value {
    font-size: 22px;
    font-weight: 800;
    color: #1a1a1a;
}

.info-divider {
    width: 2px;
    height: 40px;
    background: #fbbf24;
    margin: 0 5px;
}

.card-price {
    font-weight: 700;
    color: #f59e0b;
    font-size: 20px;
    margin: 15px 0;
    padding-top: 15px;
    border-top: 2px solid #f3f4f6;
}

.card-price small {
    font-size: 14px;
    color: #64748b;
    font-weight: 500;
}

.card-actions {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 10px;
}

.btn-action {
    text-decoration: none;
    font-weight: 600;
    padding: 12px;
    border-radius: 12px;
    font-size: 13px;
    transition: all 0.3s;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-view {
    background: #dbeafe;
    color: #1e40af;
}

.btn-view:hover {
    background: #bfdbfe;
    transform: scale(1.05);
}

.btn-edit {
    background: #fef3c7;
    color: #92400e;
}

.btn-edit:hover {
    background: #fde68a;
    transform: scale(1.05);
}

.btn-delete {
    background: #fee2e2;
    color: #991b1b;
}

.btn-delete:hover {
    background: #fecaca;
    transform: scale(1.05);
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
@media(max-width:992px) {
    .content { 
        margin-left: 0; 
        padding: 20px;
    }
    .hamburger { 
        display: flex; 
    }
    .grid { 
        grid-template-columns: 1fr; 
    }
    .tabs {
        flex-direction: column;
    }
    .page-title {
        font-size: 22px;
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
            <span class="hamburger" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </span>

            <h1 class="page-title"> Inventori Penginapan</h1>
        </div>
    </div>

    <!-- ALERT SUCCESS -->
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <span style="font-size: 24px;">
            <?php if ($_GET['success'] == '1'): ?>
            <?php elseif ($_GET['success'] == 'delete'): ?>
            <?php endif; ?>
        </span>
        <span>
            <?php if ($_GET['success'] == '1'): ?>
                Penginapan berhasil ditambahkan!
            <?php elseif ($_GET['success'] == 'delete'): ?>
                Penginapan berhasil dihapus!
            <?php endif; ?>
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
            <span>
                 <i class="fa-solid fa-plus"></i>
                Tambah Penginapan
            </span>

        </a>
    </div>

    <!-- TABS -->
    <div class="tabs">
        <a href="?tipe=homestay" class="<?= $tipe == 'homestay' ? 'active' : '' ?>">
            <span>Homestay</span>
        </a>
        <a href="?tipe=hotel" class="<?= $tipe == 'hotel' ? 'active' : '' ?>">
            <span>Hotel</span>
        </a>
        <a href="?tipe=villa" class="<?= $tipe == 'villa' ? 'active' : '' ?>">
            <span>Villa</span>
        </a>
    </div>

    <!-- GRID CARDS -->
    <?php if ($jumlahData > 0): ?>
    <div class="grid">
    <?php while($row = mysqli_fetch_assoc($query)): 
        $gambar = $row['gambar_thumbnail'] ? '../../' . $row['gambar_thumbnail'] : '../../uploads/penginapan/default.jpg';
        $total_tipe = $row['total_tipe_kamar'] ?? 0;
        $total_unit = $row['total_unit_kamar'] ?? 0;
    ?>
        <div class="card">
            <div class="card-image-wrapper">
                <img src="<?= $gambar ?>" alt="<?= htmlspecialchars($row['nama_penginapan']) ?>">
                <span class="card-badge badge-<?= strtolower($row['tipe_penginapan']) ?>">
                    <?= strtoupper($row['tipe_penginapan']) ?>
                </span>
            </div>
            
            <div class="card-body">
                <h3 class="card-title"><?= htmlspecialchars($row['nama_penginapan']) ?></h3>
                
                <div class="card-info">
                    <div class="info-row">
                        <span class="info-icon"><i class="fa-solid fa-location-dot"></i></span>
                        <span><?= htmlspecialchars($row['nama_kecamatan'] ?? 'Lokasi') ?>, <?= htmlspecialchars($row['nama_kabupaten'] ?? 'Yogyakarta') ?></span>
                    </div>
                </div>
                
                <div class="info-highlight">
                    <div class="info-highlight-item">
                        <div class="info-highlight-label">Tipe Kamar</div>
                        <div class="info-highlight-value"><?= $total_tipe ?></div>
                    </div>
                    <div class="info-divider"></div>
                    <div class="info-highlight-item">
                        <div class="info-highlight-label">Total Unit</div>
                        <div class="info-highlight-value"><?= $total_unit ?></div>
                    </div>
                </div>
                
                <div class="card-price">
                    Mulai Rp <?= number_format($row['harga_mulai'] ?? 0, 0, ',', '.') ?> <small>/malam</small>
                </div>
                
                <div class="card-actions">
                    <a href="detail_penginapan.php?id=<?= $row['id_penginapan'] ?>" class="btn-action btn-view">
                        <i class="fa-solid fa-eye"></i>
                        <span>Detail</span>
                    </a>

                    <a href="edit_penginapan.php?id=<?= $row['id_penginapan'] ?>" class="btn-action btn-edit">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>

                    <a href="javascript:void(0)" 
                    onclick="openDelete(<?= $row['id_penginapan'] ?>, '<?= strtolower($tipe) ?>')" 
                    class="btn-action btn-delete">
                        <i class="fa-solid fa-trash"></i>
                    </a>

                </div>
            </div>
        </div>
    <?php endwhile; ?>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPage > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?tipe=<?= $tipe ?>&page=<?= $page - 1 ?>">← Prev</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPage; $i++): ?>
            <a href="?tipe=<?= $tipe ?>&page=<?= $i ?>" class="<?= $page == $i ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPage): ?>
            <a href="?tipe=<?= $tipe ?>&page=<?= $page + 1 ?>">Next →</a>
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
            <span>Tambah Penginapan</span>
        </a>
    </div>
    <?php endif; ?>

</div>

<!-- MODAL DELETE -->
<div id="deleteModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-icon"></div>
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

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDelete();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDelete();
    }
});

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

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
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

    // LOG AKTIVITAS ADMIN 
    if (isset($_SESSION['id_user'])) {
        $adminId = intval($_SESSION['id_user']);
        $aksi = "Hapus penginapan ID $id";
        mysqli_query($conn, "
            INSERT INTO log_aktivitas_admin (id_admin, aktivitas, deskripsi, created_at)
            VALUES ($adminId, 'Hapus Penginapan', '".mysqli_real_escape_string($conn,$aksi)."', NOW())
        ");
    }

    $from_tipe = isset($_GET['from_tipe']) ? $_GET['from_tipe'] : 'homestay';
    header("Location: inventori.php?tipe=$from_tipe&success=delete");
    exit;
}

/* PARAMETER */
$tipe = isset($_GET['tipe']) ? mysqli_real_escape_string($conn, strtolower($_GET['tipe'])) : 'homestay';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$start = ($page - 1) * $limit;

/* TOTAL DATA */
$totalQuery = mysqli_query($conn, "
    SELECT COUNT(DISTINCT p.id_penginapan) AS total
    FROM penginapan p
    WHERE LOWER(p.tipe_penginapan) = '$tipe'
");
$totalData = mysqli_fetch_assoc($totalQuery)['total'] ?? 0;
$totalPage = ceil($totalData / $limit);

/* DATA PENGINAPAN DENGAN TIPE KAMAR */
$query = mysqli_query($conn, "
    SELECT
        p.id_penginapan,
        p.nama_penginapan,
        p.tipe_penginapan,
        p.alamat,
        p.deskripsi,
        kc.nama_kecamatan,
        kb.nama_kabupaten,
        tk.id_tipe_kamar,
        tk.nama_tipe,
        tk.jumlah_kamar,
        tk.harga_per_malam,
        GROUP_CONCAT(DISTINCT f.nama_fasilitas SEPARATOR ', ') as fasilitas,
        GROUP_CONCAT(DISTINCT CONCAT(kp.jenis_kontak, ':', kp.isi_kontak) SEPARATOR '|') as kontak
    FROM penginapan p
    LEFT JOIN kecamatan kc ON p.id_kecamatan = kc.id_kecamatan
    LEFT JOIN kabupaten kb ON p.id_kabupaten = kb.id_kabupaten
    LEFT JOIN tipe_kamar tk ON p.id_penginapan = tk.id_penginapan
    LEFT JOIN penginapan_fasilitas pf ON p.id_penginapan = pf.id_penginapan
    LEFT JOIN fasilitas f ON pf.id_fasilitas = f.id_fasilitas
    LEFT JOIN kontak_penginapan kp ON p.id_penginapan = kp.id_penginapan
    WHERE LOWER(p.tipe_penginapan) = '$tipe'
    GROUP BY p.id_penginapan, tk.id_tipe_kamar
    ORDER BY p.nama_penginapan, tk.nama_tipe
    LIMIT $start, $limit
");

if (!$query) {
    die("Error query: " . mysqli_error($conn));
}

$jumlahData = mysqli_num_rows($query);

// Group by penginapan
$grouped_data = [];
while($row = mysqli_fetch_assoc($query)) {
    $id = $row['id_penginapan'];
    if (!isset($grouped_data[$id])) {
        $grouped_data[$id] = [
            'info' => $row,
            'tipe_kamar' => []
        ];
    }
    if ($row['id_tipe_kamar']) {
        $grouped_data[$id]['tipe_kamar'][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Inventori Kamar & Harga - YogyaStay</title>
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
    margin-bottom: 20px;
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

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    border-bottom: 4px solid #f59e0b;
    padding-bottom: 5px;
    display: inline-block;
}

.btn-add {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    padding: 12px 24px;
    border-radius: 12px;
    text-decoration: none;
    color: white;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3);
}

.btn-add:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(251, 191, 36, 0.4);
}

/* TABS */
.tabs {
    display: flex;
    gap: 10px;
    margin: 25px 0;
}

.tabs a {
    text-decoration: none;
    font-weight: 600;
    color: #64748b;
    padding: 10px 20px;
    border-radius: 8px;
    transition: all 0.3s;
    background: white;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tabs a:hover {
    background: #fef3c7;
    color: #92400e;
}

.tabs a.active {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
}

/* TABLE */
.table-container {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1200px;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
    white-space: nowrap;
}

tbody tr:hover {
    background: #f9fafb;
}

.desc-cell {
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ACTIONS */
.action-btns {
    display: flex;
    gap: 8px;
}

.btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
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

/* PAGINATION */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 25px;
}

.pagination a, .pagination span {
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
    color: #374151;
    font-weight: 600;
    transition: all 0.3s;
    background: white;
    border: 1px solid #e5e7eb;
}

.pagination a:hover {
    background: #fef3c7;
    border-color: #fbbf24;
}

.pagination .active {
    background: #f59e0b;
    color: white;
    border-color: #f59e0b;
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 60px 30px;
    background: white;
    border-radius: 16px;
}

.empty-icon {
    font-size: 80px;
    margin-bottom: 20px;
    opacity: 0.3;
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
    .tabs {
        flex-direction: column;
    }
    .page-title {
        font-size: 22px;
    }
    .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
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
            <h1 class="page-title">Inventori Kamar & Harga</h1>
        </div>
        <a href="tambah_penginapan.php" class="btn-add">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Penginapan</span>
        </a>
    </div>

    <!-- TABS -->
    <div class="tabs">
        <a href="?tipe=homestay" class="<?= $tipe == 'homestay' ? 'active' : '' ?>">
            Homestay <span style="background: rgba(0,0,0,0.1); padding: 2px 8px; border-radius: 12px; font-size: 12px;"><?= getTotal($conn, "SELECT COUNT(*) AS total FROM penginapan WHERE tipe_penginapan='homestay'") ?></span>
        </a>
        <a href="?tipe=hotel" class="<?= $tipe == 'hotel' ? 'active' : '' ?>">
            Hotel <span style="background: rgba(0,0,0,0.1); padding: 2px 8px; border-radius: 12px; font-size: 12px;"><?= getTotal($conn, "SELECT COUNT(*) AS total FROM penginapan WHERE tipe_penginapan='hotel'") ?></span>
        </a>
        <a href="?tipe=villa" class="<?= $tipe == 'villa' ? 'active' : '' ?>">
            Villa <span style="background: rgba(0,0,0,0.1); padding: 2px 8px; border-radius: 12px; font-size: 12px;"><?= getTotal($conn, "SELECT COUNT(*) AS total FROM penginapan WHERE tipe_penginapan='villa'") ?></span>
        </a>
    </div>

    <!-- TABLE -->
    <?php if(count($grouped_data) > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Penginapan</th>
                    <th>Tipe Kamar</th>
                    <th>Total Unit</th>
                    <th>Sisa Kamar</th>
                    <th>Harga/Malam</th>
                    <th>Deskripsi</th>
                    <th>Fasilitas</th>
                    <th>Lokasi</th>
                    <th>Kontak</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = $start + 1;
                foreach($grouped_data as $id => $data): 
                    $rowspan = max(1, count($data['tipe_kamar']));
                    $first = true;
                    
                    if(empty($data['tipe_kamar'])) {
                        // Jika tidak ada tipe kamar
                        $info = $data['info'];
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><strong><?= htmlspecialchars($info['nama_penginapan']) ?></strong></td>
                    <td colspan="3" style="text-align: center; color: #6b7280;">Belum ada tipe kamar</td>
                    <td class="desc-cell"><?= htmlspecialchars($info['deskripsi'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($info['fasilitas'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($info['nama_kecamatan'] ?? '-') ?>, <?= htmlspecialchars($info['nama_kabupaten'] ?? '-') ?></td>
                    <td>
                        <?php 
                        if($info['kontak']) {
                            $kontaks = explode('|', $info['kontak']);
                            foreach($kontaks as $k) {
                                if($k) {
                                    list($jenis, $isi) = explode(':', $k);
                                    echo htmlspecialchars($isi).'<br>';
                                }
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="detail_penginapan.php?id=<?= $info['id_penginapan'] ?>" class="btn-icon btn-view" title="Lihat">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="edit_penginapan.php?id=<?= $info['id_penginapan'] ?>" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <button onclick="confirmDelete(<?= $info['id_penginapan'] ?>, '<?= $tipe ?>')" class="btn-icon btn-delete" title="Hapus">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php
                    } else {
                        // Jika ada tipe kamar
                        foreach($data['tipe_kamar'] as $tk):
                            $info = $data['info'];
                ?>
                <tr>
                    <?php if($first): ?>
                    <td rowspan="<?= $rowspan ?>"><?= $no++ ?></td>
                    <td rowspan="<?= $rowspan ?>"><strong><?= htmlspecialchars($info['nama_penginapan']) ?></strong></td>
                    <?php endif; ?>
                    
                    <td><?= htmlspecialchars($tk['nama_tipe'] ?? '-') ?></td>
                    <td><?= $tk['jumlah_kamar'] ?? 0 ?></td>
                    <td><?= $tk['jumlah_kamar'] ?? 0 ?></td>
                    <td>Rp <?= number_format($tk['harga_per_malam'] ?? 0, 0, ',', '.') ?></td>
                    
                    <?php if($first): ?>
                    <td rowspan="<?= $rowspan ?>" class="desc-cell"><?= htmlspecialchars($info['deskripsi'] ?? '-') ?></td>
                    <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($info['fasilitas'] ?? '-') ?></td>
                    <td rowspan="<?= $rowspan ?>"><?= htmlspecialchars($info['nama_kecamatan'] ?? '-') ?>, <?= htmlspecialchars($info['nama_kabupaten'] ?? '-') ?></td>
                    <td rowspan="<?= $rowspan ?>">
                        <?php 
                        if($info['kontak']) {
                            $kontaks = explode('|', $info['kontak']);
                            foreach($kontaks as $k) {
                                if($k) {
                                    list($jenis, $isi) = explode(':', $k);
                                    echo htmlspecialchars($isi).'<br>';
                                }
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                    <td rowspan="<?= $rowspan ?>">
                        <div class="action-btns">
                            <a href="detail_penginapan.php?id=<?= $info['id_penginapan'] ?>" class="btn-icon btn-view" title="Lihat">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="edit_penginapan.php?id=<?= $info['id_penginapan'] ?>" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <button onclick="confirmDelete(<?= $info['id_penginapan'] ?>, '<?= $tipe ?>')" class="btn-icon btn-delete" title="Hapus">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php
                            $first = false;
                        endforeach;
                    }
                endforeach; 
                ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPage > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?tipe=<?= $tipe ?>&page=<?= $page - 1 ?>">Previous</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPage; $i++): ?>
            <?php if($i == $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="?tipe=<?= $tipe ?>&page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $totalPage): ?>
            <a href="?tipe=<?= $tipe ?>&page=<?= $page + 1 ?>">Next</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">📦</div>
        <h3>Belum Ada <?= ucfirst($tipe) ?></h3>
        <p>Mulai tambahkan penginapan <?= $tipe ?> pertama Anda</p>
    </div>
    <?php endif; ?>

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

function confirmDelete(id, tipe) {
    if(confirm('Yakin ingin menghapus penginapan ini? Semua data terkait akan ikut terhapus.')) {
        window.location.href = '?delete=' + id + '&from_tipe=' + tipe;
    }
}
</script>

</body>
</html>

<?php
function getTotal($conn, $query) {
    $res = mysqli_query($conn, $query);
    if (!$res) return 0;
    $row = mysqli_fetch_assoc($res);
    return $row['total'] ?? 0;
}
?>
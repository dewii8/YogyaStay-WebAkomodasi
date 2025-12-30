<?php
require_once '../../config.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 'admin') {
    header("Location: ../../autentikasi/login.php");
    exit();
}

// Fungsi untuk mendapatkan total
function getTotal($conn, $query) {
    $res = mysqli_query($conn, $query);
    if (!$res) return 0;
    $row = mysqli_fetch_assoc($res);
    return $row['total'] ?? 0;
}

// Filter berdasarkan jenis aksi
$filter_aksi = isset($_GET['aksi']) ? $_GET['aksi'] : 'semua';

// Filter berdasarkan tanggal
$filter_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : 'semua';

/* ================= STATISTIK CARD ================= */
$total_aktivitas = getTotal($conn, "SELECT COUNT(*) AS total FROM log_aktivitas_admin");
$aktivitas_hari_ini = getTotal($conn, "SELECT COUNT(*) AS total FROM log_aktivitas_admin WHERE DATE(created_at) = CURDATE()");
$aktivitas_minggu_ini = getTotal($conn, "SELECT COUNT(*) AS total FROM log_aktivitas_admin WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE())");

/* ================= BUILD WHERE CLAUSE ================= */
$where_conditions = [];

// Filter berdasarkan jenis aksi
if($filter_aksi !== 'semua') {
    $filter_aksi_safe = mysqli_real_escape_string($conn, $filter_aksi);
    $where_conditions[] = "l.aksi LIKE '%$filter_aksi_safe%'";
}

// Filter berdasarkan tanggal
switch($filter_tanggal) {
    case 'hari_ini':
        $where_conditions[] = "DATE(l.created_at) = CURDATE()";
        break;
    case 'minggu_ini':
        $where_conditions[] = "YEARWEEK(l.created_at) = YEARWEEK(CURDATE())";
        break;
    case 'bulan_ini':
        $where_conditions[] = "MONTH(l.created_at) = MONTH(CURDATE()) AND YEAR(l.created_at) = YEAR(CURDATE())";
        break;
}

$where_clause = "";
if(count($where_conditions) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

/* ================= QUERY LOG AKTIVITAS ================= */
$query_log = mysqli_query($conn, "
    SELECT 
        l.id_log,
        l.id_admin,
        l.aksi,
        l.deskripsi,
        l.created_at
    FROM log_aktivitas_admin l
    $where_clause
    ORDER BY l.created_at DESC
");

$total_filtered = mysqli_num_rows($query_log);

// Ambil jenis-jenis aksi yang ada untuk filter
$query_aksi_types = mysqli_query($conn, "
    SELECT DISTINCT aksi 
    FROM log_aktivitas_admin 
    ORDER BY aksi ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log Aktivitas Admin - YogyaStay</title>
 <link rel="icon" type="image/jpeg" href="../../assets/img/logonw.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
*{box-sizing:border-box;margin:0;padding:0}
body{
    font-family:'Poppins', sans-serif;
    background:#fff7ed;
    color:#1f2937;
}

.layout{display:flex;min-height:100vh}
.content{margin-left:240px;padding:30px;width:100%;max-width:1600px;transition:margin-left 0.3s ease}

.topbar{
    display:flex;
    gap:15px;
    align-items:center;
    margin-bottom:30px;
}

h1{
    margin:0;
    font-size:32px;
    font-weight:700;
    color:#1f2937;
    border-bottom:4px solid #f59e0b;
    display:inline-block;
    padding-bottom:8px;
}

/* Cards */
.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:25px;
    margin-top:30px;
    margin-bottom:40px;
}

.card{
    background:#fff;
    padding:25px;
    border-radius:16px;
    box-shadow:0 4px 20px rgba(0,0,0,.08);
    transition:transform 0.2s, box-shadow 0.2s;
}

.card:hover{
    transform:translateY(-5px);
    box-shadow:0 8px 30px rgba(0,0,0,.12);
}

.card.blue{border-left:6px solid #3b82f6}
.card.green{border-left:6px solid #10b981}
.card.purple{border-left:6px solid #8b5cf6}

.card small{
    color:#6b7280;
    font-size:14px;
    font-weight:500;
    display:block;
    margin-bottom:10px;
}

.card h2{
    font-size:36px;
    font-weight:700;
    color:#1f2937;
    margin:0;
}

/* Filter Section */
.filter-section{
    background:#fff;
    padding:25px 30px;
    border-radius:16px;
    box-shadow:0 4px 20px rgba(0,0,0,.08);
    margin-bottom:25px;
}

.filter-row{
    display:flex;
    gap:20px;
    flex-wrap:wrap;
    align-items:center;
}

.filter-group{
    flex:1;
    min-width:200px;
}

.filter-label{
    display:block;
    font-size:14px;
    font-weight:600;
    color:#374151;
    margin-bottom:8px;
}

.filter-select{
    width:100%;
    padding:10px 15px;
    border:2px solid #e5e7eb;
    border-radius:8px;
    font-size:14px;
    font-family:'Poppins', sans-serif;
    background:white;
    cursor:pointer;
    transition:border-color 0.3s;
}

.filter-select:focus{
    outline:none;
    border-color:#f59e0b;
}

.btn-filter{
    padding:10px 25px;
    background:#f59e0b;
    color:white;
    border:none;
    border-radius:8px;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
    transition:all 0.3s;
    margin-top:24px;
}

.btn-filter:hover{
    background:#d97706;
    transform:translateY(-2px);
}

.btn-reset{
    padding:10px 25px;
    background:#e5e7eb;
    color:#4b5563;
    border:none;
    border-radius:8px;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
    transition:all 0.3s;
    margin-top:24px;
    text-decoration:none;
    display:inline-block;
}

.btn-reset:hover{
    background:#d1d5db;
}

/* Table Section */
.table-section{
    background:#fff;
    padding:30px;
    border-radius:16px;
    box-shadow:0 4px 20px rgba(0,0,0,.08);
}

.table-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.table-section h3{
    font-size:20px;
    font-weight:600;
    color:#1f2937;
    margin:0;
}

.result-count{
    font-size:14px;
    color:#666;
    background:#f3f4f6;
    padding:8px 16px;
    border-radius:8px;
    font-weight:600;
}

.table-wrapper{
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
    min-width:900px;
}

thead{
    background:#f9fafb;
}

th{
    padding:15px 12px;
    text-align:left;
    font-weight:600;
    color:#374151;
    font-size:13px;
    border-bottom:2px solid #e5e7eb;
    text-transform:uppercase;
    letter-spacing:0.5px;
}

td{
    padding:18px 12px;
    font-size:14px;
    color:#4b5563;
    border-bottom:1px solid #e5e7eb;
}

tr:hover{
    background:#f9fafb;
}

/* Badge Aksi */
.badge-aksi{
    display:inline-block;
    padding:6px 12px;
    border-radius:6px;
    font-size:12px;
    font-weight:600;
}

.badge-tambah{
    background:#d1fae5;
    color:#065f46;
}

.badge-edit{
    background:#dbeafe;
    color:#1e40af;
}

.badge-hapus{
    background:#fee2e2;
    color:#991b1b;
}

.badge-update{
    background:#fef3c7;
    color:#92400e;
}

.badge-approve{
    background:#d1fae5;
    color:#065f46;
}

.badge-tolak{
    background:#fee2e2;
    color:#991b1b;
}

/* Badge khusus untuk inventori kamar */
.badge-kamar{
    background:#e9d5ff;
    color:#6b21a8;
}

.badge-stok{
    background:#fef3c7;
    color:#92400e;
}

.badge-default{
    background:#f3f4f6;
    color:#4b5563;
}

.time-badge{
    display:inline-block;
    padding:4px 10px;
    background:#f3f4f6;
    border-radius:6px;
    font-size:12px;
    color:#6b7280;
    font-weight:500;
}

.deskripsi-cell{
    max-width:400px;
    line-height:1.6;
}

/* Empty State */
.empty-state{
    text-align:center;
    padding:60px 20px;
}

.empty-icon{
    font-size:80px;
    color:#d1d5db;
    margin-bottom:20px;
}

.empty-title{
    font-size:20px;
    font-weight:600;
    color:#1f2937;
    margin-bottom:10px;
}

.empty-text{
    font-size:14px;
    color:#6b7280;
}

@media(max-width:968px){
    .content{margin-left:0;padding:20px}
    .cards{grid-template-columns:1fr}
    h1{font-size:24px}
    .filter-row{flex-direction:column}
    .filter-group{width:100%}
}
</style>
</head>

<body>

<div class="layout">
<?php include '../partials/sidebar.php'; ?>

<div class="content">
    <div class="topbar">
        <h1>Log Aktivitas Admin</h1>
    </div>

    <!-- CARDS -->
    <div class="cards">
        <div class="card blue">
            <small>Total Aktivitas</small>
            <h2><?= $total_aktivitas ?></h2>
        </div>
        <div class="card green">
            <small>Aktivitas Hari Ini</small>
            <h2><?= $aktivitas_hari_ini ?></h2>
        </div>
        <div class="card purple">
            <small>Aktivitas Minggu Ini</small>
            <h2><?= $aktivitas_minggu_ini ?></h2>
        </div>
    </div>

    <!-- FILTER SECTION -->
    <div class="filter-section">
        <form method="GET" action="">
            <div class="filter-row">
                <div class="filter-group">
                    <label class="filter-label">Jenis Aksi</label>
                    <select name="aksi" class="filter-select">
                        <option value="semua" <?= $filter_aksi == 'semua' ? 'selected' : '' ?>>Semua Aksi</option>
                        <?php while($aksi_type = mysqli_fetch_assoc($query_aksi_types)): ?>
                            <option value="<?= htmlspecialchars($aksi_type['aksi']) ?>" 
                                    <?= $filter_aksi == $aksi_type['aksi'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($aksi_type['aksi']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Periode Waktu</label>
                    <select name="tanggal" class="filter-select">
                        <option value="semua" <?= $filter_tanggal == 'semua' ? 'selected' : '' ?>>Semua Waktu</option>
                        <option value="hari_ini" <?= $filter_tanggal == 'hari_ini' ? 'selected' : '' ?>>Hari Ini</option>
                        <option value="minggu_ini" <?= $filter_tanggal == 'minggu_ini' ? 'selected' : '' ?>>Minggu Ini</option>
                        <option value="bulan_ini" <?= $filter_tanggal == 'bulan_ini' ? 'selected' : '' ?>>Bulan Ini</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-filter">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                
                <a href="?" class="btn-reset">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <!-- TABLE -->
    <div class="table-section">
        <div class="table-header">
            <h3>Riwayat Aktivitas</h3>
            <span class="result-count">
                <?= $total_filtered ?> aktivitas ditemukan
            </span>
        </div>
        
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Aksi</th>
                        <th>Deskripsi</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($query_log && mysqli_num_rows($query_log) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($query_log)): ?>
                        <tr>
                            <td style="font-weight:600;color:#6b7280">#<?= $row['id_log'] ?></td>
                            
                            <td>
                                <?php
                                $aksi = strtolower($row['aksi']);
                                $badge_class = 'badge-default';
                                
                                // Deteksi jenis aksi untuk badge
                                if(strpos($aksi, 'tambah') !== false) {
                                    $badge_class = 'badge-tambah';
                                } elseif(strpos($aksi, 'edit') !== false || strpos($aksi, 'update') !== false || strpos($aksi, 'ubah') !== false) {
                                    $badge_class = 'badge-edit';
                                } elseif(strpos($aksi, 'hapus') !== false) {
                                    $badge_class = 'badge-hapus';
                                } elseif(strpos($aksi, 'approve') !== false) {
                                    $badge_class = 'badge-approve';
                                } elseif(strpos($aksi, 'tolak') !== false || strpos($aksi, 'reject') !== false) {
                                    $badge_class = 'badge-tolak';
                                } 
                                
                                // Badge khusus untuk aktivitas kamar
                                if(strpos($aksi, 'kamar') !== false) {
                                    if(strpos($aksi, 'tambah') !== false) {
                                        $badge_class = 'badge-tambah';
                                    } elseif(strpos($aksi, 'edit') !== false || strpos($aksi, 'update') !== false) {
                                        $badge_class = 'badge-kamar';
                                    } elseif(strpos($aksi, 'hapus') !== false) {
                                        $badge_class = 'badge-hapus';
                                    } elseif(strpos($aksi, 'stok') !== false) {
                                        $badge_class = 'badge-stok';
                                    }
                                }
                                ?>
                                <span class="badge-aksi <?= $badge_class ?>">
                                    <?= htmlspecialchars($row['aksi']) ?>
                                </span>
                            </td>
                            
                            <td class="deskripsi-cell">
                                <?= htmlspecialchars($row['deskripsi']) ?>
                            </td>
                            
                            <td>
                                <span class="time-badge">
                                    <i class="bi bi-clock"></i>
                                    <?= date('d M Y, H:i', strtotime($row['created_at'])) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <div class="empty-icon">📝</div>
                                    <h4 class="empty-title">Tidak Ada Aktivitas</h4>
                                    <p class="empty-text">
                                        Belum ada aktivitas yang tercatat dengan filter yang dipilih
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

</body>
</html>
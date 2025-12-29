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
$logs_per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $logs_per_page;

// QUERY LOG 
$query = mysqli_query($conn, "
    SELECT l.*, u.nama AS admin_name, u.email AS admin_email
    FROM log_aktivitas_admin l
    LEFT JOIN users u ON l.id_admin = u.id_user
    ORDER BY l.created_at DESC
    LIMIT $logs_per_page OFFSET $offset
");

if (!$query) {
    die("Query Error: " . mysqli_error($conn));
}

// TOTAL LOG 
$total_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM log_aktivitas_admin");
$total = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total / $logs_per_page);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log Aktivitas Admin - YogyaStay</title>
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

.log-dashboard { background:#fff; border-radius:20px; padding:30px; box-shadow:0 6px 20px rgba(0,0,0,0.08); overflow-x:auto; }

table { width:100%; border-collapse:collapse; min-width:800px; }
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

.badge-info { background: #dbeafe; color: #1e40af; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-danger { background: #fee2e2; color: #991b1b; }

.pagination { display:flex; flex-wrap:wrap; justify-content:center; margin-top:25px; gap: 5px; }
.pagination a { padding:10px 16px; border-radius:8px; background:#f8f9fa; color:#555; text-decoration:none; transition:0.3s; font-size:14px; font-weight: 600; }
.pagination a:hover { background:#fde68a; }
.pagination a.active { background:#f59e0b; color:#fff; }

@media(max-width:768px){ 
    .main-content { margin-left:0; padding:20px; } 
    .hamburger { display: flex; }
    table { font-size: 12px; min-width: 700px; }
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
            <h2>📋 Log Aktivitas Admin</h2>
        </div>
    </div>

    <section class="log-dashboard">
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Waktu</th>
                    <th>Admin</th>
                    <th>Aktivitas</th>
                    <th>Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = $offset + 1; while($log = mysqli_fetch_assoc($query)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td style="white-space: nowrap;">
                        <i class="far fa-clock" style="color: #6b7280;"></i> 
                        <?= date('d M Y, H:i', strtotime($log['created_at'])); ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($log['admin_name'] ?? 'System'); ?></strong><br>
                        <small style="color: #6b7280;"><?= htmlspecialchars($log['admin_email'] ?? '-'); ?></small>
                    </td>
                    <td>
                        <span class="badge badge-info">
                            <?= htmlspecialchars($log['aktivitas']); ?>
                        </span>
                    </td>
                    <td style="max-width: 400px;">
                        <?= htmlspecialchars($log['deskripsi']); ?>
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
<?php
require_once '../../config.php';

//  PAGINATION 
$logs_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $logs_per_page;

//  QUERY LOG 
$query = mysqli_query($conn, "
    SELECT l.*, u.nama AS admin_name, u.email AS admin_email
    FROM log_aktivitas_admin l
    JOIN users u ON l.id_admin = u.id_user
    ORDER BY l.created_at DESC
    LIMIT $logs_per_page OFFSET $offset
");

if (!$query) {
    die("Query Error: " . mysqli_error($conn));
}

//  TOTAL LOG 
$total_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM log_aktivitas_admin");
$total = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total / $logs_per_page);

//  INCLUDE SIDEBAR 
if (file_exists('../partials/sidebar.php')) {
    include '../partials/sidebar.php';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log Aktivitas Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">

<style>
body { font-family: 'Poppins', sans-serif; background:#fff7ed; margin:0; padding:0; }
.main-content { margin-left:240px; padding:40px; transition:0.3s; }
h2 { font-size:28px; color:#333; margin-bottom:20px; }
.log-dashboard { background:#fff; border-radius:12px; padding:30px; box-shadow:0 6px 20px rgba(0,0,0,0.08); overflow-x:auto; }
table { width:100%; border-collapse:collapse; min-width:700px; }
th, td { padding:12px 15px; border-bottom:1px solid #e0e0e0; text-align:left; }
th { background:#f1f3f6; font-weight:600; color:#555; }
tr:hover { background:#f9f9f9; transition:0.2s; }
.pagination { display:flex; flex-wrap:wrap; justify-content:center; margin-top:25px; }
.pagination a { margin:5px; padding:10px 14px; border-radius:6px; background:#f8f9fa; color:#555; text-decoration:none; transition:0.3s; font-size:14px; }
.pagination a:hover { background:#d1d1d1; }
.pagination a.active { background:#5e6b8d; color:#fff; }
#sidebar { width:240px; position:fixed; top:0; left:0; height:100%; background:#9aa7c5; color:#fff; padding:20px 10px; box-sizing:border-box; transition: transform 0.3s ease; z-index:1002; }
#sidebar ul { list-style:none; padding:0; margin:0; }
#sidebar ul li { margin-bottom:15px; }
#sidebar ul li a { color:#fff; text-decoration:none; font-weight:600; display:block; padding:10px 12px; border-radius:6px; }
#sidebar.show { transform:translateX(0); }
#hamburger { display:none; position:fixed; top:15px; left:15px; font-size:24px; cursor:pointer; z-index:1001; color:#9aa7c5; }
#sidebar-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; }
#sidebar-overlay.show { display:block; }
@media(max-width:768px){ .main-content { margin-left:0; padding:20px; } #hamburger { display:block; } }
</style>
</head>
<body>

<div id="hamburger"><i class="fas fa-bars"></i></div>
<div id="sidebar-overlay"></div>

<div class="main-content">
    <section class="log-dashboard">
        <h2>Log Aktivitas Admin</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Admin</th>
                    <th>Aksi</th>
                    <th>Deskripsi</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = $offset + 1; while($log = mysqli_fetch_assoc($query)): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($log['admin_name']); ?> (<?= htmlspecialchars($log['admin_email']); ?>)</td>
                    <td><?= htmlspecialchars($log['aksi']); ?></td>
                    <td><?= htmlspecialchars($log['deskripsi']); ?></td>
                    <td><?= $log['created_at']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php
            $range = 2;
            if($page > 1) echo '<a href="?page=1">&laquo;</a>';
            if($page > 1) echo '<a href="?page='.($page-1).'">&lt;</a>';
            for($i = max(1,$page-$range); $i <= min($total_pages,$page+$range); $i++){
                echo '<a href="?page='.$i.'" class="'.($i==$page?'active':'').'">'.$i.'</a>';
            }
            if($page < $total_pages) echo '<a href="?page='.($page+1).'">&gt;</a>';
            if($page < $total_pages) echo '<a href="?page='.$total_pages.'">&raquo;</a>';
            ?>
        </div>
    </section>
</div>

<script>
const hamburger = document.getElementById('hamburger');
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebar-overlay');

hamburger.addEventListener('click', () => {
    sidebar.classList.toggle('show');
    sidebarOverlay.classList.toggle('show');
});
sidebarOverlay.addEventListener('click', () => {
    sidebar.classList.remove('show');
    sidebarOverlay.classList.remove('show');
});
</script>

</body>
</html>

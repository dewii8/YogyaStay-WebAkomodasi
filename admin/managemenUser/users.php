<?php
require_once '../../config.php';

//  PAGINATION 
$users_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $users_per_page;

//  QUERY 
$query = mysqli_query($conn, "
    SELECT id_user, nama, email, status
    FROM users
    ORDER BY nama ASC
    LIMIT $users_per_page OFFSET $offset
");

$total_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$total = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total / $users_per_page);

//  HAPUS USER 
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Ambil nama/email untuk deskripsi log
    $user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama,email FROM users WHERE id_user=$delete_id"));
    
    mysqli_query($conn, "DELETE FROM users WHERE id_user=$delete_id");
    
    //  LOG AKTIVITAS ADMIN 
    $aksi = "Hapus User";
    $deskripsi = "Admin ID ".$_SESSION['user_id']." menghapus user ".$user_data['nama']." (".$user_data['email'].")";
    mysqli_query($conn, "INSERT INTO log_aktivitas_admin (id_admin, aksi, deskripsi) VALUES ({$_SESSION['user_id']}, '$aksi', '$deskripsi')");
    
    header("Location: users.php?page=$page");
    exit;
}

//  EDIT STATUS 
if (isset($_POST['edit_status'])) {
    $edit_id = (int)$_POST['user_id'];
    $new_status = $_POST['status'] === 'aktif' ? 'aktif' : 'nonaktif';
    
    // Update status user
    mysqli_query($conn, "UPDATE users SET status='$new_status' WHERE id_user=$edit_id");
    
    //  LOG AKTIVITAS ADMIN 
    $aksi = "Update Status User";
    $deskripsi = "Admin ID ".$_SESSION['user_id']." mengubah status user ID $edit_id menjadi $new_status";
    mysqli_query($conn, "INSERT INTO log_aktivitas_admin (id_admin, aksi, deskripsi) VALUES ({$_SESSION['user_id']}, '$aksi', '$deskripsi')");
    
    // Redirect 
    header("Location: users.php?page=$page");
    exit;
}


//  SIDEBAR 
if (file_exists('../partials/sidebar.php')) {
    include '../partials/sidebar.php'; 
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen User</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background:#fff7ed; margin:0; padding:0; }
.main-content { margin-left:240px; padding:40px; transition:0.3s; }
h1 { font-size:28px; color:#333; margin-bottom:20px; }
.user-dashboard { background:#fff; border-radius:12px; padding:30px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }

/*  TABLE  */
.user-list { overflow-x:auto; }
.user-list table { width:100%; min-width:600px; border-collapse:collapse; }
.user-list th, .user-list td { padding:12px 15px; border-bottom:1px solid #e0e0e0; text-align:left; }
.user-list th { background:#f1f3f6; font-weight:600; color:#555; }
.user-list tr:hover { background:#f9f9f9; transition:0.2s; }

/*  ACTION BUTTONS  */
.action-buttons { display:flex; gap:8px; flex-wrap:wrap; }
.action-buttons a, .action-buttons button { display:inline-flex; align-items:center; padding:6px 12px; border-radius:6px; font-weight:500; text-decoration:none; cursor:pointer; border:none; transition:0.3s; font-size:14px; }
.action-buttons a { background:#5e6b8d; color:#fff; }
.action-buttons a:hover { background:#4a5363; }
.action-buttons .delete-button { background:#dc3545; color:#fff; }
.action-buttons .delete-button:hover { background:#c82333; }
.action-buttons button { background:#ffc107; color:#000; }
.action-buttons i { margin-right:6px; }

/*  STATUS LABEL  */
.status-aktif { color:#155724; background-color:#d4edda; padding:5px 10px; border-radius:6px; font-weight:600; font-size:14px; }
.status-nonaktif { color:#856404; background-color:#fff3cd; padding:5px 10px; border-radius:6px; font-weight:600; font-size:14px; }

/*  PAGINATION  */
.pagination { display:flex; flex-wrap:wrap; justify-content:center; margin-top:25px; }
.pagination a { margin:5px; padding:10px 14px; border-radius:6px; background:#f8f9fa; color:#555; text-decoration:none; transition:0.3s; font-size:14px; }
.pagination a:hover { background:#d1d1d1; }
.pagination a.active { background:#5e6b8d; color:#fff; }

/*  SIDEBAR  */
#sidebar { width:240px; position:fixed; top:0; left:0; height:100%; background:#9aa7c5; color:#fff; padding:20px 10px; box-sizing:border-box; transition: transform 0.3s ease; z-index:1002; }
#sidebar ul { list-style:none; padding:0; margin:0; }
#sidebar ul li { margin-bottom:15px; }
#sidebar ul li a { color:#fff; text-decoration:none; font-weight:600; display:block; padding:10px 12px; border-radius:6px; }
#sidebar.show { transform:translateX(0); }

/*  HAMBURGER  */
#hamburger { display:none; position:fixed; top:15px; left:15px; font-size:24px; cursor:pointer; z-index:1001; color:#9aa7c5; }
#sidebar-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; }
#sidebar-overlay.show { display:block; }

/*  RESPONSIVE  */
@media(max-width:768px){
    .main-content { margin-left:0; padding:20px; }
    #hamburger { display:block; }
    #sidebar { transform:translateX(-100%); }
    .action-buttons { flex-direction:column; gap:5px; }
    .action-buttons a, .action-buttons button { width:100%; justify-content:center; }
    .user-list table { min-width:100%; }
}
</style>
</head>
<body>
<div id="hamburger"><i class="fas fa-bars"></i></div>
<div id="sidebar-overlay"></div>

<div class="main-content">
    <section class="user-dashboard">
        <h1>Manajemen User</h1>
        <div class="user-list">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nama']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td>
                            <?php if($row['status']=='aktif'): ?>
                                <span class="status-aktif">Aktif</span>
                            <?php else: ?>
                                <span class="status-nonaktif">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="action-buttons">
                            <form method="POST" action="users.php?page=<?= $page; ?>" style="margin:0;">
                                <input type="hidden" name="user_id" value="<?= $row['id_user']; ?>">
                                <select name="status" onchange="this.form.submit()" style="padding:6px 8px; border-radius:6px; border:1px solid #ccc;">
                                    <option value="aktif" <?= $row['status']=='aktif'?'selected':''; ?>>Aktif</option>
                                    <option value="nonaktif" <?= $row['status']=='nonaktif'?'selected':''; ?>>Nonaktif</option>
                                </select>
                                <input type="hidden" name="edit_status" value="1">
                            </form>
                            <a href="users.php?delete_id=<?= $row['id_user']; ?>&page=<?= $page; ?>" onclick="return confirm('Hapus user ini?')" class="delete-button"><i class="fas fa-trash"></i> Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <?php
            $range = 2;
            $pagination = [];
            if($page > 1) $pagination[] = '<a href="users.php?page=1">&laquo;</a>';
            if($page > 1) $pagination[] = '<a href="users.php?page='.($page-1).'">&lt;</a>';
            for($i=max(1,$page-$range); $i<=min($total_pages,$page+$range); $i++){
                $pagination[] = '<a href="users.php?page='.$i.'" class="'.($i==$page?'active':'').'">'.$i.'</a>';
            }
            if($page < $total_pages) $pagination[] = '<a href="users.php?page='.($page+1).'">&gt;</a>';
            if($page < $total_pages) $pagination[] = '<a href="users.php?page='.$total_pages.'">&raquo;</a>';
            echo implode('', $pagination);
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

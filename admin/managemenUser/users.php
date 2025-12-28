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
$users_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $users_per_page;

// QUERY 
$query = mysqli_query($conn, "
    SELECT id_user, nama, email, role_id, status, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT $users_per_page OFFSET $offset
");

$total_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$total = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total / $users_per_page);

// HAPUS USER 
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Ambil nama/email untuk deskripsi log
    $user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama,email FROM users WHERE id_user=$delete_id"));
    
    mysqli_query($conn, "DELETE FROM users WHERE id_user=$delete_id");
    
    // LOG AKTIVITAS ADMIN 
    if(isset($_SESSION['id_user'])) {
        $aksi = "Hapus User";
        $deskripsi = "Admin menghapus user ".$user_data['nama']." (".$user_data['email'].")";
        mysqli_query($conn, "INSERT INTO log_aktivitas_admin (id_admin, aktivitas, deskripsi, created_at) VALUES ({$_SESSION['id_user']}, '$aksi', '$deskripsi', NOW())");
    }
    
    header("Location: users.php?page=$page&success=deleted");
    exit;
}

// EDIT STATUS 
if (isset($_POST['edit_status'])) {
    $edit_id = (int)$_POST['user_id'];
    $new_status = $_POST['status'] === 'aktif' ? 'aktif' : 'nonaktif';
    
    // Update status user
    mysqli_query($conn, "UPDATE users SET status='$new_status' WHERE id_user=$edit_id");
    
    // LOG AKTIVITAS ADMIN 
    if(isset($_SESSION['id_user'])) {
        $aksi = "Update Status User";
        $deskripsi = "Admin mengubah status user ID $edit_id menjadi $new_status";
        mysqli_query($conn, "INSERT INTO log_aktivitas_admin (id_admin, aktivitas, deskripsi, created_at) VALUES ({$_SESSION['id_user']}, '$aksi', '$deskripsi', NOW())");
    }
    
    header("Location: users.php?page=$page&success=updated");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen User - YogyaStay</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Poppins', sans-serif; background:#fff7ed; }

.main-content { margin-left:240px; padding:40px; transition: 0.3s; }

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

h1 { font-size:28px; color:#333; margin:0; }

.alert {
    padding: 16px 24px;
    border-radius: 16px;
    margin-bottom: 25px;
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

.user-dashboard { background:#fff; border-radius:20px; padding:30px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }

.user-list { overflow-x:auto; }
.user-list table { width:100%; min-width:700px; border-collapse:collapse; }
.user-list th, .user-list td { padding:15px; border-bottom:1px solid #e0e0e0; text-align:left; }
.user-list th { background:#f1f3f6; font-weight:600; color:#555; }
.user-list tr:hover { background:#f9f9f9; transition:0.2s; }

.action-buttons { display:flex; gap:10px; flex-wrap:wrap; align-items: center; }
.action-buttons select { padding:8px 12px; border-radius:8px; border:1px solid #ddd; font-size: 14px; cursor: pointer; }
.action-buttons button { display:inline-flex; align-items:center; padding:8px 14px; border-radius:8px; font-weight:500; cursor:pointer; border:none; transition:0.3s; font-size:14px; }
.action-buttons .delete-button { background:#dc3545; color:#fff; }
.action-buttons .delete-button:hover { background:#c82333; }
.action-buttons i { margin-right:6px; }

.status-aktif { color:#155724; background-color:#d4edda; padding:6px 12px; border-radius:8px; font-weight:600; font-size:13px; }
.status-nonaktif { color:#856404; background-color:#fff3cd; padding:6px 12px; border-radius:8px; font-weight:600; font-size:13px; }

.badge {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
}

.badge-admin { background: #fee2e2; color: #991b1b; }
.badge-user { background: #dbeafe; color: #1e40af; }

.pagination { display:flex; flex-wrap:wrap; justify-content:center; margin-top:25px; gap: 5px; }
.pagination a { padding:10px 16px; border-radius:8px; background:#f8f9fa; color:#555; text-decoration:none; transition:0.3s; font-size:14px; font-weight: 600; }
.pagination a:hover { background:#fde68a; }
.pagination a.active { background:#f59e0b; color:#fff; }

@media(max-width:768px){
    .main-content { margin-left:0; padding:20px; }
    .hamburger { display: flex; }
    .action-buttons { flex-direction:column; gap:5px; }
    .action-buttons select, .action-buttons button { width:100%; }
    .user-list table { font-size: 13px; min-width:600px; }
    .user-list th, .user-list td { padding: 10px 8px; }
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
            <h1>👥 Manajemen User</h1>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <span style="font-size: 24px;">✅</span>
        <span>
            <?php if($_GET['success'] == 'updated'): ?>
                Status user berhasil diperbarui!
            <?php elseif($_GET['success'] == 'deleted'): ?>
                User berhasil dihapus!
            <?php endif; ?>
        </span>
    </div>
    <?php endif; ?>

    <section class="user-dashboard">
        <div class="user-list">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Terdaftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = $offset + 1; while($row = mysqli_fetch_assoc($query)): ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><strong><?= htmlspecialchars($row['nama']); ?></strong></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td>
                            <span class="badge badge-<?= $row['role_id'] ?>">
                                <?= ucfirst($row['role_id']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if($row['status']=='aktif'): ?>
                                <span class="status-aktif">Aktif</span>
                            <?php else: ?>
                                <span class="status-nonaktif">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d M Y', strtotime($row['created_at'])); ?></td>
                        <td class="action-buttons">
                            <?php if($row['id_user'] != ($_SESSION['id_user'] ?? 0)): ?>
                            <form method="POST" action="users.php?page=<?= $page; ?>" style="margin:0; display: flex; gap: 5px; align-items: center;">
                                <input type="hidden" name="user_id" value="<?= $row['id_user']; ?>">
                                <select name="status">
                                    <option value="aktif" <?= $row['status']=='aktif'?'selected':''; ?>>Aktif</option>
                                    <option value="nonaktif" <?= $row['status']=='nonaktif'?'selected':''; ?>>Nonaktif</option>
                                </select>
                                <button type="submit" name="edit_status" style="background:#f59e0b; color: #fff;">
                                    <i class="fas fa-check"></i> Update
                                </button>
                            </form>
                            <button onclick="confirmDelete(<?= $row['id_user']; ?>)" class="delete-button">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                            <?php else: ?>
                            <span style="color: #6b7280; font-size: 13px;">Akun Anda</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <?php
            if($page > 1) echo '<a href="users.php?page=1">«</a>';
            if($page > 1) echo '<a href="users.php?page='.($page-1).'">‹</a>';
            
            $range = 2;
            for($i=max(1,$page-$range); $i<=min($total_pages,$page+$range); $i++){
                echo '<a href="users.php?page='.$i.'" class="'.($i==$page?'active':'').'">'.$i.'</a>';
            }
            
            if($page < $total_pages) echo '<a href="users.php?page='.($page+1).'">›</a>';
            if($page < $total_pages) echo '<a href="users.php?page='.$total_pages.'">»</a>';
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

function confirmDelete(userId) {
    if(confirm('Yakin ingin menghapus user ini? Tindakan ini tidak dapat dibatalkan.')) {
        window.location.href = 'users.php?delete_id=' + userId + '&page=<?= $page; ?>';
    }
}
</script>

</body>
</html>
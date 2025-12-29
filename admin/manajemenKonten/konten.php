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

// PENGATURAN PAGINATION
$posts_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $posts_per_page;

// FILTER STATUS POSTINGAN
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$where = "WHERE 1=1";
if ($status_filter == 'publish') {
    $where = "WHERE blog.status='publish'";
} elseif ($status_filter == 'draft') {
    $where = "WHERE blog.status='draft'";
}

// QUERY MENAMPILKAN POSTINGAN
$query = mysqli_query($conn, "
    SELECT blog.id_blog, blog.judul, blog.konten, users.nama, blog.tanggal_publish, blog.status
    FROM blog
    LEFT JOIN users ON blog.id_admin = users.id_user
    $where
    ORDER BY blog.created_at DESC
    LIMIT $posts_per_page OFFSET $offset
");

// HITUNG TOTAL UNTUK PAGINATION
$total_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM blog $where");
if (!$total_query) {
    echo "Error: " . mysqli_error($conn);
    exit;
}
$total = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total / $posts_per_page);

// HANDLING FORM TAMBAH POSTINGAN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $konten = mysqli_real_escape_string($conn, $_POST['konten']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $id_admin = $_SESSION['id_user'];

    $target_dir = "../../assets/blog/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $thumbnail = null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $file_name = time() . '_' . str_replace(" ", "_", basename($_FILES["thumbnail"]["name"]));
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
            $thumbnail = $file_name;
        }
    }

    $insert_query = "INSERT INTO blog (judul, konten, thumbnail, tanggal_publish, id_admin, status, created_at)
                     VALUES ('$judul', '$konten', '$thumbnail', NOW(), $id_admin, '$status', NOW())";
    $result = mysqli_query($conn, $insert_query);

    if ($result) {
        // LOG AKTIVITAS ADMIN 
        $aksi = "Tambah Blog";
        $deskripsi = "Admin menambahkan blog berjudul '$judul'";
        mysqli_query($conn, "INSERT INTO log_aktivitas_admin (id_admin, aktivitas, deskripsi, created_at) VALUES ($id_admin, '$aksi', '$deskripsi', NOW())");
        
        header('Location: konten.php?status_filter=' . $status_filter . '&success=added');
        exit;
    } else {
        $error = "Gagal menambah blog: " . mysqli_error($conn);
    }
}

// HANDLE DELETE
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Ambil data blog untuk hapus thumbnail
    $blog_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT judul, thumbnail FROM blog WHERE id_blog=$delete_id"));
    
    // Hapus file thumbnail jika ada
    if($blog_data['thumbnail'] && file_exists("../../assets/blog/".$blog_data['thumbnail'])) {
        unlink("../../assets/blog/".$blog_data['thumbnail']);
    }
    
    // Hapus dari database
    mysqli_query($conn, "DELETE FROM blog WHERE id_blog=$delete_id");
    
    // LOG AKTIVITAS
    if(isset($_SESSION['id_user'])) {
        $aksi = "Hapus Blog";
        $deskripsi = "Admin menghapus blog: ".$blog_data['judul'];
        mysqli_query($conn, "INSERT INTO log_aktivitas_admin (id_admin, aktivitas, deskripsi, created_at) VALUES ({$_SESSION['id_user']}, '$aksi', '$deskripsi', NOW())");
    }
    
    header('Location: konten.php?status_filter=' . $status_filter . '&success=deleted');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Konten Blog - YogyaStay</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Poppins', sans-serif; background-color: #fff7ed; }

.main-content { margin-left: 240px; padding:40px; transition: 0.3s; }

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

h1 { font-size:28px; color:#333; margin: 0; }

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

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 2px solid #fecaca;
}

.blog-form, .blog-dashboard { background:#fff; border-radius:20px; padding:30px; box-shadow:0 6px 20px rgba(0,0,0,0.1); margin-bottom:30px; }

.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #374151; }
.form-group input, .form-group textarea, .form-group select { 
    width:100%; 
    padding:12px; 
    border-radius:8px; 
    border:2px solid #e5e7eb; 
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
}
.form-group textarea { min-height: 150px; resize: vertical; }

.btn-submit { 
    padding:12px 28px; 
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color:#fff; 
    border:none; 
    cursor:pointer; 
    font-size:16px; 
    font-weight: 600;
    border-radius: 10px;
    transition: all 0.3s;
}
.btn-submit:hover { 
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(251, 191, 36, 0.4);
}

.blog-list { overflow-x: auto; }
.blog-list table { width: 100%; min-width: 800px; border-collapse: collapse; }
.blog-list th, .blog-list td { padding: 15px; border-bottom: 1px solid #e0e0e0; text-align: left; }
.blog-list th { background:#f1f3f6; font-weight:600; color:#555; }
.blog-list tr:hover { background: #f9f9f9; }

.action-buttons { display:flex; gap:10px; flex-wrap: wrap; }
.action-buttons a, .action-buttons button { 
    display:inline-flex; 
    align-items:center; 
    padding:8px 16px; 
    border-radius:8px; 
    color:#fff; 
    font-weight:600; 
    text-decoration:none; 
    transition:0.3s; 
    font-size: 13px;
    border: none;
    cursor: pointer;
}
.btn-edit { background:#3b82f6; }
.btn-edit:hover { background:#2563eb; }
.btn-delete { background:#dc3545; }
.btn-delete:hover { background:#c82333; }
.action-buttons i { margin-right:6px; }

.status-publish { color:#155724; background-color:#d4edda; padding:6px 12px; border-radius:8px; font-weight:600; font-size: 13px; }
.status-draft { color:#856404; background-color:#fff3cd; padding:6px 12px; border-radius:8px; font-weight:600; font-size: 13px; }

.pagination { display:flex; flex-wrap:wrap; justify-content:center; margin-top:25px; gap: 5px; }
.pagination a { padding:10px 16px; border-radius:8px; background:#f8f9fa; color:#555; text-decoration:none; transition:0.3s; font-size:14px; font-weight: 600; }
.pagination a:hover { background:#fde68a; }
.pagination a.active { background:#f59e0b; color:#fff; }

.filter-status { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
.filter-status a { 
    padding:10px 20px; 
    border-radius:8px; 
    background:#f1f3f6; 
    text-decoration:none; 
    color:#555; 
    font-weight: 600;
    transition: 0.3s;
}
.filter-status a.active { background:#f59e0b; color:#fff; }
.filter-status a:hover { background:#fde68a; }

@media (max-width: 768px) {
    .main-content { margin-left:0; padding:20px; }
    .hamburger { display: flex; }
    .blog-form, .blog-dashboard { padding:20px; }
    .action-buttons { flex-direction: column; }
    .action-buttons a, .action-buttons button { width: 100%; justify-content: center; }
    table { font-size: 13px; }
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
            <h1>📝 Manajemen Konten Blog</h1>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <span style="font-size: 24px;">✅</span>
        <span>
            <?php if($_GET['success'] == 'added'): ?>
                Blog berhasil ditambahkan!
            <?php elseif($_GET['success'] == 'deleted'): ?>
                Blog berhasil dihapus!
            <?php endif; ?>
        </span>
    </div>
    <?php endif; ?>

    <?php if(isset($error)): ?>
    <div class="alert alert-error">
        <span style="font-size: 24px;">❌</span>
        <span><?= $error ?></span>
    </div>
    <?php endif; ?>

    <!-- FORM TAMBAH POSTINGAN -->
    <section class="blog-form">
        <h2 style="margin-bottom: 20px;">✍️ Buat Postingan Baru</h2>
        <form action="konten.php?status_filter=<?= $status_filter; ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Judul Postingan</label>
                <input type="text" name="judul" required placeholder="Masukkan judul blog...">
            </div>
            <div class="form-group">
                <label>Unggah Gambar Thumbnail</label>
                <input type="file" name="thumbnail" accept="image/*">
            </div>
            <div class="form-group">
                <label>Isi Blog (Konten Utama)</label>
                <textarea name="konten" required placeholder="Tulis konten blog di sini..."></textarea>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" required>
                    <option value="publish">Publish</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            <button type="submit" name="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Publikasikan Postingan
            </button>
        </form>
    </section>

    <!-- DASHBOARD POSTINGAN -->
    <section class="blog-dashboard">
        <h2 style="margin-bottom: 20px;">📚 Daftar Blog</h2>

        <!-- FILTER STATUS -->
        <div class="filter-status">
            <a href="konten.php?status_filter=all" class="<?= $status_filter=='all'?'active':''; ?>">
                <i class="fas fa-list"></i> Semua
            </a>
            <a href="konten.php?status_filter=publish" class="<?= $status_filter=='publish'?'active':''; ?>">
                <i class="fas fa-check-circle"></i> Publish
            </a>
            <a href="konten.php?status_filter=draft" class="<?= $status_filter=='draft'?'active':''; ?>">
                <i class="fas fa-edit"></i> Draft
            </a>
        </div>

        <div class="blog-list">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Tanggal Publish</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = $offset + 1; while($row=mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><strong><?= htmlspecialchars($row['judul']); ?></strong></td>
                            <td><?= htmlspecialchars($row['nama'] ?? 'Unknown'); ?></td>
                            <td><?= $row['tanggal_publish'] ? date('d M Y', strtotime($row['tanggal_publish'])) : '-'; ?></td>
                            <td>
                                <?php if($row['status']=='publish'): ?>
                                    <span class="status-publish">Dipublikasikan</span>
                                <?php else: ?>
                                    <span class="status-draft">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <a href="edit_blog.php?id=<?= $row['id_blog']; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i>Edit
                                </a>
                                <button onclick="confirmDelete(<?= $row['id_blog']; ?>)" class="btn-delete">
                                    <i class="fas fa-trash"></i>Hapus
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- PAGINATION -->
            <div class="pagination">
                <?php
                if($page > 1) echo '<a href="?page=1&status_filter='.$status_filter.'">«</a>';
                if($page > 1) echo '<a href="?page='.($page-1).'&status_filter='.$status_filter.'">‹</a>';
                
                $range = 2;
                for($i=max(1,$page-$range); $i<=min($total_pages,$page+$range); $i++){
                    echo '<a href="?page='.$i.'&status_filter='.$status_filter.'" class="'.($i==$page?'active':'').'">'.$i.'</a>';
                }
                
                if($page < $total_pages) echo '<a href="?page='.($page+1).'&status_filter='.$status_filter.'">›</a>';
                if($page < $total_pages) echo '<a href="?page='.$total_pages.'&status_filter='.$status_filter.'">»</a>';
                ?>
            </div>
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

function confirmDelete(blogId) {
    if(confirm('Yakin ingin menghapus blog ini? Tindakan ini tidak dapat dibatalkan.')) {
        window.location.href = 'konten.php?delete_id=' + blogId + '&status_filter=<?= $status_filter; ?>';
    }
}
</script>

</body>
</html>
<?php
require_once '../../config.php';
require_once '../log/functions.php';

// PENGATURAN PAGINATION
$posts_per_page = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $posts_per_page;

// FILTER STATUS POSTINGAN
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'all';
$where = "WHERE blog.status IN ('publish','draft')";
if ($status_filter == 'publish') {
    $where = "WHERE blog.status='publish'";
} elseif ($status_filter == 'draft') {
    $where = "WHERE blog.status='draft'";
}

// QUERY MENAMPILKAN POSTINGAN
$query = mysqli_query($conn, "
    SELECT blog.id_blog, blog.judul, users.nama, blog.tanggal_publish, blog.status
    FROM blog
    JOIN users ON blog.id_admin = users.id_user
    $where
    ORDER BY blog.tanggal_publish DESC
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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = $_POST['judul'];
    $penulis = $_POST['penulis'];
    $konten = $_POST['konten'];
    $status = $_POST['status'];

    $target_dir = "../../assets/blog/";
    if (!is_dir($target_dir)) {
        echo "Folder untuk menyimpan gambar tidak ditemukan.";
        exit;
    }

    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $file_name = str_replace(" ", "_", basename($_FILES["thumbnail"]["name"]));
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
            $insert_query = "INSERT INTO blog (judul, konten, thumbnail, tanggal_publish, id_admin, status)
                             VALUES ('$judul', '$konten', '$file_name', NOW(), {$_SESSION['user_id']}, '$status')";
            $result = mysqli_query($conn, $insert_query);

            if (!$result) {
                echo "Error: " . mysqli_error($conn);
            } else {
                //  LOG AKTIVITAS ADMIN 
                $aksi = "Tambah Blog";
                $deskripsi = "Admin ID ".$_SESSION['user_id']." menambahkan blog berjudul '$judul'";
                addAdminLog($conn, $_SESSION['user_id'], $aksi, $deskripsi);
                // 

                header('Location: konten.php?status_filter=' . $status_filter);
                exit;
            }
        } else {
            echo "Gagal meng-upload gambar.";
        }
    } else {
        echo "Silakan pilih gambar yang akan di-upload.";
    }
}

// SIDEBAR
if (file_exists('../partials/sidebar.php')) {
    include '../partials/sidebar.php'; 
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Konten Blog</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
<style>

/* style*/
body { font-family: 'Poppins', sans-serif; background-color: #fff7ed; margin:0; padding:0; }
.main-content { margin-left: 240px; padding:40px; }
h1 { font-size:30px; color:#333; }
.blog-form, .blog-dashboard { background:#fff; border-radius:10px; padding:30px; box-shadow:0 10px 30px rgba(0,0,0,0.1); margin-top:30px; }
.blog-form input, .blog-form textarea, .blog-form select { width:100%; padding:10px; margin-bottom:15px; border-radius:5px; border:1px solid #ddd; }
.blog-form button { padding:10px 20px; background-color:#5e6b8d; color:#fff; border:none; cursor:pointer; font-size:16px; }
.blog-form button:hover { background-color:#4a5363; }

/* TABEL RESPONSIF */
.blog-list {
    overflow-x: auto; 
}
.blog-list table {
    width: 100%;
    min-width: 600px;
    border-collapse: collapse;
}
.blog-list th, .blog-list td {
    padding: 12px 15px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}
.action-buttons { display:flex; gap:10px; }
.action-buttons a { display:inline-flex; align-items:center; padding:8px 16px; border-radius:5px; color:#fff; font-weight:bold; text-decoration:none; background-color:#5e6b8d; transition:0.3s; }
.action-buttons a:hover { background-color:#4a5363; }
.action-buttons .delete-button { background-color:#dc3545; }
.action-buttons .delete-button:hover { background-color:#c82333; }
.action-buttons i { margin-right:8px; }

.status-publish { color:#155724; background-color:#d4edda; padding:4px 8px; border-radius:5px; font-weight:bold; }
.status-draft { color:#856404; background-color:#fff3cd; padding:4px 8px; border-radius:5px; font-weight:bold; }

/* PAGINATION RESPONSIF */
.pagination {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    margin-top: 20px;
}
.pagination a {
    margin: 5px;
    padding: 10px 15px;
    border-radius: 5px;
    background: #f8f9fa;
    color: #555;
    text-decoration: none;
    transition: 0.3s;
}
.pagination a:hover { background: #d1d1d1; }
.pagination a.active { background: #5e6b8d; color: #fff; }

/* FILTER STATUS RESPONSIF */
.filter-status {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}
.filter-status a { margin-right:10px; padding:6px 12px; border-radius:5px; background:#f8f9fa; text-decoration:none; color:#555; }
.filter-status a.active { background:#5e6b8d; color:#fff; }

/* MODAL DELETE */
.overlay { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:flex; justify-content:center; align-items:center; z-index:1000; }
.modal-box { background:#fff; padding:30px; border-radius:10px; text-align:center; width:400px; max-width:90%; box-shadow:0 10px 30px rgba(0,0,0,0.3); }
.modal-box .icon { font-size:50px; color:#dc3545; margin-bottom:15px; }
.modal-box h2 { margin-bottom:15px; }
.modal-box p { margin-bottom:25px; }
.modal-box .actions button { padding:10px 20px; border:none; border-radius:5px; cursor:pointer; font-weight:bold; margin:0 10px; }
.btn-cancel { background-color:#6c757d; color:#fff; }
.btn-cancel:hover { background-color:#5a6268; }
.btn-delete { background-color:#dc3545; color:#fff; }
.btn-delete:hover { background-color:#c82333; }

/* SIDEBAR DEFAULT */
#sidebar.show {
    transform: translateX(0);
}

/* HAMBURGER BUTTON */
#hamburger { display:none; position:fixed; top:15px; left:15px; font-size:24px; cursor:pointer; z-index:1001; }
#sidebar-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; }
#sidebar-overlay.show { display:block; }

@media (max-width:768px) {
    .main-content { margin-left:0; }
    #hamburger { display:block; }
    #sidebar { transform: translateX(-100%); }
}


/* MEDIA QUERY UNTUK DEVICE KECIL */
@media (max-width: 768px) {
    .main-content { margin-left:0; padding:20px; }
    .blog-form, .blog-dashboard { padding:20px; }
    .action-buttons { flex-direction: column; gap: 5px; }
    .action-buttons a { width: 100%; justify-content: center; }
}
</style>
</head>
<body>
<div id="hamburger"><i class="fas fa-bars"></i></div>
<div id="sidebar-overlay"></div>

<div class="main-content">
    <!-- FORM TAMBAH POSTINGAN -->
    <section class="blog-form">
        <h1>Buat Postingan Baru</h1>
        <form action="konten.php?status_filter=<?= $status_filter; ?>" method="POST" enctype="multipart/form-data">
            <label>Judul Postingan</label>
            <input type="text" name="judul" required>
            <label>Nama Penulis</label>
            <input type="text" name="penulis" required>
            <label>Unggah Gambar</label>
            <input type="file" name="thumbnail" accept="image/*" required>
            <label>Isi Blog (Konten Utama)</label>
            <textarea name="konten" required></textarea>
            <label>Status</label>
            <select name="status" required>
                <option value="publish">Publish</option>
                <option value="draft">Draft</option>
            </select>
            <button type="submit" name="submit">Publikasikan Postingan</button>
        </form>
    </section>

    <!-- DASHBOARD POSTINGAN -->
    <section class="blog-dashboard">
        <h1>Manajemen Konten Blog</h1>

        <!-- FILTER STATUS -->
        <div class="filter-status">
            <a href="konten.php?status_filter=all" class="<?= $status_filter=='all'?'active':''; ?>">Semua</a>
            <a href="konten.php?status_filter=publish" class="<?= $status_filter=='publish'?'active':''; ?>">Publish</a>
            <a href="konten.php?status_filter=draft" class="<?= $status_filter=='draft'?'active':''; ?>">Draft</a>
        </div>

        <div class="blog-list">
            <table>
                <thead>
                    <tr>
                        <th>Judul & Isi Singkat</th>
                        <th>Penulis</th>
                        <th>Tanggal Publish</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row=mysqli_fetch_assoc($query)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['judul']); ?></td>
                            <td><?= htmlspecialchars($row['nama']); ?></td>
                            <td><?= date('Y-m-d', strtotime($row['tanggal_publish'])); ?></td>
                            <td>
                                <?php if($row['status']=='publish'): ?>
                                    <span class="status-publish">Dipublikasikan</span>
                                <?php else: ?>
                                    <span class="status-draft">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <a href="edit_blog.php?id=<?= $row['id_blog']; ?>"><i class="fas fa-edit"></i>Edit</a>
                                <a href="#" class="btn-delete-blog delete-button" data-id="<?= $row['id_blog']; ?>"><i class="fas fa-trash"></i>Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- PAGINATION -->
            <div class="pagination">
                <?php
                $range = 2;
                $pagination = [];
                if($page > 1) $pagination[] = '<a href="?page=1&status_filter='.$status_filter.'">&laquo;</a>';
                if($page > 1) $pagination[] = '<a href="?page='.($page-1).'&status_filter='.$status_filter.'">&lt;</a>';
                for($i=max(1,$page-$range); $i<=min($total_pages,$page+$range); $i++){
                    $pagination[] = '<a href="?page='.$i.'&status_filter='.$status_filter.'" class="'.($i==$page?'active':'').'">'.$i.'</a>';
                }
                if($page < $total_pages) $pagination[] = '<a href="?page='.($page+1).'&status_filter='.$status_filter.'">&gt;</a>';
                if($page < $total_pages) $pagination[] = '<a href="?page='.$total_pages.'&status_filter='.$status_filter.'">&raquo;</a>';
                echo implode('', $pagination);
                ?>
            </div>
        </div>
    </section>
</div>

<!-- MODAL DELETE -->
<div class="overlay" id="deleteModal" style="display:none;">
    <div class="modal-box">
        <div class="icon">!</div>
        <h2>Hapus Postingan?</h2>
        <p>Data postingan, termasuk gambar, akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>
        <div class="actions">
            <button class="btn-cancel" id="cancelDelete">Batal</button>
            <button class="btn-delete" id="confirmDelete">Hapus</button>
        </div>
    </div>
</div>

<script>

// SCRIPT UNTUK DELETE POSTINGAN
let deleteId = null;

document.querySelectorAll('.btn-delete-blog').forEach(btn => {
    btn.addEventListener('click', e => {
        e.preventDefault();
        deleteId = btn.dataset.id;
        document.getElementById('deleteModal').style.display = 'flex';
    });
});

document.getElementById('cancelDelete').onclick = () => {
    document.getElementById('deleteModal').style.display = 'none';
};

document.getElementById('confirmDelete').onclick = () => {
    window.location.href = 'delete_blog.php?id=' + deleteId;
};
</script>

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

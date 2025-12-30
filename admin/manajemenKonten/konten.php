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
$posts_per_page = 8;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
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
    SELECT blog.id_blog, blog.judul, blog.konten, blog.thumbnail, users.nama, blog.tanggal_publish, blog.status
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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $penulis = mysqli_real_escape_string($conn, $_POST['penulis']);
    $konten = mysqli_real_escape_string($conn, $_POST['konten']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $target_dir = "../../assets/blog/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $file_name = time() . "_" . str_replace(" ", "_", basename($_FILES["thumbnail"]["name"]));
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
            // Insert blog
            $insert_query = "INSERT INTO blog (judul, konten, thumbnail, tanggal_publish, id_admin, status)
                             VALUES ('$judul', '$konten', '$file_name', NOW(), {$_SESSION['user_id']}, '$status')";
            $result = mysqli_query($conn, $insert_query);

            if ($result) {
                // LOGGING AKTIVITAS ADMIN
                $admin_id = $_SESSION['user_id'];
                $aksi = mysqli_real_escape_string($conn, "Tambah Blog");
                $deskripsi = mysqli_real_escape_string($conn, "Admin menambahkan blog berjudul '$judul' dengan status '$status'");

                // Cek struktur tabel log_aktivitas_admin
                $check_table = mysqli_query($conn, "DESCRIBE log_aktivitas_admin");

                if ($check_table) {
                    $columns = [];
                    while ($col = mysqli_fetch_assoc($check_table)) {
                        $columns[] = $col['Field'];
                    }

                    // Deteksi nama kolom untuk admin_id
                    $admin_column = null;
                    $possible_admin_columns = ['admin_id', 'id_admin', 'user_id', 'id_user'];
                    foreach ($possible_admin_columns as $col) {
                        if (in_array($col, $columns)) {
                            $admin_column = $col;
                            break;
                        }
                    }

                    // Deteksi nama kolom untuk timestamp
                    $time_column = null;
                    $possible_time_columns = ['created_at', 'waktu', 'tanggal', 'timestamp', 'date_created'];
                    foreach ($possible_time_columns as $col) {
                        if (in_array($col, $columns)) {
                            $time_column = $col;
                            break;
                        }
                    }

                    // Buat query sesuai kolom yang tersedia
                    if ($admin_column && in_array('aksi', $columns) && in_array('deskripsi', $columns)) {
                        if ($time_column) {
                            $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, deskripsi, $time_column) 
                                         VALUES ('$admin_id', '$aksi', '$deskripsi', NOW())";
                        } else {
                            $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, deskripsi) 
                                         VALUES ('$admin_id', '$aksi', '$deskripsi')";
                        }

                        $log_result = mysqli_query($conn, $log_query);

                        if (!$log_result) {
                            // Simpan error ke session untuk debugging
                            $_SESSION['log_error'] = mysqli_error($conn);
                            $_SESSION['log_query'] = $log_query;
                            $_SESSION['log_columns'] = implode(', ', $columns);
                        } else {
                            // Berhasil, hapus error jika ada
                            unset($_SESSION['log_error']);
                            unset($_SESSION['log_query']);
                            unset($_SESSION['log_columns']);
                        }
                    } else {
                        $_SESSION['log_error'] = "Kolom yang dibutuhkan tidak lengkap. Kolom yang ada: " . implode(', ', $columns);
                    }
                } else {
                    $_SESSION['log_error'] = "Tidak dapat membaca struktur tabel log_aktivitas_admin";
                }

                header('Location: konten.php?status_filter=' . $status_filter . '&success=add');
                exit;
            } else {
                $error_message = "Gagal menambahkan postingan: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Gagal mengupload gambar.";
        }
    } else {
        $error_message = "Silakan pilih gambar thumbnail.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Konten Blog - YogyaStay</title>
    <link rel="icon" type="image/jpeg" href="../../assets/img/logonw.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
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
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
            align-items: flex-start;
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

        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 2px solid #fbbf24;
        }

        .alert-debug {
            background: #e0e7ff;
            color: #3730a3;
            border: 2px solid #818cf8;
            font-size: 11px;
            font-family: monospace;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 200px;
            overflow-y: auto;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* FORM SECTION */
        .blog-form {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .blog-form h2 {
            font-size: 22px;
            margin-bottom: 25px;
            color: #1a1a1a;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .form-group input[type="text"],
        .form-group input[type="file"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #fbbf24;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .btn-submit {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            padding: 14px 32px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 6px 20px rgba(251, 191, 36, 0.4);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(251, 191, 36, 0.5);
        }

        /* DASHBOARD SECTION */
        .blog-dashboard {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
            flex-wrap: wrap;
            gap: 15px;
        }

        .dashboard-header h2 {
            font-size: 22px;
            color: #1a1a1a;
        }

        .total-posts {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
            color: #92400e;
            border: 2px solid #fbbf24;
        }

        /* FILTER TABS */
        .filter-status {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-status a {
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            color: #64748b;
            background: #f8fafc;
            transition: all 0.3s;
            font-size: 14px;
            white-space: nowrap;
        }

        .filter-status a:hover {
            background: #fef3c7;
            color: #92400e;
        }

        .filter-status a.active {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(251, 191, 36, 0.4);
        }

        /* TABLE */
        .blog-list {
            overflow-x: auto;
            margin-bottom: 25px;
        }

        .blog-list table {
            width: 100%;
            min-width: 800px;
            border-collapse: collapse;
        }

        .blog-list th,
        .blog-list td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .blog-list th {
            background: #f1f5f9;
            font-weight: 600;
            color: #475569;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .blog-list tbody tr {
            transition: all 0.2s;
        }

        .blog-list tbody tr:hover {
            background: #f8fafc;
        }

        .blog-list td {
            color: #374151;
            font-size: 14px;
        }

        .blog-title {
            font-weight: 600;
            color: #1a1a1a;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* STATUS BADGE */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            white-space: nowrap;
        }

        .status-publish {
            background: #d1fae5;
            color: #065f46;
        }

        .status-publish i {
            color: #10b981;
        }

        .status-draft {
            background: #fef3c7;
            color: #92400e;
        }

        .status-draft i {
            color: #f59e0b;
        }

        /* ACTION BUTTONS */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-edit {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-edit:hover {
            background: #bfdbfe;
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

        /* PAGINATION */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .pagination a {
            padding: 10px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            text-decoration: none;
            color: #374151;
            font-weight: 600;
            transition: all 0.3s;
            background: white;
            min-width: 40px;
            text-align: center;
            font-size: 14px;
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

        /* MODAL DELETE */
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
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-box {
            background: white;
            width: 90%;
            max-width: 450px;
            padding: 35px;
            border-radius: 24px;
            text-align: center;
            animation: scaleIn 0.3s ease;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
        }

        @keyframes scaleIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
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
        }

        .btn-delete-confirm:hover {
            background: #dc2626;
            transform: scale(1.05);
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
            color: #9ca3af;
        }

        .empty-state h3 {
            color: #374151;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .empty-state p {
            color: #9ca3af;
        }

        /* ========================== */
        /* RESPONSIVE DESIGN */
        /* ========================== */

        /* Tablet */
        @media (max-width: 992px) {
            .content {
                margin-left: 0;
                padding: 20px;
            }

            .page-title {
                font-size: 24px;
            }

            .topbar {
                padding: 15px 20px;
            }

            .blog-form,
            .blog-dashboard {
                padding: 25px;
            }

            .blog-list table {
                min-width: 700px;
            }
        }

        /* Mobile */
        @media (max-width: 768px) {
            .content {
                padding: 15px;
            }

            .topbar {
                padding: 12px 15px;
                margin-bottom: 20px;
            }

            .page-title {
                font-size: 20px;
            }

            .blog-form,
            .blog-dashboard {
                padding: 20px;
                border-radius: 16px;
            }

            .blog-form h2,
            .dashboard-header h2 {
                font-size: 18px;
            }

            .form-group input[type="text"],
            .form-group input[type="file"],
            .form-group textarea,
            .form-group select {
                padding: 10px 14px;
                font-size: 13px;
            }

            .btn-submit {
                width: 100%;
                justify-content: center;
                padding: 12px 24px;
                font-size: 14px;
            }

            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .total-posts {
                font-size: 14px;
            }

            .filter-status {
                width: 100%;
                overflow-x: auto;
                scrollbar-width: thin;
            }

            .filter-status a {
                font-size: 13px;
                padding: 8px 16px;
            }

            .blog-list table {
                min-width: 100%;
                font-size: 13px;
            }

            .blog-list th,
            .blog-list td {
                padding: 12px 10px;
            }

            .blog-title {
                max-width: 150px;
            }

            .action-buttons {
                flex-direction: column;
                width: 100%;
                gap: 6px;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
                font-size: 12px;
            }

            .pagination a {
                padding: 8px 12px;
                font-size: 13px;
                min-width: 36px;
            }

            .modal-box {
                width: 95%;
                padding: 25px;
            }

            .modal-icon {
                font-size: 60px;
            }

            .modal-box h3 {
                font-size: 20px;
            }

            .modal-box p {
                font-size: 13px;
            }

            .btn-modal {
                padding: 12px 18px;
                font-size: 14px;
            }
        }

        /* Mobile Small */
        @media (max-width: 480px) {
            .content {
                padding: 12px;
            }

            .page-title {
                font-size: 18px;
            }

            .topbar-left {
                gap: 10px;
            }

            .blog-form,
            .blog-dashboard {
                padding: 15px;
            }

            .blog-form h2,
            .dashboard-header h2 {
                font-size: 16px;
            }

            .pagination a {
                padding: 6px 10px;
                font-size: 12px;
                min-width: 32px;
            }
        }

        /* Table Scroll Indicator */
        @media (max-width: 768px) {
            .blog-list::after {
                content: '← Geser untuk melihat lebih banyak →';
                display: block;
                text-align: center;
                padding: 10px;
                font-size: 12px;
                color: #9ca3af;
                font-style: italic;
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
                <h1 class="page-title">Manajemen Konten Blog</h1>
            </div>
        </div>

        <!-- DEBUG INFO - Bisa dihapus setelah berhasil -->
        <?php if (isset($_SESSION['log_error'])): ?>
            <div class="alert alert-warning">
                <span style="font-size: 24px;">⚠</span>
                <div style="flex: 1;">
                    <strong>Debug - Error Log Aktivitas:</strong><br>
                    <?= htmlspecialchars($_SESSION['log_error']) ?>
                    <?php if (isset($_SESSION['log_query'])): ?>
                        <br><br><strong>Query:</strong>
                        <div class="alert-debug"><?= htmlspecialchars($_SESSION['log_query']) ?></div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['log_columns'])): ?>
                        <br><strong>Kolom yang tersedia:</strong> <?= htmlspecialchars($_SESSION['log_columns']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            unset($_SESSION['log_error']);
            unset($_SESSION['log_query']);
            unset($_SESSION['log_columns']);
        endif;
        ?>

        <!-- ALERT SUCCESS -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <span style="font-size: 24px;">✓</span>
                <span>
                    <?php if ($_GET['success'] == 'add'): ?>
                        Postingan berhasil ditambahkan!
                    <?php elseif ($_GET['success'] == 'delete'): ?>
                        Postingan berhasil dihapus!
                    <?php elseif ($_GET['success'] == 'update'): ?>
                        Postingan berhasil diperbarui!
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>

        <!-- ALERT ERROR -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <span style="font-size: 24px;">✕</span>
                <span><?= $error_message ?></span>
            </div>
        <?php endif; ?>

        <!-- FORM TAMBAH POSTINGAN -->
        <section class="blog-form">
            <h2>Buat Postingan Baru</h2>
            <form action="konten.php?status_filter=<?= $status_filter; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Judul Postingan</label>
                    <input type="text" name="judul" placeholder="Masukkan judul postingan..." required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-edit"></i> Nama Penulis</label>
                    <input type="text" name="penulis" placeholder="Masukkan nama penulis..." required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-image"></i> Thumbnail / Gambar Utama</label>
                    <input type="file" name="thumbnail" accept="image/*" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-paragraph"></i> Isi Blog (Konten Utama)</label>
                    <textarea name="konten" placeholder="Tulis konten blog Anda di sini..." required></textarea>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-toggle-on"></i> Status Publikasi</label>
                    <select name="status" required>
                        <option value="publish">Publish - Tampilkan di website</option>
                        <option value="draft">Draft - Simpan sebagai draft</option>
                    </select>
                </div>

                <button type="submit" name="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i>
                    Publikasikan Postingan
                </button>
            </form>
        </section>

        <!-- DASHBOARD POSTINGAN -->
        <section class="blog-dashboard">
            <div class="dashboard-header">
                <h2>Daftar Postingan Blog</h2>
                <div class="total-posts">
                    <i class="fas fa-file-alt"></i> Total: <?= $total ?> Postingan
                </div>
            </div>

            <!-- FILTER STATUS -->
            <div class="filter-status">
                <a href="konten.php?status_filter=all" class="<?= $status_filter == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> Semua
                </a>
                <a href="konten.php?status_filter=publish" class="<?= $status_filter == 'publish' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Publish
                </a>
                <a href="konten.php?status_filter=draft" class="<?= $status_filter == 'draft' ? 'active' : ''; ?>">
                    <i class="fas fa-file"></i> Draft
                </a>
            </div>

            <?php if (mysqli_num_rows($query) > 0): ?>
                <div class="blog-list">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul</th>
                                <th>Penulis</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = $offset + 1;
                            while ($row = mysqli_fetch_assoc($query)):
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <div class="blog-title" title="<?= htmlspecialchars($row['judul']); ?>">
                                            <?= htmlspecialchars($row['judul']); ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($row['nama']); ?></td>
                                    <td><?= date('d M Y', strtotime($row['tanggal_publish'])); ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'publish'): ?>
                                            <span class="status-badge status-publish">
                                                <i class="fas fa-check-circle"></i>
                                                Publish
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-draft">
                                                <i class="fas fa-file"></i>
                                                Draft
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_blog.php?id=<?= $row['id_blog']; ?>" class="btn-action btn-edit">
                                                <i class="fas fa-edit"></i>
                                                Edit
                                            </a>
                                            <a href="javascript:void(0)" class="btn-action btn-delete btn-delete-blog"
                                                data-id="<?= $row['id_blog']; ?>"
                                                data-title="<?= htmlspecialchars($row['judul']); ?>">
                                                <i class="fas fa-trash"></i>
                                                Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1&status_filter=<?= $status_filter ?>" title="Halaman Pertama">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="?page=<?= $page - 1 ?>&status_filter=<?= $status_filter ?>" title="Sebelumnya">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        $range = 2;
                        for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++):
                            ?>
                            <a href="?page=<?= $i ?>&status_filter=<?= $status_filter ?>"
                                class="<?= $i == $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>&status_filter=<?= $status_filter ?>" title="Selanjutnya">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?page=<?= $total_pages ?>&status_filter=<?= $status_filter ?>" title="Halaman Terakhir">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3>Belum Ada Postingan</h3>
                    <p>Mulai buat postingan blog pertama Anda di atas</p>
                </div>
            <?php endif; ?>
        </section>

    </div>

    <!-- MODAL DELETE -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <div class="modal-icon">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3>Hapus Postingan?</h3>
            <p id="deleteMessage">Data postingan, termasuk gambar, akan dihapus permanen. Tindakan ini tidak dapat
                dibatalkan.</p>
            <div class="modal-actions">
                <button class="btn-modal btn-cancel" id="cancelDelete">Batal</button>
                <button class="btn-modal btn-delete-confirm" id="confirmDelete">Hapus</button>
            </div>
        </div>
    </div>

    <script>
        // Alert
        const alert = document.querySelector('.alert');
        if (alert) {
            setTimeout(() => {
                alert.style.animation = 'slideInDown 0.3s ease reverse';
                setTimeout(() => alert.remove(), 300);
            }, 8000);
        }

        // SCRIPT UNTUK DELETE POSTINGAN
        let deleteId = null;
        let deleteTitle = null;

        document.querySelectorAll('.btn-delete-blog').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                deleteId = btn.dataset.id;
                deleteTitle = btn.dataset.title;

                const message = `Anda yakin ingin menghapus postingan "<strong>${deleteTitle}</strong>"? Tindakan ini tidak dapat dibatalkan.`;
                document.getElementById('deleteMessage').innerHTML = message;
                document.getElementById('deleteModal').classList.add('show');
            });
        });

        document.getElementById('cancelDelete').onclick = () => {
            document.getElementById('deleteModal').classList.remove('show');
        };

        document.getElementById('confirmDelete').onclick = () => {
            window.location.href = 'delete_blog.php?id=' + deleteId + '&status_filter=<?= $status_filter ?>';
        };

        //Modal
        document.getElementById('deleteModal').addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.getElementById('deleteModal').classList.remove('show');
            }
        });
    </script>

</body>

</html>
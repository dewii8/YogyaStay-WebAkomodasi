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

//  PAGINATION 
$users_per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $users_per_page;

//  QUERY 
$query = mysqli_query($conn, "
    SELECT id_user, nama, email, status, created_at
    FROM users
    WHERE role_id = 'user'
    ORDER BY created_at DESC
    LIMIT $users_per_page OFFSET $offset
");

$total_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role_id = 'user'");
$total = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total / $users_per_page);

//  HAPUS USER 
if (isset($_GET['delete_id'])) {
    $delete_id = (int) $_GET['delete_id'];

    // Ambil nama/email untuk deskripsi log
    $user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama, email FROM users WHERE id_user=$delete_id"));

    if ($user_data) {
        $nama_user = $user_data['nama'];
        $email_user = $user_data['email'];

        // Mulai transaction untuk keamanan
        mysqli_begin_transaction($conn);

        try {

            // Hapus booking user
            mysqli_query($conn, "DELETE FROM booking WHERE id_user=$delete_id");

            // Hapus wishlist user
            mysqli_query($conn, "DELETE FROM wishlist WHERE id_user=$delete_id");

            // Hapus review user
            mysqli_query($conn, "DELETE FROM review WHERE id_user=$delete_id");

            // Hapus user
            mysqli_query($conn, "DELETE FROM users WHERE id_user=$delete_id");

            // Commit transaction
            mysqli_commit($conn);

            // LOG AKTIVITAS ADMIN 
            if (isset($_SESSION['user_id'])) {
                $admin_id = intval($_SESSION['user_id']);
                $aksi = mysqli_real_escape_string($conn, "Hapus User");
                $deskripsi = mysqli_real_escape_string($conn, "Admin menghapus user $nama_user ($email_user)");

                // Cek struktur tabel log_aktivitas_admin
                $check_table = mysqli_query($conn, "DESCRIBE log_aktivitas_admin");

                if ($check_table) {
                    $columns = [];
                    while ($col = mysqli_fetch_assoc($check_table)) {
                        $columns[] = $col['Field'];
                    }

                    // Deteksi nama kolom untuk admin_id
                    $admin_column = null;
                    $possible_admin_columns = ['id_admin', 'admin_id', 'user_id', 'id_user'];
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
                    if ($admin_column && in_array('aksi', $columns)) {
                        if (in_array('deskripsi', $columns)) {
                            if ($time_column) {
                                $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, deskripsi, $time_column) 
                                             VALUES ('$admin_id', '$aksi', '$deskripsi', NOW())";
                            } else {
                                $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, deskripsi) 
                                             VALUES ('$admin_id', '$aksi', '$deskripsi')";
                            }
                        } else if (in_array('target_id', $columns)) {
                            if ($time_column) {
                                $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, target_id, $time_column) 
                                             VALUES ('$admin_id', '$aksi', '$delete_id', NOW())";
                            } else {
                                $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, target_id) 
                                             VALUES ('$admin_id', '$aksi', '$delete_id')";
                            }
                        } else {
                            if ($time_column) {
                                $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, $time_column) 
                                             VALUES ('$admin_id', '$aksi', NOW())";
                            } else {
                                $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi) 
                                             VALUES ('$admin_id', '$aksi')";
                            }
                        }

                        // Execute log query
                        mysqli_query($conn, $log_query);
                    }
                }
            }

        } catch (Exception $e) {
            // Rollback jika ada error
            mysqli_rollback($conn);
            header("Location: users.php?page=$page&error=delete");
            exit;
        }
    }

    header("Location: users.php?page=$page&success=delete");
    exit;
}

//  EDIT STATUS 
if (isset($_POST['edit_status'])) {
    $edit_id = (int) $_POST['user_id'];
    $new_status = $_POST['status'] === 'aktif' ? 'aktif' : 'nonaktif';

    // Ambil nama user untuk log
    $user_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama, email FROM users WHERE id_user=$edit_id"));
    $nama_user = $user_data['nama'] ?? "ID $edit_id";

    // Update status user
    mysqli_query($conn, "UPDATE users SET status='$new_status' WHERE id_user=$edit_id");

    // LOG AKTIVITAS ADMIN 
    if (isset($_SESSION['user_id'])) {
        $admin_id = intval($_SESSION['user_id']);
        $aksi = mysqli_real_escape_string($conn, "Update Status User");
        $deskripsi = mysqli_real_escape_string($conn, "Admin mengubah status user $nama_user menjadi $new_status");

        // Cek struktur tabel log_aktivitas_admin
        $check_table = mysqli_query($conn, "DESCRIBE log_aktivitas_admin");

        if ($check_table) {
            $columns = [];
            while ($col = mysqli_fetch_assoc($check_table)) {
                $columns[] = $col['Field'];
            }

            // Deteksi nama kolom untuk admin_id
            $admin_column = null;
            $possible_admin_columns = ['id_admin', 'admin_id', 'user_id', 'id_user'];
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
            if ($admin_column && in_array('aksi', $columns)) {
                if (in_array('deskripsi', $columns)) {
                    if ($time_column) {
                        $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, deskripsi, $time_column) 
                                     VALUES ('$admin_id', '$aksi', '$deskripsi', NOW())";
                    } else {
                        $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, deskripsi) 
                                     VALUES ('$admin_id', '$aksi', '$deskripsi')";
                    }
                } else if (in_array('target_id', $columns)) {
                    if ($time_column) {
                        $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, target_id, $time_column) 
                                     VALUES ('$admin_id', '$aksi', '$edit_id', NOW())";
                    } else {
                        $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, target_id) 
                                     VALUES ('$admin_id', '$aksi', '$edit_id')";
                    }
                } else {
                    if ($time_column) {
                        $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi, $time_column) 
                                     VALUES ('$admin_id', '$aksi', NOW())";
                    } else {
                        $log_query = "INSERT INTO log_aktivitas_admin ($admin_column, aksi) 
                                     VALUES ('$admin_id', '$aksi')";
                    }
                }

                // Execute log query
                mysqli_query($conn, $log_query);
            }
        }
    }

    header("Location: users.php?page=$page&success=update");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User - YogyaStay</title>
    <link rel="icon" type="image/jpeg" href="../../assets/img/logonw.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            border: 2px solid #fca5a5;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 2px solid #93c5fd;
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

        /* DASHBOARD CARD */
        .user-dashboard {
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

        .total-users {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
            color: #92400e;
            border: 2px solid #fbbf24;
        }

        /* TABLE */
        .user-list {
            overflow-x: auto;
            margin-bottom: 25px;
        }

        .user-list table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
        }

        .user-list th,
        .user-list td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .user-list th {
            background: #f1f5f9;
            font-weight: 600;
            color: #475569;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .user-list tbody tr {
            transition: all 0.2s;
        }

        .user-list tbody tr:hover {
            background: #f8fafc;
        }

        .user-list td {
            color: #374151;
            font-size: 14px;
        }

        /* STATUS BADGE */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            white-space: nowrap;
        }

        .status-aktif {
            background: #d1fae5;
            color: #065f46;
        }

        .status-aktif i {
            color: #10b981;
        }

        .status-nonaktif {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-nonaktif i {
            color: #ef4444;
        }

        /* ACTION BUTTONS */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .status-dropdown {
            padding: 8px 12px;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            background: white;
            color: #374151;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .status-dropdown:hover {
            border-color: #fbbf24;
            background: #fef3c7;
        }

        .status-dropdown:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
        }

        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .btn-delete:hover {
            background: #fecaca;
            border-color: #ef4444;
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

        .pagination a.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
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

        /* RESPONSIVE DESIGN */

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

            .user-dashboard {
                padding: 25px;
            }

            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
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

            .user-dashboard {
                padding: 20px;
                border-radius: 16px;
            }

            .dashboard-header h2 {
                font-size: 18px;
            }

            .total-users {
                font-size: 14px;
                padding: 8px 16px;
            }

            .user-list {
                margin-bottom: 20px;
            }

            .user-list table {
                min-width: 100%;
                font-size: 13px;
            }

            .user-list th,
            .user-list td {
                padding: 12px 10px;
            }

            .user-list th {
                font-size: 12px;
            }

            .action-buttons {
                flex-direction: column;
                width: 100%;
                gap: 6px;
            }

            .status-dropdown,
            .btn-delete {
                width: 100%;
                justify-content: center;
                font-size: 12px;
            }

            .pagination a {
                padding: 8px 12px;
                font-size: 13px;
                min-width: 36px;
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

            .user-dashboard {
                padding: 15px;
            }

            .dashboard-header h2 {
                font-size: 16px;
            }

            .total-users {
                font-size: 13px;
            }

            .user-list th,
            .user-list td {
                padding: 10px 8px;
                font-size: 12px;
            }

            .status-badge {
                font-size: 11px;
                padding: 5px 10px;
            }

            .pagination a {
                padding: 6px 10px;
                font-size: 12px;
                min-width: 32px;
            }
        }

        /* Table Scroll Indicator */
        @media (max-width: 768px) {
            .user-list::after {
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
                <h1 class="page-title">Manajemen User</h1>
            </div>
        </div>

        <!-- ALERT SUCCESS -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <span style="font-size: 24px;">
                    <?php if ($_GET['success'] == 'delete'): ?>
                        ✓
                    <?php elseif ($_GET['success'] == 'update'): ?>
                        ✓
                    <?php endif; ?>
                </span>
                <span>
                    <?php if ($_GET['success'] == 'delete'): ?>
                        User berhasil dihapus!
                    <?php elseif ($_GET['success'] == 'update'): ?>
                        Status user berhasil diperbarui!
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>

        <!-- ALERT ERROR -->
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <span style="font-size: 24px;">✕</span>
                <span>
                    <?php if ($_GET['error'] == 'delete'): ?>
                        Gagal menghapus user. Silakan coba lagi.
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>

        <!-- DASHBOARD CARD -->
        <section class="user-dashboard">
            <div class="dashboard-header">
                <h2>Daftar User Terdaftar</h2>
                <div class="total-users">
                    <i class="fas fa-users"></i> Total: <?= $total ?> User
                </div>
            </div>

            <?php if (mysqli_num_rows($query) > 0): ?>
                <div class="user-list">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
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
                                        <strong><?= htmlspecialchars($row['nama']); ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row['email']); ?></td>
                                    <td>
                                        <?php if ($row['status'] == 'aktif'): ?>
                                            <span class="status-badge status-aktif">
                                                <i class="fas fa-check-circle"></i>
                                                Aktif
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-nonaktif">
                                                <i class="fas fa-times-circle"></i>
                                                Nonaktif
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" action="users.php?page=<?= $page; ?>" style="margin:0;"
                                                id="form-<?= $row['id_user']; ?>">
                                                <input type="hidden" name="user_id" value="<?= $row['id_user']; ?>">
                                                <select name="status" class="status-dropdown"
                                                    data-user-id="<?= $row['id_user']; ?>"
                                                    data-user-name="<?= htmlspecialchars($row['nama']); ?>"
                                                    data-current-status="<?= $row['status']; ?>">
                                                    <option value="aktif" <?= $row['status'] == 'aktif' ? 'selected' : ''; ?>>
                                                        ✓ Aktif
                                                    </option>
                                                    <option value="nonaktif" <?= $row['status'] == 'nonaktif' ? 'selected' : ''; ?>>
                                                        ✗ Nonaktif
                                                    </option>
                                                </select>
                                                <input type="hidden" name="edit_status" value="1">
                                            </form>
                                            <a href="#"
                                                onclick="confirmDelete(<?= $row['id_user']; ?>, '<?= htmlspecialchars($row['nama']); ?>', <?= $page; ?>); return false;"
                                                class="btn-delete">
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
                            <a href="users.php?page=1" title="Halaman Pertama">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="users.php?page=<?= $page - 1 ?>" title="Sebelumnya">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php
                        $range = 2;
                        for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++):
                            ?>
                            <a href="users.php?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="users.php?page=<?= $page + 1 ?>" title="Selanjutnya">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="users.php?page=<?= $total_pages ?>" title="Halaman Terakhir">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-users-slash"></i>
                    </div>
                    <h3>Belum Ada User</h3>
                    <p>Tidak ada user terdaftar di sistem</p>
                </div>
            <?php endif; ?>
        </section>

    </div>

    <script>
        // Alert auto hide
        const alert = document.querySelector('.alert');
        if (alert) {
            setTimeout(() => {
                alert.style.animation = 'slideInDown 0.3s ease reverse';
                setTimeout(() => alert.remove(), 300);
            }, 4000);
        }

        // Konfirmasi perubahan status dengan SweetAlert
        document.querySelectorAll('.status-dropdown').forEach(select => {
            select.addEventListener('change', function (e) {
                const userId = this.getAttribute('data-user-id');
                const userName = this.getAttribute('data-user-name');
                const currentStatus = this.getAttribute('data-current-status');
                const newStatus = this.value;
                const selectElement = this;
                const form = document.getElementById('form-' + userId);

                if (currentStatus === newStatus) {
                    return;
                }

                // SweetAlert konfirmasi
                Swal.fire({
                    title: 'Konfirmasi Perubahan Status',
                    html: `Apakah Anda yakin ingin mengubah status user <strong>${userName}</strong> menjadi <strong>${newStatus}</strong>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#fbbf24',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Ubah Status',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    } else {
                        selectElement.value = currentStatus;
                    }
                });
            });
        });

        // Konfirmasi hapus user dengan SweetAlert
        function confirmDelete(userId, userName, page) {
            Swal.fire({
                title: 'Konfirmasi Hapus User',
                html: `Apakah Anda yakin ingin menghapus user <strong>${userName}</strong>?<br><br><span style="color: #dc2626;">Data user, booking, wishlist, dan review akan dihapus permanen!</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `users.php?delete_id=${userId}&page=${page}`;
                }
            });
        }
    </script>

</body>

</html>
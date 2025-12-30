<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../log/functions.php';

// ✅ DEBUG SESSION
error_log("========== DEBUG SESSION ==========");
error_log("Session ID User: " . ($_SESSION['id_user'] ?? 'TIDAK ADA'));
error_log("Session Role: " . ($_SESSION['role_id'] ?? 'TIDAK ADA'));
error_log("Session Nama: " . ($_SESSION['nama'] ?? 'TIDAK ADA'));
error_log("===================================");

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 'admin') {
    header("Location: " . dirname(dirname($_SERVER['PHP_SELF'])) . "/../autentikasi/login.php");
    exit();
}

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 'admin') {
    header("Location: " . dirname(dirname($_SERVER['PHP_SELF'])) . "/../autentikasi/login.php");
    exit();
}

// total
function getTotal($conn, $query)
{
    $res = mysqli_query($conn, $query);
    if (!$res)
        return 0;
    $row = mysqli_fetch_assoc($res);
    return $row['total'] ?? 0;
}


// Ambil filter status dari URL
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'semua';

/*  STATISTIK CARD  */
$refund_pending = getTotal($conn, "
    SELECT COUNT(*) AS total 
    FROM pembatalan 
    WHERE status_pembatalan='diproses'
");

$pembatalan_today = getTotal($conn, "
    SELECT COUNT(*) AS total 
    FROM pembatalan 
    WHERE DATE(tanggal_pengajuan)=CURDATE()
");

$total_refund = getTotal($conn, "
    SELECT SUM(total_refund) AS total 
    FROM pembatalan 
    WHERE status_pembatalan='disetujui'
");

// Handle aksi refund/tolak 
if (isset($_POST['action']) && isset($_POST['id_pembatalan'])) {
    $id_pembatalan = intval($_POST['id_pembatalan']);
    $action = $_POST['action'];
    $id_admin = intval($_SESSION['id_user']);

    // Debug log
    error_log("=== PEMBATALAN DEBUG ===");
    error_log("Action: $action");
    error_log("ID Pembatalan: $id_pembatalan");
    error_log("ID Admin dari Session: $id_admin");

    if ($action == 'approve') {
        // Ambil data pembatalan lengkap
        $get_pembatalan = mysqli_query($conn, "
            SELECT 
                p.id_booking,
                p.total_refund,
                p.alasan,
                b.kode_booking,
                u.nama AS nama_pelanggan,
                py.id_pembayaran
            FROM pembatalan p
            JOIN booking b ON p.id_booking = b.id_booking
            JOIN users u ON p.id_user = u.id_user
            JOIN pembayaran py ON b.id_booking = py.id_booking
            WHERE p.id_pembatalan = $id_pembatalan
        ");

        if (!$get_pembatalan) {
            $_SESSION['error'] = "Error query: " . mysqli_error($conn);
            error_log("ERROR Query pembatalan: " . mysqli_error($conn));
            header("Location: pembatalan.php");
            exit();
        }

        $pembatalan_data = mysqli_fetch_assoc($get_pembatalan);

        if (!$pembatalan_data) {
            $_SESSION['error'] = "Data pembatalan tidak ditemukan";
            error_log("ERROR: Data pembatalan ID $id_pembatalan tidak ditemukan");
            header("Location: pembatalan.php");
            exit();
        }

        $id_booking = $pembatalan_data['id_booking'];
        $id_pembayaran = $pembatalan_data['id_pembayaran'];
        $kode_booking = $pembatalan_data['kode_booking'];
        $nama_pelanggan = $pembatalan_data['nama_pelanggan'];
        $total_refund = $pembatalan_data['total_refund'];
        $alasan = mysqli_real_escape_string($conn, $pembatalan_data['alasan']);

        error_log("Data pembatalan ditemukan - Booking: $kode_booking, Refund: $total_refund, Pembayaran ID: $id_pembayaran");

        // Mulai transaction
        mysqli_begin_transaction($conn);

        try {
            // 1. Update pembatalan
            $update_pembatalan = mysqli_query($conn, "
                UPDATE pembatalan 
                SET status_pembatalan='disetujui',
                    tanggal_diproses=NOW(),
                    diproses_oleh=$id_admin
                WHERE id_pembatalan=$id_pembatalan
            ");

            if (!$update_pembatalan) {
                throw new Exception("Gagal update pembatalan: " . mysqli_error($conn));
            }

            error_log("✓ Update pembatalan berhasil");

            // 2. Update status booking
            $update_booking = mysqli_query($conn, "
                UPDATE booking 
                SET status_reservasi='dibatalkan' 
                WHERE id_booking=$id_booking
            ");

            if (!$update_booking) {
                throw new Exception("Gagal update booking: " . mysqli_error($conn));
            }

            error_log("✓ Update booking berhasil");

            // 3. Insert ke Tabel Refund
            $insert_refund = mysqli_query($conn, "
                INSERT INTO refund (
                    id_pembayaran,
                    id_pembatalan,
                    id_admin,
                    jumlah_refund,
                    alasan_refund,
                    status_refund,
                    tanggal_refund
                ) VALUES (
                    $id_pembayaran,
                    $id_pembatalan,
                    $id_admin,
                    $total_refund,
                    '$alasan',
                    'selesai',
                    NOW()
                )
            ");

            if (!$insert_refund) {
                throw new Exception("Gagal insert ke tabel refund: " . mysqli_error($conn));
            }

            error_log("✓ Insert ke tabel refund berhasil");

            // 4. Update status pembayaran menjadi 'refund'
            $update_pembayaran = mysqli_query($conn, "
                UPDATE pembayaran 
                SET status_pembayaran='refund'
                WHERE id_pembayaran=$id_pembayaran
            ");

            if (!$update_pembayaran) {
                throw new Exception("Gagal update status pembayaran: " . mysqli_error($conn));
            }

            error_log("✓ Update status pembayaran berhasil");

            // 5. Log aktivitas
            $deskripsi_log = "Admin ID $id_admin menyetujui pembatalan booking $kode_booking atas nama $nama_pelanggan dengan total refund Rp " . number_format($total_refund, 0, ',', '.');

            // DEBUG - CEK ID ADMIN
            error_log("DEBUG: ID Admin dari Session = " . $id_admin);

            $check_admin = mysqli_query($conn, "SELECT id_user FROM users WHERE id_user = $id_admin AND role_id = 'admin'");
            if (!$check_admin || mysqli_num_rows($check_admin) == 0) {
                error_log("ERROR: ID Admin $id_admin tidak ditemukan di tabel users!");
            } else {
                addAdminLog($conn, $id_admin, 'Approve Pembatalan', $deskripsi_log);
                error_log("✓ Log aktivitas berhasil disimpan");
            }

            // 6. Commit transaction
            mysqli_commit($conn);

            error_log("✓✓✓ TRANSAKSI BERHASIL - Commit selesai dengan integrasi refund");

            $_SESSION['success'] = "Refund berhasil disetujui! Dana akan dikembalikan ke pelanggan.";
            $_SESSION['alert_type'] = 'approve';

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error'] = $e->getMessage();
            error_log("ERROR TRANSAKSI: " . $e->getMessage());
        }

    } elseif ($action == 'reject') {
        $alasan_admin = mysqli_real_escape_string($conn, $_POST['alasan_admin'] ?? 'Tidak memenuhi syarat');

        // Ambil data pembatalan
        $get_pembatalan = mysqli_query($conn, "
            SELECT 
                p.id_booking,
                b.kode_booking,
                u.nama AS nama_pelanggan
            FROM pembatalan p
            JOIN booking b ON p.id_booking = b.id_booking
            JOIN users u ON p.id_user = u.id_user
            WHERE p.id_pembatalan = $id_pembatalan
        ");

        if (!$get_pembatalan) {
            $_SESSION['error'] = "Error query: " . mysqli_error($conn);
            error_log("ERROR Query pembatalan: " . mysqli_error($conn));
            header("Location: pembatalan.php");
            exit();
        }

        $pembatalan_data = mysqli_fetch_assoc($get_pembatalan);

        if (!$pembatalan_data) {
            $_SESSION['error'] = "Data pembatalan tidak ditemukan";
            error_log("ERROR: Data pembatalan ID $id_pembatalan tidak ditemukan");
            header("Location: pembatalan.php");
            exit();
        }

        $id_booking = $pembatalan_data['id_booking'];
        $kode_booking = $pembatalan_data['kode_booking'];
        $nama_pelanggan = $pembatalan_data['nama_pelanggan'];

        error_log("Data pembatalan ditemukan - Booking: $kode_booking");

        // Mulai transaction
        mysqli_begin_transaction($conn);

        try {
            // 1. Update pembatalan
            $update_pembatalan = mysqli_query($conn, "
                UPDATE pembatalan 
                SET status_pembatalan='ditolak',
                    tanggal_diproses=NOW(),
                    alasan_admin='$alasan_admin',
                    diproses_oleh=$id_admin
                WHERE id_pembatalan=$id_pembatalan
            ");

            if (!$update_pembatalan) {
                throw new Exception("Gagal update pembatalan: " . mysqli_error($conn));
            }

            error_log("✓ Update pembatalan berhasil");

            // 2. Update status booking kembali ke dipesan
            $update_booking = mysqli_query($conn, "
                UPDATE booking 
                SET status_reservasi='dipesan' 
                WHERE id_booking=$id_booking
            ");

            if (!$update_booking) {
                throw new Exception("Gagal update booking: " . mysqli_error($conn));
            }

            error_log("✓ Update booking berhasil");

            // 3. Log aktivitas
            $deskripsi_log = "Admin ID $id_admin menolak pembatalan booking $kode_booking atas nama $nama_pelanggan. Alasan: $alasan_admin";

            // DEBUG - CEK ID ADMIN
            error_log("DEBUG: ID Admin dari Session = " . $id_admin);

            // Cek apakah ID admin valid di database
            $check_admin = mysqli_query($conn, "SELECT id_user FROM users WHERE id_user = $id_admin AND role_id = 'admin'");
            if (!$check_admin || mysqli_num_rows($check_admin) == 0) {
                error_log("ERROR: ID Admin $id_admin tidak ditemukan di tabel users!");
            } else {
                addAdminLog($conn, $id_admin, 'Tolak Pembatalan', $deskripsi_log);
                error_log("✓ Log aktivitas berhasil disimpan");
            }

            // 4. Commit transaction
            mysqli_commit($conn);

            error_log("✓✓✓ TRANSAKSI BERHASIL - Commit selesai");

            $_SESSION['success'] = "Pembatalan berhasil ditolak! Reservasi dikembalikan ke status dipesan.";
            $_SESSION['alert_type'] = 'reject';

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error'] = $e->getMessage();
            error_log("ERROR TRANSAKSI: " . $e->getMessage());
        }
    }

    // REDIRECT KE HALAMAN YANG SAMA
    header("Location: pembatalan.php");
    exit();
}

/*  BUILD WHERE CLAUSE UNTUK FILTER  */
$where_clause = "";
switch ($filter_status) {
    case 'diproses':
        $where_clause = "WHERE p.status_pembatalan = 'diproses'";
        break;
    case 'refund':
        $where_clause = "WHERE p.status_pembatalan = 'disetujui'";
        break;
    case 'ditolak':
        $where_clause = "WHERE p.status_pembatalan = 'ditolak'";
        break;
    default:
        $where_clause = "";
        break;
}

/*  DAFTAR PERMINTAAN PEMBATALAN  */
$query_pembatalan = mysqli_query($conn, "
    SELECT 
        p.id_pembatalan,
        p.id_booking,
        p.alasan,
        p.biaya_pembatalan,
        p.total_refund,
        p.tanggal_pengajuan,
        p.status_pembatalan,
        p.alasan_admin,
        b.kode_booking,
        u.nama AS nama_pelanggan,
        u.email AS email_pelanggan
    FROM pembatalan p
    JOIN booking b ON p.id_booking = b.id_booking
    JOIN users u ON p.id_user = u.id_user
    $where_clause
    ORDER BY 
        CASE 
            WHEN p.status_pembatalan='diproses' THEN 1
            WHEN p.status_pembatalan='disetujui' THEN 2
            WHEN p.status_pembatalan='ditolak' THEN 3
        END,
        p.tanggal_pengajuan DESC
");

$total_filtered = mysqli_num_rows($query_pembatalan);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembatalan Kamar & Refund - YogyaStay</title>

    <link rel="icon" type="image/jpeg" href="../../assets/img/logonw.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #fff7ed;
            color: #1f2937;
        }

        .layout {
            display: flex;
            min-height: 100vh
        }

        .content {
            margin-left: 240px;
            padding: 30px;
            width: 100%;
            max-width: 1600px
        }

        .topbar {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 30px;
        }

        .toggle-btn {
            display: none;
            font-size: 26px;
            background: none;
            border: none;
            cursor: pointer
        }

        h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            border-bottom: 4px solid #f59e0b;
            display: inline-block;
            padding-bottom: 8px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
            margin-bottom: 40px;
        }

        .card {
            background: #fff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, .12);
        }

        .card.red {
            border-left: 6px solid #ef4444
        }

        .card.orange {
            border-left: 6px solid #f59e0b
        }

        .card.green {
            border-left: 6px solid #10b981
        }

        .card small {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
            display: block;
            margin-bottom: 10px;
        }

        .card h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }

        .filter-section {
            background: #fff;
            padding: 20px 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-label {
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .filter-btn:hover {
            border-color: #f59e0b;
            color: #f59e0b;
            transform: translateY(-2px);
        }

        .filter-btn.active {
            background: #f59e0b;
            border-color: #f59e0b;
            color: white;
        }

        .table-section {
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .table-section h3 {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .result-count {
            font-size: 14px;
            color: #666;
            background: #f3f4f6;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        thead {
            background: #f3f4f6;
        }

        th {
            padding: 15px 12px;
            text-align: center;
            font-weight: 600;
            color: #374151;
            font-size: 13px;
            border-bottom: 2px solid #e5e7eb;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 18px 12px;
            font-size: 14px;
            color: #4b5563;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        td:nth-child(4),
        td:nth-child(5) {
            text-align: right;
        }

        td:nth-child(7) {
            text-align: center;
        }

        tr:hover {
            background: #f9fafb;
        }

        .id-text {
            font-weight: 600;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin: 2px;
        }

        .btn-refund {
            background: #10b981;
            color: #fff;
        }

        .btn-refund:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .btn-tolak {
            background: #ef4444;
            color: #fff;
        }

        .btn-tolak:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
        }

        .status-refund {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .status-ditolak {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-content h3 {
            margin-bottom: 20px;
            color: #1f2937;
            font-size: 20px;
            font-weight: 600;
        }

        .modal-content textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            margin-bottom: 20px;
            resize: vertical;
            min-height: 100px;
            transition: border-color 0.3s;
        }

        .modal-content textarea:focus {
            outline: none;
            border-color: #ef4444;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-cancel {
            background: #e5e7eb;
            color: #4b5563;
        }

        .btn-cancel:hover {
            background: #d1d5db;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 80px;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-title {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .empty-text {
            font-size: 14px;
            color: #6b7280;
        }

        @media(max-width:968px) {
            .content {
                margin-left: 0;
                padding: 20px
            }

            .toggle-btn {
                display: block
            }

            .cards {
                grid-template-columns: 1fr
            }

            h1 {
                font-size: 24px
            }

            .filter-section {
                flex-direction: column;
                align-items: flex-start
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px
            }
        }
    </style>
</head>

<body>

    <div class="layout">
        <?php include '../partials/sidebar.php'; ?>

        <div class="content">
            <div class="topbar">
                <h1>Pembatalan Kamar & Refund</h1>
            </div>

            <div class="cards">
                <div class="card red">
                    <small>Refund Menunggu Diproses</small>
                    <h2><?= $refund_pending ?> Permintaan</h2>
                </div>
                <div class="card orange">
                    <small>Pembatalan Hari Ini</small>
                    <h2><?= $pembatalan_today ?> Transaksi</h2>
                </div>
                <div class="card green">
                    <small>Total Refund Selesai</small>
                    <h2>Rp <?= number_format($total_refund, 0, ',', '.') ?></h2>
                </div>
            </div>

            <div class="filter-section">
                <span class="filter-label">Filter Status:</span>
                <div class="filter-buttons">
                    <a href="?status=semua" class="filter-btn <?= $filter_status == 'semua' ? 'active' : '' ?>">
                        Semua
                    </a>
                    <a href="?status=diproses" class="filter-btn <?= $filter_status == 'diproses' ? 'active' : '' ?>">
                        Diproses
                    </a>
                    <a href="?status=refund" class="filter-btn <?= $filter_status == 'refund' ? 'active' : '' ?>">
                        Refund
                    </a>
                    <a href="?status=ditolak" class="filter-btn <?= $filter_status == 'ditolak' ? 'active' : '' ?>">
                        Ditolak
                    </a>
                </div>
            </div>

            <div class="table-section">
                <div class="table-header">
                    <h3>Daftar Permintaan Pembatalan</h3>
                    <span class="result-count">
                        <?= $total_filtered ?> data ditemukan
                    </span>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Reservasi</th>
                                <th>Pelanggan</th>
                                <th>Alasan</th>
                                <th>Biaya Pembatalan</th>
                                <th>Total Refund</th>
                                <th>Tanggal Pengajuan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($query_pembatalan && mysqli_num_rows($query_pembatalan) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($query_pembatalan)): ?>
                                    <tr>
                                        <td>
                                            <span class="id-text">
                                                <?= htmlspecialchars($row['kode_booking']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($row['nama_pelanggan']) ?></strong><br>
                                            <small
                                                style="color:#9ca3af"><?= htmlspecialchars($row['email_pelanggan']) ?></small>
                                        </td>
                                        <td style="max-width:250px">
                                            <?= htmlspecialchars($row['alasan']) ?>
                                        </td>
                                        <td style="color:#ef4444;font-weight:600">
                                            Rp <?= number_format($row['biaya_pembatalan'], 0, ',', '.') ?>
                                        </td>
                                        <td style="color:#10b981;font-weight:600">
                                            Rp <?= number_format($row['total_refund'], 0, ',', '.') ?>
                                        </td>
                                        <td>
                                            <?= date('d M Y, H:i', strtotime($row['tanggal_pengajuan'])) ?>
                                        </td>
                                        <td>
                                            <?php if ($row['status_pembatalan'] == 'diproses'): ?>
                                                <button type="button" class="btn btn-refund" data-id="<?= $row['id_pembatalan'] ?>"
                                                    data-kode="<?= htmlspecialchars($row['kode_booking']) ?>"
                                                    data-nama="<?= htmlspecialchars($row['nama_pelanggan']) ?>"
                                                    data-refund="<?= $row['total_refund'] ?>" onclick="confirmRefund(this)">
                                                    ✓ Refund
                                                </button>
                                                <button type="button" class="btn btn-tolak" data-id="<?= $row['id_pembatalan'] ?>"
                                                    data-kode="<?= htmlspecialchars($row['kode_booking']) ?>"
                                                    data-nama="<?= htmlspecialchars($row['nama_pelanggan']) ?>"
                                                    onclick="openRejectModal(this)">
                                                    ✗ Tolak
                                                </button>
                                            <?php elseif ($row['status_pembatalan'] == 'disetujui'): ?>
                                                <span class="status-badge status-refund">Refund</span>
                                            <?php else: ?>
                                                <span class="status-badge status-ditolak">Ditolak</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <div class="empty-icon">📭</div>
                                            <h4 class="empty-title">Tidak Ada Data</h4>
                                            <p class="empty-text">
                                                <?php
                                                switch ($filter_status) {
                                                    case 'diproses':
                                                        echo 'Tidak ada pembatalan yang sedang diproses saat ini';
                                                        break;
                                                    case 'refund':
                                                        echo 'Tidak ada refund yang telah disetujui';
                                                        break;
                                                    case 'ditolak':
                                                        echo 'Tidak ada pembatalan yang ditolak';
                                                        break;
                                                    default:
                                                        echo 'Tidak ada data pembatalan tersedia';
                                                }
                                                ?>
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

    <!-- Modal Tolak -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <h3>Alasan Penolakan</h3>
            <form method="POST" id="rejectForm" onsubmit="return submitReject(event)">
                <input type="hidden" name="id_pembatalan" id="reject_id">
                <input type="hidden" name="action" value="reject">
                <textarea name="alasan_admin" id="alasan_admin"
                    placeholder="Masukkan alasan penolakan (minimal 10 karakter)..." required></textarea>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-cancel" onclick="closeRejectModal()">Batal</button>
                    <button type="submit" class="btn btn-tolak">Tolak Pembatalan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar')?.classList.toggle('active');
        }

        let currentRejectData = {
            id: '',
            kodeBooking: '',
            namaPelanggan: ''
        };

        function openRejectModal(button) {
            const id = button.getAttribute('data-id');
            const kode = button.getAttribute('data-kode');
            const nama = button.getAttribute('data-nama');

            currentRejectData = {
                id: id || '',
                kodeBooking: kode || 'Tidak tersedia',
                namaPelanggan: nama || 'Tidak tersedia'
            };

            document.getElementById('reject_id').value = id;
            document.getElementById('alasan_admin').value = '';
            document.getElementById('rejectModal').classList.add('active');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.remove('active');
        }

        window.onclick = function (event) {
            const modal = document.getElementById('rejectModal');
            if (event.target == modal) {
                closeRejectModal();
            }
        }

        function confirmRefund(button) {
            const id = button.getAttribute('data-id');
            const kodeBooking = button.getAttribute('data-kode');
            const namaPelanggan = button.getAttribute('data-nama');
            const totalRefund = parseFloat(button.getAttribute('data-refund'));

            Swal.fire({
                title: 'Setujui Refund?',
                html: `
            <div style="text-align: left; padding: 15px;">
                <div style="background: #f3f4f6; padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                    <p style="margin-bottom: 8px;"><strong>Kode Booking:</strong> ${kodeBooking}</p>
                    <p style="margin-bottom: 8px;"><strong>Pelanggan:</strong> ${namaPelanggan}</p>
                    <p style="margin-bottom: 0;"><strong>Total Refund:</strong> <span style="color: #10b981; font-weight: 700;">Rp ${totalRefund.toLocaleString('id-ID')}</span></p>
                </div>
                <div style="background: #d1fae5; padding: 12px; border-radius: 8px; border-left: 4px solid #10b981;">
                    <p style="color: #065f46; font-size: 13px; margin: 0;">
                        ✓ Dana akan dikembalikan ke pelanggan dalam 3-7 hari kerja<br>
                        ✓ Data refund akan dicatat ke sistem secara otomatis
                    </p>
                </div>
            </div>
        `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '✓ Ya, Setujui Refund',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                width: '500px'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses Refund...',
                        html: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'pembatalan.php';
                    form.innerHTML = `
                <input type="hidden" name="id_pembatalan" value="${id}">
                <input type="hidden" name="action" value="approve">
            `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function submitReject(event) {
            event.preventDefault();

            const alasan = document.getElementById('alasan_admin').value.trim();

            if (alasan.length < 10) {
                Swal.fire({
                    title: 'Alasan Terlalu Singkat!',
                    text: 'Alasan penolakan minimal 10 karakter',
                    icon: 'warning',
                    confirmButtonColor: '#ef4444'
                });
                return false;
            }

            closeRejectModal();

            Swal.fire({
                title: 'Tolak Pembatalan?',
                html: `
            <div style="text-align: left; padding: 15px;">
                <div style="background: #f3f4f6; padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                    <p style="margin-bottom: 8px;"><strong>Kode Booking:</strong> ${currentRejectData.kodeBooking}</p>
                    <p style="margin-bottom: 8px;"><strong>Pelanggan:</strong> ${currentRejectData.namaPelanggan}</p>
                    <p style="margin-bottom: 0;"><strong>Alasan:</strong> ${alasan}</p>
                </div>
                <div style="background: #fee2e2; padding: 12px; border-radius: 8px; border-left: 4px solid #ef4444;">
                    <p style="color: #991b1b; font-size: 13px; margin: 0;">
                        ⚠ Reservasi akan dikembalikan ke status dipesan
                    </p>
                </div>
            </div>
        `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '✗ Ya, Tolak Pembatalan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                width: '500px'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses Penolakan...',
                        html: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    document.getElementById('rejectForm').submit();
                } else {
                    openRejectModal(document.querySelector(`[data-id="${currentRejectData.id}"]`));
                }
            });

            return false;
        }

        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['success'] ?>',
                confirmButtonColor: '#10b981'
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?= $_SESSION['error'] ?>',
                confirmButtonColor: '#ef4444'
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>

</body>

</html>
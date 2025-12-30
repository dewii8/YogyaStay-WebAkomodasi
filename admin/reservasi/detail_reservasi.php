<?php
require_once '../../config.php';

if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 'admin') {
    header("Location: ../../autentikasi/login.php");
    exit();
}

// Handle check-in action
if(isset($_POST['action']) && $_POST['action'] === 'checkin' && isset($_POST['id_booking'])) {
    $id_booking = intval($_POST['id_booking']);
    $id_admin = intval($_SESSION['id_user']);
    
    // Get booking details for logging
    $booking_query = "SELECT b.kode_booking, u.nama FROM booking b 
                      JOIN users u ON b.id_user = u.id_user 
                      WHERE b.id_booking = ?";
    $stmt_booking = $conn->prepare($booking_query);
    $stmt_booking->bind_param("i", $id_booking);
    $stmt_booking->execute();
    $booking_result = $stmt_booking->get_result();
    $booking_info = $booking_result->fetch_assoc();
    
    // Update booking status to check-in
    $update_query = "UPDATE booking SET status_reservasi = 'check-in' WHERE id_booking = ?";
    $stmt_update = $conn->prepare($update_query);
    
    if($stmt_update) {
        $stmt_update->bind_param("i", $id_booking);
        
        if($stmt_update->execute()) {
            // Log aktivitas admin
            $log_query = "INSERT INTO log_aktivitas_admin (id_admin, aksi, deskripsi, created_at) VALUES (?, ?, ?, NOW())";
            $stmt_log = $conn->prepare($log_query);
            
            if($stmt_log) {
                $aksi = "Check-In Reservasi";
                $kode_booking = $booking_info['kode_booking'] ?? $id_booking;
                $nama_user = $booking_info['nama'] ?? 'User';
                $deskripsi = "Admin ID $id_admin melakukan check-in untuk booking $kode_booking atas nama $nama_user";
                $stmt_log->bind_param("iss", $id_admin, $aksi, $deskripsi);
                $stmt_log->execute();
            }
            
            $_SESSION['success'] = "Check-in berhasil dikonfirmasi!";
        } else {
            $_SESSION['error'] = "Gagal melakukan check-in: " . $conn->error;
        }
    }
    
    header("Location: detail_reservasi.php?id=" . $id_booking);
    exit();
}

// Handle cancel booking action
if(isset($_POST['action']) && $_POST['action'] === 'cancel' && isset($_POST['id_booking'])) {
    $id_booking = intval($_POST['id_booking']);
    $id_admin = intval($_SESSION['id_user']);
    $alasan = isset($_POST['alasan']) ? trim($_POST['alasan']) : 'Dibatalkan oleh admin';
    
    // Get booking details for logging
    $booking_query = "SELECT b.kode_booking, u.nama FROM booking b 
                      JOIN users u ON b.id_user = u.id_user 
                      WHERE b.id_booking = ?";
    $stmt_booking = $conn->prepare($booking_query);
    $stmt_booking->bind_param("i", $id_booking);
    $stmt_booking->execute();
    $booking_result = $stmt_booking->get_result();
    $booking_info = $booking_result->fetch_assoc();
    
    // Update booking status to dibatalkan
    $update_query = "UPDATE booking SET status_reservasi = 'dibatalkan' WHERE id_booking = ?";
    $stmt_update = $conn->prepare($update_query);
    
    if($stmt_update) {
        $stmt_update->bind_param("i", $id_booking);
        
        if($stmt_update->execute()) {
            // Log aktivitas admin
            $log_query = "INSERT INTO log_aktivitas_admin (id_admin, aksi, deskripsi, created_at) VALUES (?, ?, ?, NOW())";
            $stmt_log = $conn->prepare($log_query);
            
            if($stmt_log) {
                $aksi = "Batalkan Reservasi";
                $kode_booking = $booking_info['kode_booking'] ?? $id_booking;
                $nama_user = $booking_info['nama'] ?? 'User';
                $deskripsi = "Admin ID $id_admin membatalkan booking $kode_booking atas nama $nama_user. Alasan: $alasan";
                $stmt_log->bind_param("iss", $id_admin, $aksi, $deskripsi);
                $stmt_log->execute();
            }
            
            $_SESSION['success'] = "Reservasi berhasil dibatalkan!";
        } else {
            $_SESSION['error'] = "Gagal membatalkan reservasi: " . $conn->error;
        }
    }
    
    header("Location: detail_reservasi.php?id=" . $id_booking);
    exit();
}

// Handle checkout action  
if(isset($_POST['action']) && $_POST['action'] === 'checkout' && isset($_POST['id_booking'])) {
    $id_booking = intval($_POST['id_booking']);
    $id_admin = intval($_SESSION['id_user']);
    
    // Get booking details for logging
    $booking_query = "SELECT b.kode_booking, u.nama FROM booking b 
                      JOIN users u ON b.id_user = u.id_user 
                      WHERE b.id_booking = ?";
    $stmt_booking = $conn->prepare($booking_query);
    $stmt_booking->bind_param("i", $id_booking);
    $stmt_booking->execute();
    $booking_result = $stmt_booking->get_result();
    $booking_info = $booking_result->fetch_assoc();
    
    // Update booking status to selesai
    $update_query = "UPDATE booking SET status_reservasi = 'selesai' WHERE id_booking = ?";
    $stmt_update = $conn->prepare($update_query);
    
    if($stmt_update) {
        $stmt_update->bind_param("i", $id_booking);
        
        if($stmt_update->execute()) {
            // Log aktivitas admin
            $log_query = "INSERT INTO log_aktivitas_admin (id_admin, aksi, deskripsi, created_at) VALUES (?, ?, ?, NOW())";
            $stmt_log = $conn->prepare($log_query);
            
            if($stmt_log) {
                $aksi = "Check-Out Reservasi";
                $kode_booking = $booking_info['kode_booking'] ?? $id_booking;
                $nama_user = $booking_info['nama'] ?? 'User';
                $deskripsi = "Admin ID $id_admin melakukan check-out untuk booking $kode_booking atas nama $nama_user";
                $stmt_log->bind_param("iss", $id_admin, $aksi, $deskripsi);
                $stmt_log->execute();
            }
            
            $_SESSION['success'] = "Check-out berhasil dikonfirmasi!";
        } else {
            $_SESSION['error'] = "Gagal melakukan check-out: " . $conn->error;
        }
    }
    
    header("Location: detail_reservasi.php?id=" . $id_booking);
    exit();
}

// Get booking ID from URL
$id_booking = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id_booking <= 0) {
    header("Location: ../dashboard.php");
    exit();
}

// Fetch reservation data
$query = "SELECT 
    b.*,
    u.nama as nama_user,
    u.email as email_user,
    p.nama_penginapan,
    tk.nama_tipe,
    tk.harga_per_malam
FROM booking b
LEFT JOIN users u ON b.id_user = u.id_user
LEFT JOIN penginapan p ON b.id_penginapan = p.id_penginapan
LEFT JOIN tipe_kamar tk ON b.id_tipe_kamar = tk.id_tipe_kamar
WHERE b.id_booking = ?";

$stmt = $conn->prepare($query);

// Check if prepare failed
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error . "<br>Query: " . $query);
}

$stmt->bind_param("i", $id_booking);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    header("Location: ../dashboard.php");
    exit();
}

$reservation = $result->fetch_assoc();

// Fetch check-in details if status is check-in or selesai
$checkin_data = null;
if(in_array($reservation['status_reservasi'], ['check-in', 'selesai'])) {
    $checkin_query = "SELECT * FROM checkin WHERE id_booking = ?";
    $stmt_checkin = $conn->prepare($checkin_query);
    
    if ($stmt_checkin === false) {
        error_log("Error preparing checkin statement: " . $conn->error);
    } else {
        $stmt_checkin->bind_param("i", $id_booking);
        $stmt_checkin->execute();
        $checkin_result = $stmt_checkin->get_result();
        if($checkin_result->num_rows > 0) {
            $checkin_data = $checkin_result->fetch_assoc();
        }
    }
}

// Function to get status badge color
function getStatusColor($status) {
    switch($status) {
        case 'dipesan': 
        case '':
            return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'check-in': 
            return 'bg-green-100 text-green-700 border-green-200';
        case 'selesai': 
            return 'bg-gray-100 text-gray-700 border-gray-200';
        case 'dibatalkan': 
            return 'bg-red-100 text-red-700 border-red-200';
        case 'menunggu_pembatalan': 
            return 'bg-yellow-100 text-yellow-700 border-yellow-200';
        default: 
            return 'bg-gray-100 text-gray-700';
    }
}

// Function to format currency
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Display status with default
$display_status = empty($reservation['status_reservasi']) ? 'dipesan' : $reservation['status_reservasi'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Reservasi - <?php echo htmlspecialchars($reservation['kode_booking']); ?></title>
    
    <link rel="icon" type="image/jpeg" href="../../assets/img/logonw.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
            color: #1f2937;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .content {
            margin-left: 240px;
            padding: 30px;
            width: 100%;
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
            cursor: pointer;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.3s;
        }

        .back-btn:hover {
            color: #1f2937;
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

        .header-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-outline {
            background: #fff;
            color: #6b7280;
            border: 2px solid #e5e7eb;
        }

        .btn-outline:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 25px;
            margin-top: 30px;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #f3f4f6;
        }

        .card-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .card-header p {
            font-size: 13px;
            color: #6b7280;
            margin: 5px 0 0 0;
        }

        .card-body {
            padding: 25px;
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-badge.dipesan {
            background: #fef3c7;
            color: #92400e;
            border: 2px solid #fde68a;
        }

        .status-badge.check-in {
            background: #dbeafe;
            color: #1e40af;
            border: 2px solid #bfdbfe;
        }

        .status-badge.selesai {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #a7f3d0;
        }

        .status-badge.dibatalkan {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #fecaca;
        }

        .status-badge.menunggu_pembatalan {
            background: #fce7f3;
            color: #831843;
            border: 2px solid #fbcfe8;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            padding: 20px 0;
            border-top: 1px solid #f3f4f6;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 11px;
            color: #9ca3af;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }

        .info-value.code {
            color: #3b82f6;
            font-family: monospace;
        }

        .penginapan-name {
            font-size: 11px;
            color: #9ca3af;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin: 20px 0 8px 0;
        }

        .penginapan-name + p {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }

        .detail-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 25px;
        }

        .detail-col {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .icon-box.blue {
            background: #dbeafe;
            color: #3b82f6;
        }

        .icon-box.orange {
            background: #fed7aa;
            color: #f59e0b;
        }

        .icon-box.purple {
            background: #e9d5ff;
            color: #a855f7;
        }

        .icon-box.green {
            background: #d1fae5;
            color: #10b981;
        }

        .detail-text {
            flex: 1;
        }

        .detail-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .detail-value {
            font-size: 15px;
            font-weight: 700;
            color: #1f2937;
        }

        .sidebar-card {
            position: sticky;
            top: 30px;
        }

        .payment-summary {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 25px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #6b7280;
        }

        .payment-row.total {
            padding-top: 15px;
            border-top: 2px solid #f3f4f6;
            margin-top: 10px;
        }

        .payment-row.total .label {
            font-weight: 700;
            color: #1f2937;
        }

        .payment-row.total .value {
            font-size: 22px;
            font-weight: 700;
            color: #3b82f6;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-primary {
            background: #3b82f6;
            color: #fff;
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: background 0.3s;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-success {
            background: #10b981;
            color: #fff;
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: background 0.3s;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: #fff;
            color: #dc2626;
            border: 2px solid #fecaca;
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-danger:hover {
            background: #fef2f2;
            border-color: #fca5a5;
        }

        .alert-box {
            background: #fef3c7;
            border: 2px solid #fde68a;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .alert-content {
            display: flex;
            gap: 10px;
            color: #92400e;
            margin-bottom: 12px;
        }

        .alert-content i {
            flex-shrink: 0;
            font-size: 18px;
        }

        .alert-content p {
            font-size: 12px;
            font-weight: 500;
            margin: 0;
            line-height: 1.5;
        }

        .user-info {
            padding-top: 25px;
            margin-top: 25px;
            border-top: 2px solid #f3f4f6;
        }

        .user-card {
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: 16px;
            flex-shrink: 0;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .user-id {
            font-size: 12px;
            color: #6b7280;
            margin: 3px 0 0 0;
        }

        .activity-log {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
        }

        .activity-title {
            font-size: 11px;
            color: #9ca3af;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .activity-item {
            display: flex;
            gap: 10px;
            font-size: 11px;
        }

        .activity-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-top: 4px;
            flex-shrink: 0;
        }

        .activity-dot.blue {
            background: #3b82f6;
        }

        .activity-dot.green {
            background: #10b981;
        }

        .activity-dot.gray {
            background: #6b7280;
        }

        .activity-dot.red {
            background: #dc2626;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title-text {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 2px;
        }

        .activity-time {
            color: #6b7280;
        }

        @media (max-width: 1024px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .sidebar-card {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 20px;
            }

            .toggle-btn {
                display: block;
            }

            h1 {
                font-size: 24px;
            }

            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .detail-row {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            body {
                background: white;
            }
            .back-btn,
            .header-actions,
            .action-buttons,
            .activity-log {
                display: none;
            }
            .card {
                box-shadow: none;
                border: 1px solid #e5e7eb;
            }
        }
    </style>
</head>
<body>

<div class="layout">
    <?php include '../partials/sidebar.php'; ?>

    <div class="content">
        <div class="topbar">
            <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
            <a href="../dashboard.php" class="back-btn">
                <i class="bi bi-chevron-left"></i>
                Kembali ke Dashboard
            </a>
        </div>

        <div>
            <h1>Detail Reservasi</h1>
            <div class="header-actions">
                <button onclick="window.print()" class="btn btn-outline">
                    <i class="bi bi-printer"></i> Cetak Invoice
                </button>
            </div>
        </div>

        <div class="detail-grid">
            
            <!-- Main Content Column -->
            <div>
                
                <!-- Card: Informasi Reservasi -->
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 15px;">
                            <div>
                                <h2>Informasi Reservasi</h2>
                                <p>Dibuat pada <?php echo date('d M Y, H:i', strtotime($reservation['created_at'])); ?></p>
                            </div>
                            <span class="status-badge <?php echo $display_status; ?>">
                                <?php echo str_replace('_', ' ', strtoupper($display_status)); ?>
                            </span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Kode Booking</span>
                                <span class="info-value code"><?php echo htmlspecialchars($reservation['kode_booking']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">ID Booking</span>
                                <span class="info-value">#<?php echo $reservation['id_booking']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">ID User</span>
                                <span class="info-value">USR-<?php echo $reservation['id_user']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">ID Penginapan</span>
                                <span class="info-value">H-00<?php echo $reservation['id_penginapan']; ?></span>
                            </div>
                        </div>

                        <div class="penginapan-name">Penginapan</div>
                        <p><?php echo htmlspecialchars($reservation['nama_penginapan']); ?></p>

                        <div class="detail-row">
                            <div class="detail-col">
                                <div class="detail-item">
                                    <div class="icon-box blue">
                                        <i class="bi bi-calendar-check"></i>
                                    </div>
                                    <div class="detail-text">
                                        <div class="detail-label">Check-In</div>
                                        <div class="detail-value"><?php echo date('d M Y', strtotime($reservation['tanggal_checkin'])); ?></div>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="icon-box orange">
                                        <i class="bi bi-calendar-x"></i>
                                    </div>
                                    <div class="detail-text">
                                        <div class="detail-label">Check-Out</div>
                                        <div class="detail-value"><?php echo date('d M Y', strtotime($reservation['tanggal_checkout'])); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-col">
                                <div class="detail-item">
                                    <div class="icon-box purple">
                                        <i class="bi bi-door-open"></i>
                                    </div>
                                    <div class="detail-text">
                                        <div class="detail-label">Tipe Kamar</div>
                                        <div class="detail-value"><?php echo htmlspecialchars($reservation['nama_tipe']); ?> (<?php echo $reservation['jumlah_kamar']; ?> Kamar)</div>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="icon-box green">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <div class="detail-text">
                                        <div class="detail-label">Kapasitas</div>
                                        <div class="detail-value"><?php echo $reservation['jumlah_orang']; ?> Orang</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Column -->
            <div>
                <div class="card sidebar-card">
                    <div class="card-header">
                        <h2><i class="bi bi-credit-card" style="color: #9ca3af;"></i> Ringkasan Pembayaran</h2>
                    </div>
                    
                    <div class="card-body">
                        <?php 
                        $subtotal = $reservation['total_harga'] * 0.9;
                        $pajak = $reservation['total_harga'] * 0.1;
                        ?>
                        
                        <div class="payment-summary">
                            <div class="payment-row">
                                <span class="label">Harga Kamar (x<?php echo $reservation['jumlah_kamar']; ?>)</span>
                                <span class="value"><?php echo formatCurrency($subtotal); ?></span>
                            </div>
                            <div class="payment-row">
                                <span class="label">Pajak & Layanan</span>
                                <span class="value"><?php echo formatCurrency($pajak); ?></span>
                            </div>
                            <div class="payment-row total">
                                <span class="label">Total Harga</span>
                                <span class="value"><?php echo formatCurrency($reservation['total_harga']); ?></span>
                            </div>
                        </div>

                        <?php if($display_status === 'dipesan'): ?>
                        <div class="action-buttons">
                            <form method="POST" id="checkinForm">
                                <input type="hidden" name="action" value="checkin">
                                <input type="hidden" name="id_booking" value="<?php echo $id_booking; ?>">
                                <button type="button" class="btn btn-primary" onclick="confirmCheckin()">
                                    <i class="bi bi-check-lg"></i> Konfirmasi Check-In
                                </button>
                            </form>
                            <button class="btn btn-danger" onclick="confirmCancel()">
                                <i class="bi bi-x-lg"></i> Batalkan Pesanan
                            </button>
                        </div>
                        <?php elseif($display_status === 'check-in'): ?>
                        <div class="action-buttons">
                            <form method="POST" id="checkoutForm">
                                <input type="hidden" name="action" value="checkout">
                                <input type="hidden" name="id_booking" value="<?php echo $id_booking; ?>">
                                <button type="button" class="btn btn-success" onclick="confirmCheckout()">
                                    <i class="bi bi-door-open"></i> Proses Check-Out
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>

                        <div class="user-info">
                            <div class="user-card">
                                <div class="user-avatar">
                                    <i class="bi bi-person"></i>
                                </div>
                                <div class="user-details">
                                    <p class="user-name"><?php echo htmlspecialchars($reservation['nama_user']); ?></p>
                                    <p class="user-id"><?php echo htmlspecialchars($reservation['email_user']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Log -->
                <div class="activity-log">
                    <h4 class="activity-title">Aktivitas Terakhir</h4>
                    <div class="activity-list">
                        <div class="activity-item">
                            <div class="activity-dot blue"></div>
                            <div class="activity-content">
                                <p class="activity-title-text">Reservasi Dibuat</p>
                                <p class="activity-time"><?php echo date('d M, H:i', strtotime($reservation['created_at'])); ?></p>
                            </div>
                        </div>
                        <?php if($display_status === 'check-in' || $display_status === 'selesai'): ?>
                        <div class="activity-item">
                            <div class="activity-dot green"></div>
                            <div class="activity-content">
                                <p class="activity-title-text">Check-In Berhasil</p>
                                <p class="activity-time">Dikonfirmasi oleh Admin</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($display_status === 'selesai'): ?>
                        <div class="activity-item">
                            <div class="activity-dot gray"></div>
                            <div class="activity-content">
                                <p class="activity-title-text">Reservasi Selesai</p>
                                <p class="activity-time">Check-out berhasil</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if($display_status === 'dibatalkan'): ?>
                        <div class="activity-item">
                            <div class="activity-dot red"></div>
                            <div class="activity-content">
                                <p class="activity-title-text">Reservasi Dibatalkan</p>
                                <p class="activity-time">Oleh Admin</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if(sidebar) {
        sidebar.classList.toggle('active');
    }
}

function confirmCheckin() {
    Swal.fire({
        title: 'Konfirmasi Check-In?',
        html: `
            <div style="text-align: left; padding: 15px;">
                <p style="margin-bottom: 10px;">Pastikan tamu sudah tiba di lokasi dan siap untuk check-in.</p>
                <div style="background: #dbeafe; padding: 12px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                    <p style="color: #1e40af; font-size: 13px; margin: 0;">
                        ✓ Status akan berubah menjadi <strong>CHECK-IN</strong>
                    </p>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '✓ Ya, Konfirmasi Check-In',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('checkinForm').submit();
        }
    });
}

function confirmCheckout() {
    Swal.fire({
        title: 'Konfirmasi Check-Out?',
        html: `
            <div style="text-align: left; padding: 15px;">
                <p style="margin-bottom: 10px;">Pastikan tamu sudah menyelesaikan proses check-out.</p>
                <div style="background: #d1fae5; padding: 12px; border-radius: 8px; border-left: 4px solid #10b981;">
                    <p style="color: #065f46; font-size: 13px; margin: 0;">
                        ✓ Status akan berubah menjadi <strong>SELESAI</strong>
                    </p>
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '✓ Ya, Konfirmasi Check-Out',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('checkoutForm').submit();
        }
    });
}

function confirmCancel() {
    Swal.fire({
        title: 'Batalkan Reservasi?',
        html: `
            <div style="text-align: left; padding: 15px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1f2937;">
                    Alasan Pembatalan:
                </label>
                <textarea 
                    id="alasan" 
                    class="swal2-textarea" 
                    placeholder="Masukkan alasan pembatalan..."
                    style="width: 100%; min-height: 80px; font-family: 'Poppins', sans-serif;"
                ></textarea>
                <div style="background: #fee2e2; padding: 12px; border-radius: 8px; border-left: 4px solid #ef4444; margin-top: 15px;">
                    <p style="color: #991b1b; font-size: 13px; margin: 0;">
                        ⚠ Tindakan ini tidak dapat dibatalkan
                    </p>
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '✗ Ya, Batalkan Reservasi',
        cancelButtonText: 'Tidak',
        reverseButtons: true,
        preConfirm: () => {
            const alasan = document.getElementById('alasan').value;
            if (!alasan || alasan.trim().length < 10) {
                Swal.showValidationMessage('Alasan minimal 10 karakter');
                return false;
            }
            return alasan;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="id_booking" value="<?php echo $id_booking; ?>">
                <input type="hidden" name="alasan" value="${result.value}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Show success/error messages
<?php if(isset($_SESSION['success'])): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '<?php echo $_SESSION['success']; ?>',
        confirmButtonColor: '#10b981'
    });
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if(isset($_SESSION['error'])): ?>
    Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: '<?php echo $_SESSION['error']; ?>',
        confirmButtonColor: '#ef4444'
    });
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
</script>

</body>
</html>
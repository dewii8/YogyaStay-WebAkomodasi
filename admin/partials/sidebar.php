<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current = basename($_SERVER['PHP_SELF']);
$adminName = $_SESSION['nama'] ?? 'Admin';

/*  DYNAMIC BASE URL   */
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$scriptName = $_SERVER['SCRIPT_NAME'];
$pathParts = explode('/', $scriptName);

// Cari index 'admin' untuk menentukan base path
$adminIndex = array_search('admin', $pathParts);
if ($adminIndex !== false) {
    $baseParts = array_slice($pathParts, 0, $adminIndex);
    $baseUrl = $protocol . "://" . $host . implode('/', $baseParts);
} else {
    $baseUrl = $protocol . "://" . $host . dirname(dirname($_SERVER['SCRIPT_NAME']));
}

/* BASE URL ADMIN */
$baseAdmin = $baseUrl . "/admin";

/* LOGO */
$logoYogyaStay = $baseUrl . "/assets/img/logo.png";
$logoAdmin = $baseUrl . "/assets/img/admin.png";

// Logout handler 
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: $baseUrl/autentikasi/login.php");
    exit;
}

?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /*  STYLING SIDEBAR  */
    .sidebar {
        width: 260px;
        min-height: 100vh;
        height: 100%;
        background: linear-gradient(180deg, #9aa7c5 0%, #7d8ba8 100%);
        color: white;
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        display: flex;
        flex-direction: column;
        z-index: 1001;
        transition: transform 0.3s ease, width 0.3s ease;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Custom scrollbar untuk sidebar */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 10px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    .sidebar-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        z-index: 1000;
        transition: opacity 0.3s ease;
    }

    .sidebar-overlay.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .sidebar-header {
        padding: 25px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .sidebar-header img {
        width: 42px;
        height: 42px;
        object-fit: contain;
    }

    .sidebar-header h2 {
        font-size: 22px;
        font-weight: 700;
        margin: 0;
    }

    .menu {
        padding: 20px 12px;
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1;
    }

    .menu a {
        color: white;
        text-decoration: none;
        padding: 14px 16px;
        border-radius: 12px;
        display: flex;
        gap: 12px;
        align-items: center;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .menu a i {
        font-size: 18px;
        width: 22px;
        text-align: center;
    }

    .menu a:hover {
        background: rgba(255, 255, 255, 0.15);
        padding-left: 20px;
    }

    .menu a.active {
        background: #f4b740;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(244, 183, 64, 0.4);
    }

    .menu a.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: white;
    }

    .sidebar-footer {
        margin-top: auto;
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(0, 0, 0, 0.1);
    }

    .footer-user {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
    }

    .footer-user i {
        font-size: 38px;
        color: white;
    }

    .footer-user-text {
        display: flex;
        flex-direction: column;
        line-height: 1.3;
    }

    .footer-user-text strong {
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 120px;
    }

    .footer-user-text span {
        font-size: 12px;
        opacity: 0.85;
    }

    .logout-btn {
        color: white;
        font-size: 20px;
        text-decoration: none;
        padding: 8px;
        border-radius: 8px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .logout-btn:hover {
        background: rgba(239, 68, 68, 0.2);
        color: #fee2e2;
    }

    /* HAMBURGER BUTTON */
    .hamburger-btn {
        display: none;
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1002;
        background: #9aa7c5;
        color: white;
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 12px;
        cursor: pointer;
        font-size: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }

    .hamburger-btn:hover {
        background: #7d8ba8;
        transform: scale(1.05);
    }

    .hamburger-btn:active {
        transform: scale(0.95);
    }

    /* RESPONSIVE BREAKPOINTS */

    /* Tablet & Mobile (max-width: 992px) */
    @media (max-width: 992px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .hamburger-btn {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar.active~.hamburger-btn {
            left: 270px;
        }
    }

    /* Mobile (max-width: 768px) */
    @media (max-width: 768px) {
        .sidebar {
            width: 280px;
        }

        .sidebar-header {
            padding: 20px 16px;
        }

        .sidebar-header h2 {
            font-size: 20px;
        }

        .sidebar-header img {
            width: 38px;
            height: 38px;
        }

        .menu {
            padding: 16px 10px;
        }

        .menu a {
            padding: 12px 14px;
            font-size: 13px;
        }

        .menu a i {
            font-size: 16px;
            width: 20px;
        }

        .sidebar-footer {
            padding: 14px 16px;
        }

        .footer-user i {
            font-size: 34px;
        }

        .footer-user-text strong {
            font-size: 13px;
            max-width: 100px;
        }

        .footer-user-text span {
            font-size: 11px;
        }
    }

    /* Mobile Small (max-width: 480px) */
    @media (max-width: 480px) {
        .sidebar {
            width: 85vw;
            max-width: 300px;
        }

        .sidebar-header {
            padding: 18px 14px;
        }

        .sidebar-header h2 {
            font-size: 18px;
        }

        .sidebar-header img {
            width: 36px;
            height: 36px;
        }

        .menu {
            padding: 14px 8px;
            gap: 4px;
        }

        .menu a {
            padding: 11px 12px;
            font-size: 13px;
        }

        .sidebar-footer {
            padding: 12px 14px;
        }

        .footer-user {
            gap: 10px;
        }

        .footer-user i {
            font-size: 32px;
        }

        .footer-user-text strong {
            font-size: 12px;
            max-width: 90px;
        }

        .hamburger-btn {
            width: 42px;
            height: 42px;
            font-size: 18px;
            top: 15px;
            left: 15px;
        }
    }

    /* Touch devices */
    @media (hover: none) and (pointer: coarse) {
        .menu a {
            padding: 16px;
            min-height: 48px;
        }

        .logout-btn {
            min-width: 44px;
            min-height: 44px;
        }
    }
</style>

<!-- Hamburger Button -->
<button class="hamburger-btn" id="hamburgerBtn" onclick="toggleSidebar()" aria-label="Toggle Menu">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <img src="<?= $logoYogyaStay ?>" alt="YogyaStay Logo">
        <h2>YogyaStay</h2>
    </div>

    <nav class="menu">
        <a href="<?= $baseAdmin ?>/dashboard.php" class="<?= $current == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?= $baseAdmin ?>/inventoriKamar/inventori.php" class="<?= $current == 'inventori.php' ? 'active' : '' ?>">
            <i class="fas fa-bed"></i>
            <span>Inventori Kamar & Harga</span>
        </a>

        <a href="<?= $baseAdmin ?>/managemenUser/users.php" class="<?= $current == 'users.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Manajemen User</span>
        </a>

        <a href="<?= $baseAdmin ?>/pembatalanKamar/pembatalan.php"
            class="<?= $current == 'pembatalan.php' ? 'active' : '' ?>">
            <i class="fas fa-times-circle"></i>
            <span>Pembatalan Kamar</span>
        </a>

        <a href="<?= $baseAdmin ?>/manajemenKonten/konten.php" class="<?= $current == 'konten.php' ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i>
            <span>Manajemen Konten</span>
        </a>

        <a href="<?= $baseAdmin ?>/log/log.php" class="<?= $current == 'log.php' ? 'active' : '' ?>">
            <i class="fas fa-clipboard-list"></i>
            <span>Log Aktivitas</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="footer-user">
            <i class="fas fa-user-circle"></i>
            <div class="footer-user-text">
                <strong>
                    <?= htmlspecialchars($adminName) ?>
                </strong>
                <span>Admin</span>
            </div>
        </div>

        <a href="#" class="logout-btn" title="Logout" onclick="confirmLogout(event)">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>

</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const hamburger = document.getElementById('hamburgerBtn');

        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');

        // Update hamburger icon
        const icon = hamburger.querySelector('i');
        if (sidebar.classList.contains('active')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
    }

    // SweetAlert Logout Confirmation
    function confirmLogout(event) {
        event.preventDefault();

        Swal.fire({
            title: 'Logout',
            text: "Apakah Anda yakin ingin keluar?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Logout',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?= $baseUrl ?>/autentikasi/logout.php';
            }
        });
    }

    // Tutup Sidebar
    document.addEventListener('DOMContentLoaded', function () {
        const menuLinks = document.querySelectorAll('.menu a');
        const isTablet = window.innerWidth <= 992;

        if (isTablet) {
            menuLinks.forEach(link => {
                link.addEventListener('click', function () {
                    setTimeout(() => {
                        toggleSidebar();
                    }, 200);
                });
            });
        }

        window.addEventListener('resize', function () {
            if (window.innerWidth > 992) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.attributeName === 'class') {
                    if (sidebar.classList.contains('active') && window.innerWidth <= 992) {
                        document.body.style.overflow = 'hidden';
                    } else {
                        document.body.style.overflow = '';
                    }
                }
            });
        });

        observer.observe(sidebar, { attributes: true });
    });
</script>